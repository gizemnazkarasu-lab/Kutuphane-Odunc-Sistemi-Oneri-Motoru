<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Author;

interface AuthorRepositoryInterface
{
    public function findById(int $id): ?Author;
    /**
     * @param int[] $ids
     * @return Author[]
     */
    public function findByIds(array $ids): array;
    public function findByName(string $name): ?Author;
    /** @return Author[] */
    public function findAll(): array;
    public function save(Author $author): Author;
}