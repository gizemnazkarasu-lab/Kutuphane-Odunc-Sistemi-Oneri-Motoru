<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Copy;
use App\Model\Fine;
use App\Model\Loan;
use App\Repository\CopyRepositoryInterface;
use App\Repository\FineRepositoryInterface;
use App\Repository\JsonStorage;
use App\Repository\LoanRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use DomainException;

final class LoanService
{
    public function __construct(
        private LoanRepositoryInterface $loanRepository,
        private CopyRepositoryInterface $copyRepository,
        private ReservationRepositoryInterface $reservationRepository,
        private ReservationService $reservationService,
        private FineService $fineService,
        private FineRepositoryInterface $fineRepository,
        private EventDispatcher $eventDispatcher,
        private ?UserRepositoryInterface $userRepository = null,
        private ?AuthorizationService $authorizationService = null,
        private ?JsonStorage $storage = null,
        private int $maxActiveLoans = 5,
        private int $loanDurationDays = 14,
        private int $maxRenewals = 1
    ) {}

    public function borrow(int $actorId, int $userId, int $copyId, ?DateTimeImmutable $borrowedAt = null): Loan
    {
        if ($this->authorizationService !== null) {
            $this->authorizationService->requireOwnerOrStaff($actorId, $userId);
        }

        if ($this->userRepository !== null) {
            $member = $this->userRepository->findById($userId);
            if ($member === null || !$member->active) {
                throw new DomainException('Ödünç alacak üye bulunamadı veya aktif değil.');
            }
        }

        $now = $borrowedAt ?? new DateTimeImmutable();

        // Önce süresi dolmuş 3 günlük rezervasyon ayırmalarını temizle
        $this->reservationService->expireAllocations($now);

        // 1. Üyenin 5 kitap limiti kontrolü
        $activeCount = $this->loanRepository->countActiveByUser($userId);
        if ($activeCount >= $this->maxActiveLoans) {
            throw new DomainException("Ödünç limiti aşıldı! Bir üye aynı anda en fazla {$this->maxActiveLoans} aktif kitap ödünç alabilir.");
        }

        // 2. Fiziksel kopya kontrolü (Eşzamanlılık ve müsaitlik kontrolü)
        $copy = $this->copyRepository->findById($copyId);
        if ($copy === null) {
            throw new DomainException('Fiziksel kitap kopyası bulunamadı.');
        }

        if ($this->loanRepository->findActiveByCopyId($copyId) !== null || $copy->status === Copy::STATUS_LOANED) {
            throw new DomainException("Bu kitap kopyası ({$copy->inventoryCode}) şu anda başka bir üyede ödünçtedir.");
        }

        // 3. Kopya bir rezervasyon için ayrılmış mı kontrol et
        $activeAllocation = $this->reservationRepository->findActiveByCopy($copyId);
        if ($copy->status === Copy::STATUS_ALLOCATED || $activeAllocation !== null) {
            if ($activeAllocation === null || $activeAllocation->userId !== $userId) {
                throw new DomainException("Bu kitap kopyası ({$copy->inventoryCode}) rezervasyon sırasındaki başka bir üyeye 3 gün süreyle ayrılmıştır.");
            }
            // Kopya bu üyeye ayrılmışsa, rezervasyon durumunu State deseniyle CompletedState'e geçir
            $this->reservationService->complete($activeAllocation->id);
        }

        $dueAt = $now->modify("+{$this->loanDurationDays} days");

        $loan = new Loan(
            id: 0,
            userId: $userId,
            copyId: $copy->id,
            borrowedAt: $now,
            dueAt: $dueAt,
            returnedAt: null,
            renewalCount: 0
        );

        $copy->status = Copy::STATUS_LOANED;
        $this->copyRepository->save($copy);
        $this->loanRepository->save($loan);

        $this->storage?->logAudit(
            $actorId,
            "Kullanıcı #{$actorId}",
            'LOAN_BORROW',
            "Üye #{$userId} için Kopya {$copy->inventoryCode} ödünç verildi (Teslim: {$dueAt->format('d.m.Y')})."
        );

        return $loan;
    }

    /**
     * Kitap iadesi, gecikme cezası hesabı, rezervasyon sırasındaki ilk üyeye 3 günlük ayırma ve bildirim.
     *
     * @return array{loan: Loan, fineAmountInKurus: int, fine: ?Fine, allocationEvent: ?ReservationAllocatedEvent}
     */
    public function returnCopy(int $actorId, int $loanId, ?DateTimeImmutable $returnedAt = null): array
    {
        $loan = $this->loanRepository->findById($loanId);
        if ($loan === null) {
            throw new DomainException('Ödünç kaydı bulunamadı.');
        }

        if ($this->authorizationService !== null) {
            $this->authorizationService->requireOwnerOrStaff($actorId, $loan->userId);
        }

        if ($loan->returnedAt !== null) {
            throw new DomainException('Bu kitap zaten iade edilmiş.');
        }

        // Tek bir iade zamanı alınır (tasarım.md Bölüm 3 iş kuralı)
        $now = $returnedAt ?? new DateTimeImmutable();

        // 1. Gecikme cezasını hesapla (Strategy deseni)
        $fineAmount = $this->fineService->calculate($loan, $now);
        $fine = null;

        // 2. İade zamanını kaydet
        $loan->returnedAt = $now;
        $this->loanRepository->save($loan);

        // 3. Ceza sıfırdan büyükse ceza kaydı oluştur
        if ($fineAmount > 0) {
            $fine = new Fine(
                id: 0,
                loanId: $loan->id,
                amountInKurus: $fineAmount,
                calculatedAt: $now
            );
            $this->fineRepository->save($fine);
        }

        // 4. Rezervasyon kuyruğundaki ilk uygun üyeye 3 günlüğüne ayır veya müsait yap (State deseni)
        $allocationEvent = $this->reservationService->allocateNext($loan->copyId, $now);

        // 5. Veri işlemleri tamamlandıktan sonra bildirim olayını yayımla (Observer deseni)
        if ($allocationEvent !== null) {
            $this->eventDispatcher->dispatch($allocationEvent);
        }

        $this->storage?->logAudit(
            $actorId,
            "Kullanıcı #{$actorId}",
            'LOAN_RETURN',
            "Ödünç #{$loan->id} iade alındı. Ceza: " . number_format($fineAmount / 100, 2, ',', '.') . " TL."
            . ($allocationEvent !== null ? " Kopya Üye #{$allocationEvent->userId} için 3 günlüğüne ayrıldı." : " Kopya müsait duruma geçti.")
        );

        return [
            'loan' => $loan,
            'fineAmountInKurus' => $fineAmount,
            'fine' => $fine,
            'allocationEvent' => $allocationEvent,
        ];
    }

    public function renew(int $actorId, int $loanId, ?DateTimeImmutable $now = null): Loan
    {
        $loan = $this->loanRepository->findById($loanId);
        if ($loan === null) {
            throw new DomainException('Ödünç kaydı bulunamadı.');
        }

        if ($this->authorizationService !== null) {
            $this->authorizationService->requireOwnerOrStaff($actorId, $loan->userId);
        }

        if ($loan->returnedAt !== null) {
            throw new DomainException('İade edilmiş bir ödünç işlemi uzatılamaz.');
        }

        if ($loan->renewalCount >= $this->maxRenewals) {
            throw new DomainException('Bu kitap için uzatma hakkı kullanılmış (en fazla 1 kez uzatılabilir).');
        }

        $now = $now ?? new DateTimeImmutable();
        if ($loan->dueAt < $now) {
            throw new DomainException('Teslim tarihi geçmiş (gecikmiş) kitaplar için süre uzatımı yapılamaz. Lütfen kitabı kütüphaneciye teslim ediniz.');
        }

        // Kitap için bekleyen başka bir üye varsa uzatma yapılamaz
        $copy = $this->copyRepository->findById($loan->copyId);
        if ($copy !== null && $this->reservationRepository->findFirstWaiting($copy->bookId) !== null) {
            throw new DomainException('Bu kitap için rezervasyon sırasında bekleyen başka bir üye olduğundan süre uzatılamaz.');
        }

        $loan->dueAt = $loan->dueAt->modify("+{$this->loanDurationDays} days");
        $loan->renewalCount++;
        $this->loanRepository->save($loan);

        $this->storage?->logAudit(
            $actorId,
            "Kullanıcı #{$actorId}",
            'LOAN_RENEW',
            "Ödünç #{$loan->id} süresi uzatıldı. Yeni teslim tarihi: {$loan->dueAt->format('d.m.Y')}."
        );

        return $loan;
    }
}