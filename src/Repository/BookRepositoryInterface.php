<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Book;

interface BookRepositoryInterface
{
    public function findById(int $id): ?Book;
    public function findByIsbn(string $isbn): ?Book;
    /** @return Book[] */
    public function findAll(): array;
    public function save(Book $book): Book;
    public function delete(int $id): bool;
}