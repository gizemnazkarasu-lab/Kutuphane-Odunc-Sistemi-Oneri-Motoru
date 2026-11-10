<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Copy;
use App\Model\Reservation;
use App\Repository\BookRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\LoanRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use DateTimeImmutable;
use DomainException;

final class ReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private CopyRepositoryInterface $copyRepository,
        private BookRepositoryInterface $bookRepository,
        private ReservationStateFactory $stateFactory = new ReservationStateFactory(),
        private ?LoanRepositoryInterface $loanRepository = null,
        private ?EventDispatcher $eventDispatcher = null,
        private int $holdDays = 3
    ) {}

    public function joinQueue(int $userId, int $bookId, ?DateTimeImmutable $now = null): Reservation
    {
        $now = $now ?? new DateTimeImmutable();

        $book = $this->bookRepository->findById($bookId);
        if ($book === null) {
            throw new DomainException('Rezervasyon yapılmak istenen kitap sistemde bulunamadı.');
        }

        // Aynı kitap için aktif rezervasyon kontrolü
        $existing = $this->reservationRepository->findActiveByUserAndBook($userId, $bookId);
        if ($existing !== null) {
            throw new DomainException('Bu kitap için zaten aktif bir rezervasyonunuz (sırada veya ayrılmış) bulunmaktadır.');
        }

        // Kullanıcının elinde bu kitabın aktif bir ödüncü var mı kontrol et
        if ($this->loanRepository !== null) {
            $userLoans = $this->loanRepository->findByUser($userId);
            foreach ($userLoans as $loan) {
                if ($loan->returnedAt !== null) {
                    continue;
                }
                $copy = $this->copyRepository->findById($loan->copyId);
                if ($copy !== null && $copy->bookId === $bookId) {
                    throw new DomainException('Bu kitabın bir kopyası zaten şu anda sizde ödünçtedir.');
                }
            }
        }

        // Müsait kopya varsa doğrudan ödünç alınabilir uyarısı
        $availableCopies = $this->copyRepository->findAvailableByBookId($bookId);
        if (count($availableCopies) > 0) {
            throw new DomainException('Bu kitabın müsait kopyası bulunmaktadır. Bekleme sırasına girmeden doğrudan ödünç alabilirsiniz.');
        }

        $reservation = new Reservation(
            id: 0,
            userId: $userId,
            bookId: $bookId,
            allocatedCopyId: null,
            createdAt: $now,
            allocatedAt: null,
            expiresAt: null,
            status: Reservation::STATUS_WAITING
        );

        $this->reservationRepository->save($reservation);
        return $reservation;
    }

    public function cancel(int $reservationId, int $userId): void
    {
        $reservation = $this->reservationRepository->findById($reservationId);
        if ($reservation === null) {
            throw new DomainException('Rezervasyon kaydı bulunamadı.');
        }

        if ($reservation->userId !== $userId) {
            throw new DomainException('Yalnızca kendi rezervasyonunuzu iptal edebilirsiniz.');
        }

        $allocatedCopyId = $reservation->allocatedCopyId;

        $state = $this->stateFactory->fromStatus($reservation->status);
        $state->cancel($reservation);
        $reservation->allocatedCopyId = null;
        $reservation->expiresAt = null;
        $this->reservationRepository->save($reservation);

        // Eğer üyeye ayrılmış bir kopya iptal edildiyse, kopyayı sıradaki üyeye ayır veya müsait yap
        if ($allocatedCopyId !== null) {
            $event = $this->allocateNext($allocatedCopyId);
            if ($event !== null && $this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch($event);
            }
        }
    }

    public function complete(int $reservationId): void
    {
        $reservation = $this->reservationRepository->findById($reservationId);
        if ($reservation === null) {
            throw new DomainException('Rezervasyon kaydı bulunamadı.');
        }

        $state = $this->stateFactory->fromStatus($reservation->status);
        $state->complete($reservation);
        $this->reservationRepository->save($reservation);
    }

    /**
     * @return Reservation[]
     */
    public function listForUser(int $userId): array
    {
        return $this->reservationRepository->findByUser($userId);
    }

    /**
     * İade edilen veya boşa çıkan kopyayı sıradaki ilk bekleyen üyeye 3 günlüğüne ayırır.
     */
    public function allocateNext(int $copyId, ?DateTimeImmutable $now = null): ?ReservationAllocatedEvent
    {
        $now = $now ?? new DateTimeImmutable();

        $copy = $this->copyRepository->findById($copyId);
        if ($copy === null) {
            throw new DomainException('Fiziksel kopya bulunamadı.');
        }

        $nextReservation = $this->reservationRepository->findFirstWaiting($copy->bookId);
        if ($nextReservation === null) {
            $copy->status = Copy::STATUS_AVAILABLE;
            $this->copyRepository->save($copy);
            return null;
        }

        // State deseni ile WaitingState -> AllocatedState geçişi
        $state = $this->stateFactory->fromStatus($nextReservation->status);
        $state->allocate($nextReservation);

        $expiresAt = $now->modify("+{$this->holdDays} days");
        $nextReservation->allocatedCopyId = $copy->id;
        $nextReservation->allocatedAt = $now;
        $nextReservation->expiresAt = $expiresAt;
        $this->reservationRepository->save($nextReservation);

        $copy->status = Copy::STATUS_ALLOCATED;
        $this->copyRepository->save($copy);

        $book = $this->bookRepository->findById($copy->bookId);

        return new ReservationAllocatedEvent(
            reservationId: $nextReservation->id,
            userId: $nextReservation->userId,
            copyId: $copy->id,
            expiresAt: $expiresAt,
            bookTitle: $book !== null ? $book->title : '',
            inventoryCode: $copy->inventoryCode
        );
    }

    /**
     * 3 günlük teslim alma süresi dolan ayırmaları düşürür ve sıradaki üyeye devreder.
     *
     * @return ReservationAllocatedEvent[]
     */
    public function expireAllocations(?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable();
        $expiredList = $this->reservationRepository->findExpiredAllocations($now);
        $events = [];

        foreach ($expiredList as $reservation) {
            $copyId = $reservation->allocatedCopyId;

            $state = $this->stateFactory->fromStatus($reservation->status);
            $state->expire($reservation);
            $reservation->allocatedCopyId = null;
            $this->reservationRepository->save($reservation);

            if ($copyId !== null) {
                $event = $this->allocateNext($copyId, $now);
                if ($event !== null) {
                    $events[] = $event;
                    if ($this->eventDispatcher !== null) {
                        $this->eventDispatcher->dispatch($event);
                    }
                }
            }
        }

        return $events;
    }

    public function getQueuePosition(int $reservationId): ?int
    {
        $target = $this->reservationRepository->findById($reservationId);
        if ($target === null) {
            return null;
        }

        if ($target->status === Reservation::STATUS_ALLOCATED) {
            return 1;
        }

        if ($target->status !== Reservation::STATUS_WAITING) {
            return null;
        }

        $queue = $this->reservationRepository->findByBook($target->bookId);
        $pos = 1;
        foreach ($queue as $item) {
            if (!in_array($item->status, [Reservation::STATUS_ALLOCATED, Reservation::STATUS_WAITING], true)) {
                continue;
            }
            if ($item->id === $target->id) {
                return $pos;
            }
            $pos++;
        }

        return $pos;
    }
}