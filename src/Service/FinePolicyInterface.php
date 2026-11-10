<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

interface FinePolicyInterface
{
    public function calculate(
        DateTimeImmutable $dueAt,
        DateTimeImmutable $returnedAt,
        int $dailyRateInKurus,
        int $maximumFineInKurus
    ): int;
}