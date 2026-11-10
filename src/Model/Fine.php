<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

final class Fine
{
    public function __construct(
        public int $id,
        public int $loanId,
        public int $amountInKurus,
        public DateTimeImmutable $calculatedAt
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            loanId: (int) $data['loanId'],
            amountInKurus: (int) $data['amountInKurus'],
            calculatedAt: new DateTimeImmutable((string) $data['calculatedAt'])
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'loanId' => $this->loanId,
            'amountInKurus' => $this->amountInKurus,
            'calculatedAt' => $this->calculatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
