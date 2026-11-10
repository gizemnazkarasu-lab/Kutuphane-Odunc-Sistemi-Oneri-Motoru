<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;
use DomainException;

final class CancelledState implements ReservationStateInterface
{
    public function allocate(Reservation $reservation): void
    {
        throw new DomainException('İptal edilmiş rezervasyona kopya ayrılamaz.');
    }

    public function complete(Reservation $reservation): void
    {
        throw new DomainException('İptal edilmiş rezervasyon teslim alınamaz.');
    }

    public function expire(Reservation $reservation): void
    {
        throw new DomainException('İptal edilmiş rezervasyonun süresi dolamaz.');
    }

    public function cancel(Reservation $reservation): void
    {
        throw new DomainException('Rezervasyon zaten iptal edilmiş.');
    }
}