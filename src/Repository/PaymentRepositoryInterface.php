<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Payment;

interface PaymentRepositoryInterface
{
    /** @return Payment[] */
    public function findByFineId(int $fineId): array;
    /** @return Payment[] */
    public function findAll(): array;
    public function totalPaidForFine(int $fineId): int;
    public function save(Payment $payment): void;
}