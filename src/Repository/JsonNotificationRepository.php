<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Notification;
use DateTimeImmutable;

final class JsonNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function save(Notification $notification): void
    {
        $records = $this->storage->read('notifications');
        if ($notification->id <= 0) {
            $notification->id = $this->storage->nextId($records);
            $records[] = $notification->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $notification->id) {
                    $records[$i] = $notification->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $notification->toArray();
            }
        }
        $this->storage->write('notifications', $records);
    }

    public function findByUser(int $userId): array
    {
        $items = [];
        foreach ($this->storage->read('notifications') as $row) {
            if ((int) $row['userId'] === $userId) {
                $items[] = Notification::fromArray($row);
            }
        }
        usort($items, static fn(Notification $a, Notification $b): int => $b->id <=> $a->id);
        return $items;
    }

    public function findByEventKey(string $eventKey): ?Notification
    {
        foreach ($this->storage->read('notifications') as $row) {
            if ((string) $row['eventKey'] === $eventKey) {
                return Notification::fromArray($row);
            }
        }
        return null;
    }

    public function markAsRead(int $notificationId, int $userId): void
    {
        $records = $this->storage->read('notifications');
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        foreach ($records as $i => $row) {
            if ((int) $row['id'] === $notificationId && (int) $row['userId'] === $userId) {
                $records[$i]['readAt'] = $now;
                break;
            }
        }
        $this->storage->write('notifications', $records);
    }
}