<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;

abstract class BaseController
{
    /** @var User[] */
    public static array $allUsersForBar = [];

    public function __construct(
        protected string $viewsDir,
        protected ?User $currentUser = null,
        protected int $unreadNotificationCount = 0
    ) {}

    protected function setActiveUserSession(?int $userId): void
    {
        if ($userId !== null && $userId > 0) {
            unset($_SESSION['logged_out']);
            $_SESSION['user_id'] = $userId;
            @setcookie('kutuphane_uid', (string) $userId, [
                'expires' => time() + 86400 * 7,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            unset($_SESSION['user_id']);
            $_SESSION['logged_out'] = true;
            @setcookie('kutuphane_uid', '0', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $viewName, array $data = []): void
    {
        $currentUser = $this->currentUser;
        $unreadNotificationCount = $this->unreadNotificationCount;
        $allUsersForBar = self::$allUsersForBar;
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        extract($data, EXTR_SKIP);

        $viewFile = rtrim($this->viewsDir, '/') . '/' . $viewName . '.php';
        $layoutFile = rtrim($this->viewsDir, '/') . '/layout.php';

        ob_start();
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<div class="alert alert-error">Görünüm dosyası bulunamadı: ' . htmlspecialchars($viewName) . '</div>';
        }
        $content = (string) ob_get_clean();

        require $layoutFile;
    }

    protected function redirect(string $route, ?string $success = null, ?string $error = null): void
    {
        if ($success !== null) {
            $_SESSION['flash_success'] = $success;
        }
        if ($error !== null) {
            $_SESSION['flash_error'] = $error;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Location: ?r=' . ltrim($route, '/'));
        exit;
    }

    protected function requireLogin(): User
    {
        if ($this->currentUser === null) {
            $this->redirect('login', null, 'Bu sayfaya erişmek için lütfen giriş yapınız (NFR-01).');
        }
        return $this->currentUser;
    }

    /**
     * @param string[] $roles
     */
    protected function requireRoles(array $roles): User
    {
        $user = $this->requireLogin();
        if (!in_array($user->role, $roles, true)) {
            $this->redirect('catalog', null, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
        }
        return $user;
    }
}