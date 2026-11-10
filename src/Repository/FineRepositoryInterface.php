<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Fine;

interface FineRepositoryInterface
{
    public function findById(int $id): ?Fine;
    public function findByLoanId(int $loanId): ?Fine;
    /** @return Fine[] */
    public function findAll(): array;
    public function save(Fine $fine): void;
}