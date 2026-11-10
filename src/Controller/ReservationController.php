<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\BookRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\ReservationService;
use DateTimeImmutable;
use DomainException;

final class ReservationController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private ReservationService $reservationService,
        private ReservationRepositoryInterface $reservationRepository,
        private BookRepositoryInterface $bookRepository,
        private CopyRepositoryInterface $copyRepository,
        private UserRepositoryInterface $userRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $reservations = $this->reservationService->listForUser($user->id);
        $rows = [];

        foreach ($reservations as $res) {
            $book = $this->bookRepository->findById($res->bookId);
            $copy = $res->allocatedCopyId !== null ? $this->copyRepository->findById($res->allocatedCopyId) : null;
            $position = $this->reservationService->getQueuePosition($res->id);

            $rows[] = [
                'reservation' => $res,
                'book' => $book,
                'copy' => $copy,
                'position' => $position,
            ];
        }

        $this->render('my-reservations', [
            'pageTitle' => 'Rezervasyonlarım',
            'rows' => $rows,
        ]);
    }

    public function queue(): void
    {
        $this->requireRoles(['librarian', 'admin']);
        $selectedBookId = (int) ($_GET['book_id'] ?? 0);
        $books = $this->bookRepository->findAll();

        $allReservations = $selectedBookId > 0
            ? $this->reservationRepository->findByBook($selectedBookId)
            : $this->reservationRepository->findAll();

        $activeQueueRows = [];
        $historyQueueRows = [];
        $rankByBook = [];

        foreach ($allReservations as $res) {
            $book = $this->bookRepository->findById($res->bookId);
            $member = $this->userRepository->findById($res->userId);
            $copy = $res->allocatedCopyId !== null ? $this->copyRepository->findById($res->allocatedCopyId) : null;

            if (in_array($res->status, ['waiting', 'allocated'], true)) {
                $rankByBook[$res->bookId] = ($rankByBook[$res->bookId] ?? 0) + 1;
                $activeQueueRows[] = [
                    'rank' => $rankByBook[$res->bookId],
                    'reservation' => $res,
                    'book' => $book,
                    'member' => $member,
                    'copy' => $copy,
                ];
            } else {
                $historyQueueRows[] = [
                    'reservation' => $res,
                    'book' => $book,
                    'member' => $member,
                    'copy' => $copy,
                ];
            }
        }

        $this->render('librarian-reservations', [
            'pageTitle' => 'Rezervasyon Sırası Yönetimi',
            'books' => $books,
            'selectedBookId' => $selectedBookId,
            'activeQueueRows' => $activeQueueRows,
            'historyQueueRows' => $historyQueueRows,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $returnRoute = (string) ($_POST['return_route'] ?? "books/show&id={$bookId}");

        try {
            $res = $this->reservationService->joinQueue($user->id, $bookId);
            $pos = $this->reservationService->getQueuePosition($res->id);
            $this->redirect(
                $returnRoute,
                "Rezervasyon sırasına eklendiniz! Rezervasyon sıranız: {$pos}. Kitap iade edildiğinde 3 gün süreyle adınıza ayrılacak ve bildirim alacaksınız."
            );
        } catch (DomainException $e) {
            $this->redirect($returnRoute, null, $e->getMessage());
        }
    }

    public function cancel(): void
    {
        $user = $this->requireLogin();
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $targetUserId = (int) ($_POST['user_id'] ?? $user->id);
        $returnRoute = (string) ($_POST['return_route'] ?? 'my-reservations');

        try {
            $this->reservationService->cancel($reservationId, $targetUserId);
            $this->redirect($returnRoute, 'Rezervasyon kaydınız iptal edildi. Varsa ayrılan kopya sıradaki üyeye devredildi.');
        } catch (DomainException $e) {
            $this->redirect($returnRoute, null, $e->getMessage());
        }
    }

    public function expireCheck(): void
    {
        $this->requireRoles(['librarian', 'admin']);
        $simulateDaysForward = (int) ($_POST['simulate_days_forward'] ?? 4);
        $checkTime = (new DateTimeImmutable())->modify("+{$simulateDaysForward} days");

        $events = $this->reservationService->expireAllocations($checkTime);
        $count = count($events);

        if ($count > 0) {
            $this->redirect(
                'librarian/reservations',
                "3 günlük teslim alma süresi dolan ayırmalar düşürüldü ve {$count} adet kopya sıradaki sonraki üyeye 3 günlüğüne ayrılarak bildirim gönderildi!"
            );
        } else {
            $this->redirect(
                'librarian/reservations',
                '3 günlük teslim alma süresi kontrol edildi (varsa süresi dolan ayırmalar güncellendi).'
            );
        }
    }
}