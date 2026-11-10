<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;
use DomainException;

final class ExpiredState implements ReservationStateInterface
{
    public function allocate(Reservation $reservation): void
    {
        throw new DomainException('Süresi dolmuş rezervasyona kopya ayrılamaz.');
    }

    public function complete(Reservation $reservation): void
    {
        throw new DomainException('Süresi dolmuş rezervasyon teslim alınamaz.');
    }

    public function expire(Reservation $reservation): void
    {
        throw new DomainException('Rezervasyonun süresi zaten dolmuş.');
    }

    public function cancel(Reservation $reservation): void
    {
        throw new DomainException('Süresi dolmuş rezervasyon iptal edilemez.');
    }
}