<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

final class Loan
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $copyId,
        public DateTimeImmutable $borrowedAt,
        public DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt = null,
        public int $renewalCount = 0
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            userId: (int) $data['userId'],
            copyId: (int) $data['copyId'],
            borrowedAt: new DateTimeImmutable((string) $data['borrowedAt']),
            dueAt: new DateTimeImmutable((string) $data['dueAt']),
            returnedAt: !empty($data['returnedAt']) ? new DateTimeImmutable((string) $data['returnedAt']) : null,
            renewalCount: (int) ($data['renewalCount'] ?? 0)
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
            'copyId' => $this->copyId,
            'borrowedAt' => $this->borrowedAt->format('Y-m-d H:i:s'),
            'dueAt' => $this->dueAt->format('Y-m-d H:i:s'),
            'returnedAt' => $this->returnedAt?->format('Y-m-d H:i:s'),
            'renewalCount' => $this->renewalCount,
        ];
    }
}
