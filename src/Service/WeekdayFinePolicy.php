<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

final class WeekdayFinePolicy implements FinePolicyInterface
{
    public function calculate(
        DateTimeImmutable $dueAt,
        DateTimeImmutable $returnedAt,
        int $dailyRateInKurus,
        int $maximumFineInKurus
    ): int {
        $current = $dueAt->setTime(0, 0, 0);
        $end = $returnedAt->setTime(0, 0, 0);

        if ($end <= $current) {
            return 0;
        }

        $weekdays = 0;
        while ($current < $end) {
            $current = $current->modify('+1 day');
            $dayOfWeek = (int) $current->format('N'); // 1 (Pzt) .. 7 (Paz)
            if ($dayOfWeek <= 5) {
                $weekdays++;
            }
        }

        if ($weekdays <= 0) {
            return 0;
        }

        $fine = $weekdays * $dailyRateInKurus;
        return min($fine, $maximumFineInKurus);
    }
}