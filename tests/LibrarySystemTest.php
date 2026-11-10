<?php

declare(strict_types=1);

namespace Tests;

use App\Repository\JsonAuthorRepository;
use App\Repository\JsonBookRepository;
use App\Repository\JsonCategoryRepository;
use App\Repository\JsonCopyRepository;
use App\Repository\JsonFineRepository;
use App\Repository\JsonLoanRepository;
use App\Repository\JsonNotificationRepository;
use App\Repository\JsonPaymentRepository;
use App\Repository\JsonReservationRepository;
use App\Repository\JsonStorage;
use App\Repository\JsonUserRepository;
use App\Service\CalendarDayFinePolicy;
use App\Service\EventDispatcher;
use App\Service\FineService;
use App\Service\LoanService;
use App\Service\NotificationService;
use App\Service\RecommendationService;
use App\Service\ReservationNotificationListener;
use App\Service\ReservationService;
use App\Service\ReservationStateFactory;
use App\Service\WeekdayFinePolicy;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;

final class LibrarySystemTest extends TestCase
{
    private string $tempDir;
    private JsonStorage $storage;
    private JsonCopyRepository $copyRepo;
    private JsonLoanRepository $loanRepo;
    private JsonReservationRepository $reservationRepo;
    private JsonNotificationRepository $notificationRepo;
    private LoanService $loanService;
    private ReservationService $reservationService;
    private RecommendationService $recommendationService;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/kutuphane_test_' . uniqid();
        $this->storage = new JsonStorage($this->tempDir);
        $this->storage->seedDefaults(true);

        $userRepo = new JsonUserRepository($this->storage);
        $authorRepo = new JsonAuthorRepository($this->storage);
        $bookRepo = new JsonBookRepository($this->storage);
        $this->copyRepo = new JsonCopyRepository($this->storage);
        $this->loanRepo = new JsonLoanRepository($this->storage);
        $this->reservationRepo = new JsonReservationRepository($this->storage);
        $fineRepo = new JsonFineRepository($this->storage);
        $paymentRepo = new JsonPaymentRepository($this->storage);
        $this->notificationRepo = new JsonNotificationRepository($this->storage);

        $notificationService = new NotificationService($this->notificationRepo, $this->copyRepo, $bookRepo);
        $dispatcher = new EventDispatcher();
        $dispatcher->subscribe(new ReservationNotificationListener($notificationService));

        $fineService = new FineService(new CalendarDayFinePolicy(), 500, 10000, $fineRepo, $paymentRepo);

        $this->reservationService = new ReservationService(
            $this->reservationRepo,
            $this->copyRepo,
            $bookRepo,
            new ReservationStateFactory(),
            $this->loanRepo,
            $dispatcher,
            3
        );

        $this->loanService = new LoanService(
            $this->loanRepo,
            $this->copyRepo,
            $this->reservationRepo,
            $this->reservationService,
            $fineService,
            $fineRepo,
            $dispatcher,
            $userRepo
        );

        $this->recommendationService = new RecommendationService($this->loanRepo, $bookRepo, $authorRepo);
    }

    public function testReturnCopyAllocatesToFirstWaitingMemberForThreeDaysAndCreatesNotification(): void
    {
        // Kitap 2 (Sefiller, Kopya 3 / K-003) şu an Üye 1'de ödünçte (Loan #10).
        // Sefiller için sırada 1. bekleyen: Üye 2 (Reservation #1), 2. bekleyen: Üye 3 (Reservation #2).
        $returnTime = new DateTimeImmutable('2026-11-10 14:00:00');
        $result = $this->loanService->returnCopy(5, 10, $returnTime);

        $this->assertNotNull($result['allocationEvent']);
        $this->assertSame(2, $result['allocationEvent']->userId);
        $this->assertSame('2026-11-13 14:00:00', $result['allocationEvent']->expiresAt->format('Y-m-d H:i:s'));

        // Kopya durumu 'allocated' olmalı ve başka bir üye (Üye 4) ödünç alamamalı
        $copy = $this->copyRepo->findById(3);
        $this->assertSame('allocated', $copy?->status);

        // Observer ile Üye 2'ye sistem içi bildirim düşmüş olmalı
        $notifications = $this->notificationRepo->findByUser(2);
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('Sefiller', $notifications[0]->message);

        // 3 gün içinde alınmazsa (4. günde) hak sıradaki Üye 3'e geçmeli
        $afterFourDays = $returnTime->modify('+4 days');
        $events = $this->reservationService->expireAllocations($afterFourDays);
        $this->assertCount(1, $events);
        $this->assertSame(3, $events[0]->userId);
    }

    public function testFineStrategiesCalculateCalendarAndWeekdayFinesCorrectly(): void
    {
        $calendarPolicy = new CalendarDayFinePolicy();
        $weekdayPolicy = new WeekdayFinePolicy();

        // Cuma teslim tarihi, Pazartesi iade (3 takvim günü, 1 hafta içi günü)
        $dueAt = new DateTimeImmutable('2026-11-06 10:00:00'); // Cuma
        $returnedAt = new DateTimeImmutable('2026-11-09 10:00:00'); // Pazartesi

        $this->assertSame(1500, $calendarPolicy->calculate($dueAt, $returnedAt, 500, 10000));
        $this->assertSame(500, $weekdayPolicy->calculate($dueAt, $returnedAt, 500, 10000));
        // Üst sınır kontrolü
        $this->assertSame(1000, $calendarPolicy->calculate($dueAt, $returnedAt, 500, 1000));
    }

    public function testCoBorrowRecommendationOrdersBySharedUniqueReaders(): void
    {
        // Kitap 1 (Kürk Mantolu Madonna) okuyanlar:
        // İçimizdeki Şeytan (Kitap 3): 3 ortak okur
        // Kuyucaklı Yusuf (Kitap 4): 2 ortak okur
        $recs = $this->recommendationService->recommendDetailedForBook(1, 5);
        $this->assertNotEmpty($recs);
        $this->assertSame(3, $recs[0]['book']->id);
        $this->assertSame(3, $recs[0]['sharedReaderCount']);
        $this->assertSame(4, $recs[1]['book']->id);
        $this->assertSame(2, $recs[1]['sharedReaderCount']);
    }

    public function testMaxFiveActiveLoansRuleIsEnforced(): void
    {
        // Üye 4'e 5 kitap ödünç verelim, 6.'da hata fırlatmalı
        $availableCopyIds = [1, 2, 4, 5, 7, 9];
        for ($i = 0; $i < 5; $i++) {
            $this->loanService->borrow(5, 4, $availableCopyIds[$i]);
        }

        $this->expectException(DomainException::class);
        $this->loanService->borrow(5, 4, $availableCopyIds[5]);
    }
}