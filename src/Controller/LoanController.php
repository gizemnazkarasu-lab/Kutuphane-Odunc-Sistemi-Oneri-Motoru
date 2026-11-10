<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\BookRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\FineRepositoryInterface;
use App\Repository\LoanRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\LoanService;
use DateTimeImmutable;
use DomainException;

final class LoanController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private LoanService $loanService,
        private LoanRepositoryInterface $loanRepository,
        private CopyRepositoryInterface $copyRepository,
        private BookRepositoryInterface $bookRepository,
        private UserRepositoryInterface $userRepository,
        private FineRepositoryInterface $fineRepository,
        private ReservationRepositoryInterface $reservationRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function myLoans(): void
    {
        $user = $this->requireLogin();
        $loans = $this->loanRepository->findByUser($user->id);
        $activeCount = $this->loanRepository->countActiveByUser($user->id);
        $now = new DateTimeImmutable();

        $activeLoans = [];
        $historyLoans = [];

        foreach ($loans as $loan) {
            $copy = $this->copyRepository->findById($loan->copyId);
            $book = $copy !== null ? $this->bookRepository->findById($copy->bookId) : null;
            $fine = $this->fineRepository->findByLoanId($loan->id);
            $isOverdue = $loan->returnedAt === null && $loan->dueAt < $now;
            $hasWaitingQueue = $copy !== null && $this->reservationRepository->findFirstWaiting($copy->bookId) !== null;

            $item = [
                'loan' => $loan,
                'copy' => $copy,
                'book' => $book,
                'fine' => $fine,
                'isOverdue' => $isOverdue,
                'canRenew' => $loan->returnedAt === null && !$isOverdue && $loan->renewalCount < 1 && !$hasWaitingQueue,
                'hasWaitingQueue' => $hasWaitingQueue,
            ];

            if ($loan->returnedAt === null) {
                $activeLoans[] = $item;
            } else {
                $historyLoans[] = $item;
            }
        }

        $this->render('my-loans', [
            'pageTitle' => 'Ödünçlerim ve Geçmişim',
            'activeCount' => $activeCount,
            'activeLoans' => $activeLoans,
            'historyLoans' => $historyLoans,
        ]);
    }

    public function manage(): void
    {
        $this->requireRoles(['librarian', 'admin']);

        $allLoans = $this->loanRepository->findAll();
        $now = new DateTimeImmutable();
        $activeLoanRows = [];
        $recentReturnedRows = [];

        foreach ($allLoans as $loan) {
            $copy = $this->copyRepository->findById($loan->copyId);
            $book = $copy !== null ? $this->bookRepository->findById($copy->bookId) : null;
            $member = $this->userRepository->findById($loan->userId);
            $fine = $this->fineRepository->findByLoanId($loan->id);
            $waitingNext = $copy !== null ? $this->reservationRepository->findFirstWaiting($copy->bookId) : null;
            $waitingUser = $waitingNext !== null ? $this->userRepository->findById($waitingNext->userId) : null;

            $row = [
                'loan' => $loan,
                'copy' => $copy,
                'book' => $book,
                'member' => $member,
                'fine' => $fine,
                'isOverdue' => $loan->returnedAt === null && $loan->dueAt < $now,
                'waitingUser' => $waitingUser,
            ];

            if ($loan->returnedAt === null) {
                $activeLoanRows[] = $row;
            } else {
                $recentReturnedRows[] = $row;
            }
        }

        $borrowableCopies = [];
        foreach ($this->copyRepository->findAll() as $copy) {
            if ($copy->status === 'loaned') {
                continue;
            }
            $book = $this->bookRepository->findById($copy->bookId);
            $allocation = $this->reservationRepository->findActiveByCopy($copy->id);
            $allocatedUser = $allocation !== null ? $this->userRepository->findById($allocation->userId) : null;
            $borrowableCopies[] = [
                'copy' => $copy,
                'book' => $book,
                'allocatedUser' => $allocatedUser,
            ];
        }

        $this->render('librarian-loans', [
            'pageTitle' => 'Ödünç Verme ve İade İşlemleri',
            'activeLoanRows' => $activeLoanRows,
            'recentReturnedRows' => array_slice($recentReturnedRows, 0, 8),
            'members' => $this->userRepository->findAll(),
            'borrowableCopies' => $borrowableCopies,
        ]);
    }

    public function borrow(): void
    {
        $actor = $this->requireLogin();
        $userId = (int) ($_POST['user_id'] ?? $actor->id);
        $copyId = (int) ($_POST['copy_id'] ?? 0);
        $returnRoute = (string) ($_POST['return_route'] ?? ($actor->role === 'member' ? 'my-loans' : 'librarian/loans'));

        try {
            $loan = $this->loanService->borrow($actor->id, $userId, $copyId);
            $copy = $this->copyRepository->findById($loan->copyId);
            $book = $copy !== null ? $this->bookRepository->findById($copy->bookId) : null;
            $title = $book !== null ? $book->title : 'Kitap';
            $code = $copy !== null ? $copy->inventoryCode : "#{$copyId}";

            $this->redirect(
                $returnRoute,
                "Ödünç işlemi tamamlandı! '{$title}' ({$code}) kaydedildi. Son teslim tarihi: {$loan->dueAt->format('d.m.Y')}."
            );
        } catch (DomainException $e) {
            $this->redirect($returnRoute, null, $e->getMessage());
        }
    }

    public function returnCopy(): void
    {
        $actor = $this->requireLogin();
        $loanId = (int) ($_POST['loan_id'] ?? 0);
        $simulateDaysLate = isset($_POST['simulate_days_late']) ? (int) $_POST['simulate_days_late'] : 0;
        $returnRoute = (string) ($_POST['return_route'] ?? ($actor->role === 'member' ? 'my-loans' : 'librarian/loans'));

        try {
            $returnedAt = null;
            if ($simulateDaysLate > 0) {
                $loanObj = $this->loanRepository->findById($loanId);
                if ($loanObj !== null) {
                    $returnedAt = $loanObj->dueAt->modify("+{$simulateDaysLate} days");
                }
            }

            $result = $this->loanService->returnCopy($actor->id, $loanId, $returnedAt);
            $fineTl = number_format($result['fineAmountInKurus'] / 100, 2, ',', '.');
            $allocEvent = $result['allocationEvent'];

            $msg = "Kitap iadesi başarıyla kaydedildi.";
            if ($result['fineAmountInKurus'] > 0) {
                $msg .= " Gecikme Cezası: {$fineTl} TL.";
            } else {
                $msg .= " Gecikme cezası oluşmadı (0,00 TL).";
            }

            if ($allocEvent !== null) {
                $allocatedUser = $this->userRepository->findById($allocEvent->userId);
                $userName = $allocatedUser !== null ? $allocatedUser->name : "Üye #{$allocEvent->userId}";
                $msg .= " Rezervasyon sırasındaki ilk uygun üye ({$userName}) için kopya 3 gün süreyle ({$allocEvent->expiresAt->format('d.m.Y H:i')} tarihine kadar) ayrıldı ve sistem içi bildirim gönderildi!";
            } else {
                $msg .= " Bekleyen rezervasyon olmadığı için kopya 'Müsait' durumuna geçirildi.";
            }

            $this->redirect($returnRoute, $msg);
        } catch (DomainException $e) {
            $this->redirect($returnRoute, null, $e->getMessage());
        }
    }

    public function renew(): void
    {
        $actor = $this->requireLogin();
        $loanId = (int) ($_POST['loan_id'] ?? 0);
        $returnRoute = (string) ($_POST['return_route'] ?? 'my-loans');

        try {
            $loan = $this->loanService->renew($actor->id, $loanId);
            $this->redirect(
                $returnRoute,
                "Ödünç süresi 14 gün uzatıldı! Yeni teslim tarihi: {$loan->dueAt->format('d.m.Y')} (Uzatma hakkı: {$loan->renewalCount} / 1)."
            );
        } catch (DomainException $e) {
            $this->redirect($returnRoute, null, $e->getMessage());
        }
    }
}