<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Loan;

final class JsonLoanRepository implements LoanRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Loan
    {
        foreach ($this->storage->read('loans') as $row) {
            if ((int) $row['id'] === $id) {
                return Loan::fromArray($row);
            }
        }
        return null;
    }

    public function findActiveByCopyId(int $copyId): ?Loan
    {
        foreach ($this->storage->read('loans') as $row) {
            if ((int) $row['copyId'] === $copyId && empty($row['returnedAt'])) {
                return Loan::fromArray($row);
            }
        }
        return null;
    }

    public function findByUser(int $userId): array
    {
        $loans = [];
        foreach ($this->storage->read('loans') as $row) {
            if ((int) $row['userId'] === $userId) {
                $loans[] = Loan::fromArray($row);
            }
        }
        usort($loans, static fn(Loan $a, Loan $b): int => $b->id <=> $a->id);
        return $loans;
    }

    public function findAll(): array
    {
        $loans = array_map(static fn(array $r): Loan => Loan::fromArray($r), $this->storage->read('loans'));
        usort($loans, static fn(Loan $a, Loan $b): int => $b->id <=> $a->id);
        return $loans;
    }

    public function countActiveByUser(int $userId): int
    {
        $count = 0;
        foreach ($this->storage->read('loans') as $row) {
            if ((int) $row['userId'] === $userId && empty($row['returnedAt'])) {
                $count++;
            }
        }
        return $count;
    }

    public function save(Loan $loan): void
    {
        $records = $this->storage->read('loans');
        if ($loan->id <= 0) {
            $loan->id = $this->storage->nextId($records);
            $records[] = $loan->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $loan->id) {
                    $records[$i] = $loan->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $loan->toArray();
            }
        }
        $this->storage->write('loans', $records);
    }

    public function findCoBorrowCounts(int $bookId): array
    {
        $copyToBook = [];
        foreach ($this->storage->read('copies') as $copyRow) {
            $copyToBook[(int) $copyRow['id']] = (int) $copyRow['bookId'];
        }

        $userBooks = [];
        foreach ($this->storage->read('loans') as $loanRow) {
            $userId = (int) $loanRow['userId'];
            $copyId = (int) $loanRow['copyId'];
            if (!isset($copyToBook[$copyId])) {
                continue;
            }
            $bId = $copyToBook[$copyId];
            $userBooks[$userId][$bId] = true;
        }

        $coBorrowCounts = [];
        foreach ($userBooks as $booksMap) {
            if (!isset($booksMap[$bookId])) {
                continue;
            }
            foreach (array_keys($booksMap) as $otherBookId) {
                if ($otherBookId === $bookId) {
                    continue;
                }
                $coBorrowCounts[$otherBookId] = ($coBorrowCounts[$otherBookId] ?? 0) + 1;
            }
        }

        uksort($coBorrowCounts, static function (int $a, int $b) use ($coBorrowCounts): int {
            $cmp = $coBorrowCounts[$b] <=> $coBorrowCounts[$a];
            return $cmp !== 0 ? $cmp : ($a <=> $b);
        });

        return $coBorrowCounts;
    }
}