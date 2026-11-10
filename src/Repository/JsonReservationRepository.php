<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Reservation;
use DateTimeImmutable;

final class JsonReservationRepository implements ReservationRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Reservation
    {
        foreach ($this->storage->read('reservations') as $row) {
            if ((int) $row['id'] === $id) {
                return Reservation::fromArray($row);
            }
        }
        return null;
    }

    public function findByUser(int $userId): array
    {
        $items = [];
        foreach ($this->storage->read('reservations') as $row) {
            if ((int) $row['userId'] === $userId) {
                $items[] = Reservation::fromArray($row);
            }
        }
        usort($items, static fn(Reservation $a, Reservation $b): int => $b->id <=> $a->id);
        return $items;
    }

    public function findByBook(int $bookId): array
    {
        $items = [];
        foreach ($this->storage->read('reservations') as $row) {
            if ((int) $row['bookId'] === $bookId) {
                $items[] = Reservation::fromArray($row);
            }
        }
        usort($items, static function (Reservation $a, Reservation $b): int {
            $cmp = $a->createdAt <=> $b->createdAt;
            return $cmp !== 0 ? $cmp : ($a->id <=> $b->id);
        });
        return $items;
    }

    public function findActiveByUserAndBook(int $userId, int $bookId): ?Reservation
    {
        foreach ($this->storage->read('reservations') as $row) {
            $status = (string) $row['status'];
            if (
                (int) $row['userId'] === $userId
                && (int) $row['bookId'] === $bookId
                && in_array($status, [Reservation::STATUS_WAITING, Reservation::STATUS_ALLOCATED], true)
            ) {
                return Reservation::fromArray($row);
            }
        }
        return null;
    }

    public function findFirstWaiting(int $bookId): ?Reservation
    {
        $waiting = [];
        foreach ($this->storage->read('reservations') as $row) {
            if ((int) $row['bookId'] === $bookId && (string) $row['status'] === Reservation::STATUS_WAITING) {
                $waiting[] = Reservation::fromArray($row);
            }
        }
        if ($waiting === []) {
            return null;
        }
        usort($waiting, static function (Reservation $a, Reservation $b): int {
            $cmp = $a->createdAt <=> $b->createdAt;
            return $cmp !== 0 ? $cmp : ($a->id <=> $b->id);
        });
        return $waiting[0];
    }

    public function findActiveByCopy(int $copyId): ?Reservation
    {
        foreach ($this->storage->read('reservations') as $row) {
            if (
                isset($row['allocatedCopyId'])
                && (int) $row['allocatedCopyId'] === $copyId
                && (string) $row['status'] === Reservation::STATUS_ALLOCATED
            ) {
                return Reservation::fromArray($row);
            }
        }
        return null;
    }

    public function findExpiredAllocations(?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable();
        $expired = [];
        foreach ($this->storage->read('reservations') as $row) {
            if ((string) $row['status'] !== Reservation::STATUS_ALLOCATED || empty($row['expiresAt'])) {
                continue;
            }
            $reservation = Reservation::fromArray($row);
            if ($reservation->expiresAt !== null && $reservation->expiresAt < $now) {
                $expired[] = $reservation;
            }
        }
        return $expired;
    }

    public function findAll(): array
    {
        $items = array_map(static fn(array $r): Reservation => Reservation::fromArray($r), $this->storage->read('reservations'));
        usort($items, static function (Reservation $a, Reservation $b): int {
            $cmp = $a->createdAt <=> $b->createdAt;
            return $cmp !== 0 ? $cmp : ($a->id <=> $b->id);
        });
        return $items;
    }

    public function save(Reservation $reservation): void
    {
        $records = $this->storage->read('reservations');
        if ($reservation->id <= 0) {
            $reservation->id = $this->storage->nextId($records);
            $records[] = $reservation->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $reservation->id) {
                    $records[$i] = $reservation->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $reservation->toArray();
            }
        }
        $this->storage->write('reservations', $records);
    }
}