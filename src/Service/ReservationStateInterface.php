<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Reservation;

interface ReservationStateInterface
{
    public function allocate(Reservation $reservation): void;
    public function complete(Reservation $reservation): void;
    public function expire(Reservation $reservation): void;
    public function cancel(Reservation $reservation): void;
}