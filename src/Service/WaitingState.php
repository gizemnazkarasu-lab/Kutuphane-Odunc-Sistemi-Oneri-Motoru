<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;
use DomainException;

final class WaitingState implements ReservationStateInterface
{
    public function allocate(Reservation $reservation): void
    {
        $reservation->status = Reservation::STATUS_ALLOCATED;
    }

    public function complete(Reservation $reservation): void
    {
        throw new DomainException('Bekleyen rezervasyon doğrudan tamamlanamaz; önce kopya ayrılmalıdır.');
    }

    public function expire(Reservation $reservation): void
    {
        throw new DomainException('Bekleyen rezervasyonun ayırma süresi dolamaz.');
    }

    public function cancel(Reservation $reservation): void
    {
        $reservation->status = Reservation::STATUS_CANCELLED;
    }
}