<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Service\NotificationService;

final class NotificationController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private NotificationService $notificationService
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $notifications = $this->notificationService->listForUser($user->id);

        $this->render('notifications', [
            'pageTitle' => 'Bildirimlerim',
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(): void
    {
        $user = $this->requireLogin();
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $this->notificationService->markAsRead($notificationId, $user->id);
        $this->redirect('notifications', 'Bildirim okundu olarak işaretlendi.');
    }
}