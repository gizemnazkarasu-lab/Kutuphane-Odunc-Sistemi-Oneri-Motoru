<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Notification;
use App\Repository\BookRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\NotificationRepositoryInterface;
use DateTimeImmutable;

final class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private ?CopyRepositoryInterface $copyRepository = null,
        private ?BookRepositoryInterface $bookRepository = null
    ) {}

    public function notifyAllocation(ReservationAllocatedEvent $event): void
    {
        $eventKey = $event->getEventKey();

        // Aynı ayırma olayı için ikinci bir bildirim oluşturma (idempotency)
        if ($this->notificationRepository->findByEventKey($eventKey) !== null) {
            return;
        }

        $bookTitle = $event->bookTitle;
        $inventoryCode = $event->inventoryCode;

        if (($bookTitle === '' || $inventoryCode === '') && $this->copyRepository !== null) {
            $copy = $this->copyRepository->findById($event->copyId);
            if ($copy !== null) {
                $inventoryCode = $copy->inventoryCode;
                if ($this->bookRepository !== null) {
                    $book = $this->bookRepository->findById($copy->bookId);
                    if ($book !== null) {
                        $bookTitle = $book->title;
                    }
                }
            }
        }

        $label = $bookTitle !== '' ? "\"{$bookTitle}\" ({$inventoryCode})" : "Kopya #{$event->copyId}";
        $deadline = $event->expiresAt->format('d.m.Y H:i');

        $message = sprintf(
            'Rezervasyon sıranız geldi! %s kitabının kopyası 3 gün süreyle (%s tarihine kadar) sizin için ayrıldı. Lütfen süresi içinde kütüphaneden teslim alınız.',
            $label,
            $deadline
        );

        $notification = new Notification(
            id: 0,
            userId: $event->userId,
            reservationId: $event->reservationId,
            eventKey: $eventKey,
            message: $message,
            createdAt: new DateTimeImmutable(),
            readAt: null
        );

        $this->notificationRepository->save($notification);
    }

    /**
     * @return Notification[]
     */
    public function listForUser(int $userId): array
    {
        return $this->notificationRepository->findByUser($userId);
    }

    public function markAsRead(int $notificationId, int $userId): void
    {
        $this->notificationRepository->markAsRead($notificationId, $userId);
    }
}