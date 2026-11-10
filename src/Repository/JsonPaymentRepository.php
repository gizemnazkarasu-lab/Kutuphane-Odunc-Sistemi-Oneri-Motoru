<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Payment;

final class JsonPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findByFineId(int $fineId): array
    {
        $payments = [];
        foreach ($this->storage->read('payments') as $row) {
            if ((int) $row['fineId'] === $fineId) {
                $payments[] = Payment::fromArray($row);
            }
        }
        return $payments;
    }

    public function findAll(): array
    {
        $payments = array_map(static fn(array $r): Payment => Payment::fromArray($r), $this->storage->read('payments'));
        usort($payments, static fn(Payment $a, Payment $b): int => $b->id <=> $a->id);
        return $payments;
    }

    public function totalPaidForFine(int $fineId): int
    {
        $total = 0;
        foreach ($this->storage->read('payments') as $row) {
            if ((int) $row['fineId'] === $fineId) {
                $total += (int) $row['amountInKurus'];
            }
        }
        return $total;
    }

    public function save(Payment $payment): void
    {
        $records = $this->storage->read('payments');
        if ($payment->id <= 0) {
            $payment->id = $this->storage->nextId($records);
            $records[] = $payment->toArray();
        } else {
            $updated = false;
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $payment->id) {
                    $records[$i] = $payment->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $payment->toArray();
            }
        }
        $this->storage->write('payments', $records);
    }
}