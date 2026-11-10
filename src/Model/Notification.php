<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

final class Notification
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $reservationId,
        public string $eventKey,
        public string $message,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $readAt = null
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            userId: (int) $data['userId'],
            reservationId: (int) $data['reservationId'],
            eventKey: (string) $data['eventKey'],
            message: (string) $data['message'],
            createdAt: new DateTimeImmutable((string) $data['createdAt']),
            readAt: !empty($data['readAt']) ? new DateTimeImmutable((string) $data['readAt']) : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'reservationId' => $this->reservationId,
            'eventKey' => $this->eventKey,
            'message' => $this->message,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'readAt' => $this->readAt?->format('Y-m-d H:i:s'),
        ];
    }
}
