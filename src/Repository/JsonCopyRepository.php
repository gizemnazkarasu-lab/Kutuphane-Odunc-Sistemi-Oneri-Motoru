<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Copy;

final class JsonCopyRepository implements CopyRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Copy
    {
        foreach ($this->storage->read('copies') as $row) {
            if ((int) $row['id'] === $id) {
                return Copy::fromArray($row);
            }
        }
        return null;
    }

    public function findByInventoryCode(string $inventoryCode): ?Copy
    {
        $needle = mb_strtolower(trim($inventoryCode));
        foreach ($this->storage->read('copies') as $row) {
            if (mb_strtolower(trim((string) $row['inventoryCode'])) === $needle) {
                return Copy::fromArray($row);
            }
        }
        return null;
    }

    public function findByBookId(int $bookId): array
    {
        $copies = [];
        foreach ($this->storage->read('copies') as $row) {
            if ((int) $row['bookId'] === $bookId) {
                $copies[] = Copy::fromArray($row);
            }
        }
        return $copies;
    }

    public function findAvailableByBookId(int $bookId): array
    {
        $copies = [];
        foreach ($this->storage->read('copies') as $row) {
            if ((int) $row['bookId'] === $bookId && (string) $row['status'] === Copy::STATUS_AVAILABLE) {
                $copies[] = Copy::fromArray($row);
            }
        }
        return $copies;
    }

    public function findAll(): array
    {
        return array_map(static fn(array $r): Copy => Copy::fromArray($r), $this->storage->read('copies'));
    }

    public function save(Copy $copy): void
    {
        $records = $this->storage->read('copies');
        if ($copy->id <= 0) {
            $copy->id = $this->storage->nextId($records);
            $records[] = $copy->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $copy->id) {
                    $records[$i] = $copy->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $copy->toArray();
            }
        }
        $this->storage->write('copies', $records);
    }

    public function deleteByBookId(int $bookId): void
    {
        $records = $this->storage->read('copies');
        $filtered = array_filter($records, static fn(array $r): bool => (int) $r['bookId'] !== $bookId);
        $this->storage->write('copies', array_values($filtered));
    }
}