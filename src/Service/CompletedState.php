<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;
use DomainException;

final class CompletedState implements ReservationStateInterface
{
    public function allocate(Reservation $reservation): void
    {
        throw new DomainException('Tamamlanmış rezervasyon üzerinde işlem yapılamaz.');
    }

    public function complete(Reservation $reservation): void
    {
        throw new DomainException('Rezervasyon zaten tamamlanmış.');
    }

    public function expire(Reservation $reservation): void
    {
        throw new DomainException('Tamamlanmış rezervasyonun süresi dolamaz.');
    }

    public function cancel(Reservation $reservation): void
    {
        throw new DomainException('Tamamlanmış rezervasyon iptal edilemez.');
    }
}