<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Book;

final class JsonBookRepository implements BookRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Book
    {
        foreach ($this->storage->read('books') as $row) {
            if ((int) $row['id'] === $id) {
                return Book::fromArray($row);
            }
        }
        return null;
    }

    public function findByIsbn(string $isbn): ?Book
    {
        $needle = trim($isbn);
        if ($needle === '') {
            return null;
        }
        foreach ($this->storage->read('books') as $row) {
            if (trim((string) ($row['isbn'] ?? '')) === $needle) {
                return Book::fromArray($row);
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_map(static fn(array $r): Book => Book::fromArray($r), $this->storage->read('books'));
    }

    public function save(Book $book): Book
    {
        $records = $this->storage->read('books');
        if ($book->id <= 0) {
            $book->id = $this->storage->nextId($records);
            $records[] = $book->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $book->id) {
                    $records[$i] = $book->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $book->toArray();
            }
        }
        $this->storage->write('books', $records);
        return $book;
    }

    public function delete(int $id): bool
    {
        $records = $this->storage->read('books');
        $filtered = [];
        $deleted = false;
        foreach ($records as $row) {
            if ((int) $row['id'] === $id) {
                $deleted = true;
                continue;
            }
            $filtered[] = $row;
        }
        if ($deleted) {
            $this->storage->write('books', $filtered);
        }
        return $deleted;
    }
}