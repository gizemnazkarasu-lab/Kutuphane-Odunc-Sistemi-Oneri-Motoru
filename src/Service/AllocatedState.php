<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;
use DomainException;

final class AllocatedState implements ReservationStateInterface
{
    public function allocate(Reservation $reservation): void
    {
        throw new DomainException('Bu rezervasyona zaten bir kopya ayrılmış.');
    }

    public function complete(Reservation $reservation): void
    {
        $reservation->status = Reservation::STATUS_COMPLETED;
    }

    public function expire(Reservation $reservation): void
    {
        $reservation->status = Reservation::STATUS_EXPIRED;
    }

    public function cancel(Reservation $reservation): void
    {
        $reservation->status = Reservation::STATUS_CANCELLED;
    }
}