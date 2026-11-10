<?php

declare(strict_types=1);

namespace App\Service;

interface EventListenerInterface
{
    public function handle(ReservationAllocatedEvent $event): void;
}