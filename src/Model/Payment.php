<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

final class Payment
{
    public function __construct(
        public int $id,
        public int $fineId,
        public int $amountInKurus,
        public DateTimeImmutable $paidAt,
        public int $recordedBy
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            fineId: (int) $data['fineId'],
            amountInKurus: (int) $data['amountInKurus'],
            paidAt: new DateTimeImmutable((string) $data['paidAt']),
            recordedBy: (int) $data['recordedBy']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fineId' => $this->fineId,
            'amountInKurus' => $this->amountInKurus,
            'paidAt' => $this->paidAt->format('Y-m-d H:i:s'),
            'recordedBy' => $this->recordedBy,
        ];
    }
}
