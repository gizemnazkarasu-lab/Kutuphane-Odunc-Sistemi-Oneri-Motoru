<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

final class CalendarDayFinePolicy implements FinePolicyInterface
{
    public function calculate(
        DateTimeImmutable $dueAt,
        DateTimeImmutable $returnedAt,
        int $dailyRateInKurus,
        int $maximumFineInKurus
    ): int {
        $dueDate = $dueAt->setTime(0, 0, 0);
        $returnDate = $returnedAt->setTime(0, 0, 0);

        if ($returnDate <= $dueDate) {
            return 0;
        }

        $daysLate = (int) $dueDate->diff($returnDate)->days;
        if ($daysLate <= 0) {
            return 0;
        }

        $fine = $daysLate * $dailyRateInKurus;
        return min($fine, $maximumFineInKurus);
    }
}