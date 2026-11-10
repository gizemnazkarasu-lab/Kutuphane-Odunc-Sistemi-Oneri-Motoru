<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Notification;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;
    /** @return Notification[] */
    public function findByUser(int $userId): array;
    public function findByEventKey(string $eventKey): ?Notification;
    public function markAsRead(int $notificationId, int $userId): void;
}