<?php

declare(strict_types=1);

namespace App\Service;

final class ReservationNotificationListener implements EventListenerInterface
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(ReservationAllocatedEvent $event): void
    {
        $this->notificationService->notifyAllocation($event);
    }
}