<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Fine;

final class JsonFineRepository implements FineRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Fine
    {
        foreach ($this->storage->read('fines') as $row) {
            if ((int) $row['id'] === $id) {
                return Fine::fromArray($row);
            }
        }
        return null;
    }

    public function findByLoanId(int $loanId): ?Fine
    {
        foreach ($this->storage->read('fines') as $row) {
            if ((int) $row['loanId'] === $loanId) {
                return Fine::fromArray($row);
            }
        }
        return null;
    }

    public function findAll(): array
    {
        $fines = array_map(static fn(array $r): Fine => Fine::fromArray($r), $this->storage->read('fines'));
        usort($fines, static fn(Fine $a, Fine $b): int => $b->id <=> $a->id);
        return $fines;
    }

    public function save(Fine $fine): void
    {
        $records = $this->storage->read('fines');
        if ($fine->id <= 0) {
            $fine->id = $this->storage->nextId($records);
            $records[] = $fine->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $fine->id) {
                    $records[$i] = $fine->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $fine->toArray();
            }
        }
        $this->storage->write('fines', $records);
    }
}