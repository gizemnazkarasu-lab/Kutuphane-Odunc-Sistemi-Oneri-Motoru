<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Copy;

interface CopyRepositoryInterface
{
    public function findById(int $id): ?Copy;
    public function findByInventoryCode(string $inventoryCode): ?Copy;
    /** @return Copy[] */
    public function findByBookId(int $bookId): array;
    /** @return Copy[] */
    public function findAvailableByBookId(int $bookId): array;
    /** @return Copy[] */
    public function findAll(): array;
    public function save(Copy $copy): void;
    public function deleteByBookId(int $bookId): void;
}