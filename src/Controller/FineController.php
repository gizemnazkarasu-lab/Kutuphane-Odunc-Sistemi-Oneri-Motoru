<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\BookRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\FineRepositoryInterface;
use App\Repository\LoanRepositoryInterface;
use App\Repository\PaymentRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\FineService;
use DomainException;

final class FineController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private FineService $fineService,
        private FineRepositoryInterface $fineRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private LoanRepositoryInterface $loanRepository,
        private CopyRepositoryInterface $copyRepository,
        private BookRepositoryInterface $bookRepository,
        private UserRepositoryInterface $userRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function myFines(): void
    {
        $user = $this->requireLogin();
        $rows = [];
        $totalRemainingInKurus = 0;

        foreach ($this->fineRepository->findAll() as $fine) {
            $loan = $this->loanRepository->findById($fine->loanId);
            if ($loan === null || $loan->userId !== $user->id) {
                continue;
            }
            $copy = $this->copyRepository->findById($loan->copyId);
            $book = $copy !== null ? $this->bookRepository->findById($copy->bookId) : null;
            $paid = $this->paymentRepository->totalPaidForFine($fine->id);
            $remaining = max(0, $fine->amountInKurus - $paid);
            $totalRemainingInKurus += $remaining;

            $rows[] = [
                'fine' => $fine,
                'loan' => $loan,
                'copy' => $copy,
                'book' => $book,
                'paidInKurus' => $paid,
                'remainingInKurus' => $remaining,
                'payments' => $this->paymentRepository->findByFineId($fine->id),
            ];
        }

        $this->render('my-fines', [
            'pageTitle' => 'Gecikme Cezalarım ve Ödemelerim',
            'rows' => $rows,
            'totalRemainingInKurus' => $totalRemainingInKurus,
        ]);
    }

    public function manage(): void
    {
        $this->requireRoles(['librarian', 'admin']);
        $rows = [];

        foreach ($this->fineRepository->findAll() as $fine) {
            $loan = $this->loanRepository->findById($fine->loanId);
            if ($loan === null) {
                continue;
            }
            $member = $this->userRepository->findById($loan->userId);
            $copy = $this->copyRepository->findById($loan->copyId);
            $book = $copy !== null ? $this->bookRepository->findById($copy->bookId) : null;
            $paid = $this->paymentRepository->totalPaidForFine($fine->id);
            $remaining = max(0, $fine->amountInKurus - $paid);

            $rows[] = [
                'fine' => $fine,
                'loan' => $loan,
                'member' => $member,
                'copy' => $copy,
                'book' => $book,
                'paidInKurus' => $paid,
                'remainingInKurus' => $remaining,
            ];
        }

        $this->render('librarian-fines', [
            'pageTitle' => 'Ceza ve Ödeme Kaydı Yönetimi',
            'rows' => $rows,
        ]);
    }

    public function pay(): void
    {
        $actor = $this->requireRoles(['librarian', 'admin']);
        $fineId = (int) ($_POST['fine_id'] ?? 0);
        $amountTl = (float) str_replace(',', '.', (string) ($_POST['amount_tl'] ?? '0'));
        $amountInKurus = (int) round($amountTl * 100);

        try {
            $this->fineService->recordPayment($actor->id, $fineId, $amountInKurus);
            $this->redirect(
                'librarian/fines',
                'Ceza ödemesi (' . number_format($amountInKurus / 100, 2, ',', '.') . ' TL) başarıyla kaydedildi.'
            );
        } catch (DomainException $e) {
            $this->redirect('librarian/fines', null, $e->getMessage());
        }
    }
}