<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\BookRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\LoanRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use DateTimeImmutable;

final class ReportService
{
    public function __construct(
        private LoanRepositoryInterface $loanRepository,
        private BookRepositoryInterface $bookRepository,
        private CopyRepositoryInterface $copyRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * US-07: İade tarihi geçmiş (geciken) aktif ödünçleri listeler.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOverdueLoans(?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable();
        $overdue = [];

        foreach ($this->loanRepository->findAll() as $loan) {
            if ($loan->returnedAt !== null || $loan->dueAt >= $now) {
                continue;
            }

            $copy = $this->copyRepository->findById($loan->copyId);
            $book = $copy !== null ? $this->bookRepository->findById($copy->bookId) : null;
            $user = $this->userRepository->findById($loan->userId);
            $daysLate = (int) $loan->dueAt->setTime(0, 0, 0)->diff($now->setTime(0, 0, 0))->days;

            $overdue[] = [
                'loan' => $loan,
                'copy' => $copy,
                'book' => $book,
                'user' => $user,
                'daysLate' => max(1, $daysLate),
            ];
        }

        return $overdue;
    }

    /**
     * En çok okunan (ödünç alınan) kitaplar istatistiği.
     *
     * @return array<int, array{bookTitle: string, isbn: string, borrowCount: int}>
     */
    public function getMostBorrowedBooks(): array
    {
        $counts = [];
        foreach ($this->loanRepository->findAll() as $loan) {
            $copy = $this->copyRepository->findById($loan->copyId);
            if ($copy === null) {
                continue;
            }
            $counts[$copy->bookId] = ($counts[$copy->bookId] ?? 0) + 1;
        }

        arsort($counts);
        $result = [];
        foreach ($counts as $bookId => $count) {
            $book = $this->bookRepository->findById((int) $bookId);
            if ($book === null) {
                continue;
            }
            $result[] = [
                'bookTitle' => $book->title,
                'isbn' => $book->isbn,
                'borrowCount' => $count,
            ];
        }
        return $result;
    }
}