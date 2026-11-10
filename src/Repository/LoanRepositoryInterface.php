<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Loan;

interface LoanRepositoryInterface
{
    public function findById(int $id): ?Loan;
    public function findActiveByCopyId(int $copyId): ?Loan;
    /** @return Loan[] */
    public function findByUser(int $userId): array;
    /** @return Loan[] */
    public function findAll(): array;
    public function countActiveByUser(int $userId): int;
    public function save(Loan $loan): void;
    /** @return array<int, int> */
    public function findCoBorrowCounts(int $bookId): array;
}