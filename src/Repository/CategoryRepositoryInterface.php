<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;
    /** @return Category[] */
    public function findAll(): array;
    public function save(Category $category): Category;
}