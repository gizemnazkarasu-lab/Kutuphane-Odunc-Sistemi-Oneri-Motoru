<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

final class Reservation
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        public int $id,
        public int $userId,
        public int $bookId,
        public ?int $allocatedCopyId,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $allocatedAt = null,
        public ?DateTimeImmutable $expiresAt = null,
        public string $status = self::STATUS_WAITING
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            userId: (int) $data['userId'],
            bookId: (int) $data['bookId'],
            allocatedCopyId: isset($data['allocatedCopyId']) && $data['allocatedCopyId'] !== null ? (int) $data['allocatedCopyId'] : null,
            createdAt: new DateTimeImmutable((string) $data['createdAt']),
            allocatedAt: !empty($data['allocatedAt']) ? new DateTimeImmutable((string) $data['allocatedAt']) : null,
            expiresAt: !empty($data['expiresAt']) ? new DateTimeImmutable((string) $data['expiresAt']) : null,
            status: (string) ($data['status'] ?? self::STATUS_WAITING)
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
            'bookId' => $this->bookId,
            'allocatedCopyId' => $this->allocatedCopyId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'allocatedAt' => $this->allocatedAt?->format('Y-m-d H:i:s'),
            'expiresAt' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'status' => $this->status,
        ];
    }
}
