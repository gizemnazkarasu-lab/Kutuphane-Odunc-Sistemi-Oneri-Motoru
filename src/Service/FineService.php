<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Fine;
use App\Model\Loan;
use App\Model\Payment;
use App\Repository\FineRepositoryInterface;
use App\Repository\PaymentRepositoryInterface;
use DateTimeImmutable;
use DomainException;

final class FineService
{
    public function __construct(
        private FinePolicyInterface $policy,
        private int $dailyRateInKurus,
        private int $maximumFineInKurus,
        private ?FineRepositoryInterface $fineRepository = null,
        private ?PaymentRepositoryInterface $paymentRepository = null
    ) {}

    public function calculate(Loan $loan, DateTimeImmutable $returnedAt): int
    {
        return $this->policy->calculate(
            $loan->dueAt,
            $returnedAt,
            $this->dailyRateInKurus,
            $this->maximumFineInKurus
        );
    }

    public function recordPayment(int $actorId, int $fineId, int $amountInKurus): Payment
    {
        if ($this->fineRepository === null || $this->paymentRepository === null) {
            throw new DomainException('Ödeme servisi yapılandırılmamış.');
        }

        if ($amountInKurus <= 0) {
            throw new DomainException('Ödeme tutarı sıfırdan büyük olmalıdır.');
        }

        $fine = $this->fineRepository->findById($fineId);
        if ($fine === null) {
            throw new DomainException('Ceza kaydı bulunamadı.');
        }

        $alreadyPaid = $this->paymentRepository->totalPaidForFine($fineId);
        $remaining = $fine->amountInKurus - $alreadyPaid;

        if ($remaining <= 0) {
            throw new DomainException('Bu ceza zaten tamamen ödenmiştir.');
        }

        if ($amountInKurus > $remaining) {
            throw new DomainException('Ödeme tutarı kalan ceza borcunu (' . number_format($remaining / 100, 2, ',', '.') . ' TL) aşamaz.');
        }

        $payment = new Payment(
            id: 0,
            fineId: $fineId,
            amountInKurus: $amountInKurus,
            paidAt: new DateTimeImmutable(),
            recordedBy: $actorId
        );

        $this->paymentRepository->save($payment);
        return $payment;
    }
}