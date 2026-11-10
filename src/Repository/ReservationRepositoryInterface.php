<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Reservation;
use DateTimeImmutable;

interface ReservationRepositoryInterface
{
    public function findById(int $id): ?Reservation;
    /** @return Reservation[] */
    public function findByUser(int $userId): array;
    /** @return Reservation[] */
    public function findByBook(int $bookId): array;
    public function findActiveByUserAndBook(int $userId, int $bookId): ?Reservation;
    public function findFirstWaiting(int $bookId): ?Reservation;
    public function findActiveByCopy(int $copyId): ?Reservation;
    /** @return Reservation[] */
    public function findExpiredAllocations(?DateTimeImmutable $now = null): array;
    /** @return Reservation[] */
    public function findAll(): array;
    public function save(Reservation $reservation): void;
}