<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

final class ReservationAllocatedEvent
{
    public function __construct(
        public int $reservationId,
        public int $userId,
        public int $copyId,
        public DateTimeImmutable $expiresAt,
        public string $bookTitle = '',
        public string $inventoryCode = ''
    ) {}

    public function getEventKey(): string
    {
        return sprintf(
            'res_alloc_%d_copy_%d_%s',
            $this->reservationId,
            $this->copyId,
            $this->expiresAt->format('YmdHis')
        );
    }
}