<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\LoanRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\ReportService;

final class ReportController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private ReportService $reportService,
        private UserRepositoryInterface $userRepository,
        private LoanRepositoryInterface $loanRepository,
        private ReservationRepositoryInterface $reservationRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function index(): void
    {
        $this->requireRoles(['librarian', 'admin']);
        $overdueLoans = $this->reportService->getOverdueLoans();
        $mostBorrowed = $this->reportService->getMostBorrowedBooks();

        $memberSearch = trim((string) ($_GET['member_q'] ?? ''));
        $members = [];
        foreach ($this->userRepository->findAll() as $u) {
            if ($memberSearch !== '') {
                $haystack = mb_strtolower($u->name . ' ' . $u->email . ' ' . $u->memberNumber);
                if (!str_contains($haystack, mb_strtolower($memberSearch))) {
                    continue;
                }
            }
            $members[] = [
                'user' => $u,
                'activeLoans' => $this->loanRepository->countActiveByUser($u->id),
                'totalLoans' => count($this->loanRepository->findByUser($u->id)),
                'reservations' => count($this->reservationRepository->findByUser($u->id)),
            ];
        }

        $this->render('librarian-reports', [
            'pageTitle' => 'Okuma İstatistikleri, Geciken Kitaplar ve Üye Kayıtları',
            'overdueLoans' => $overdueLoans,
            'mostBorrowed' => $mostBorrowed,
            'members' => $members,
            'memberSearch' => $memberSearch,
        ]);
    }
}