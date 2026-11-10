<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;
use InvalidArgumentException;

final class ReservationStateFactory
{
    public function fromStatus(string $status): ReservationStateInterface
    {
        return match ($status) {
            Reservation::STATUS_WAITING => new WaitingState(),
            Reservation::STATUS_ALLOCATED => new AllocatedState(),
            Reservation::STATUS_COMPLETED => new CompletedState(),
            Reservation::STATUS_EXPIRED => new ExpiredState(),
            Reservation::STATUS_CANCELLED => new CancelledState(),
            default => throw new InvalidArgumentException("Geçersiz rezervasyon durumu: {$status}"),
        };
    }
}