<?php

declare(strict_types=1);

namespace App\Model;

final class Copy
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LOANED = 'loaned';
    public const STATUS_ALLOCATED = 'allocated';

    public function __construct(
        public int $id,
        public int $bookId,
        public string $inventoryCode,
        public string $status = self::STATUS_AVAILABLE
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            bookId: (int) $data['bookId'],
            inventoryCode: (string) $data['inventoryCode'],
            status: (string) ($data['status'] ?? self::STATUS_AVAILABLE)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'bookId' => $this->bookId,
            'inventoryCode' => $this->inventoryCode,
            'status' => $this->status,
        ];
    }
}
