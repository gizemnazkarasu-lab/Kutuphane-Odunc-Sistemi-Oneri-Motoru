<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\BookRepositoryInterface;
use App\Service\RecommendationService;

final class RecommendationController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private RecommendationService $recommendationService,
        private BookRepositoryInterface $bookRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function show(): void
    {
        $this->requireLogin();
        $bookId = (int) ($_GET['book_id'] ?? 1);
        header('Location: ?r=books/show&id=' . $bookId);
        exit;
    }
}