<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use DomainException;

final class AuthController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private AuthService $authService,
        private UserRepositoryInterface $userRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function showLogin(): void
    {
        $demoUsers = $this->userRepository->findAll();
        $this->render('login', [
            'pageTitle' => 'Sistem Girişi',
            'demoUsers' => $demoUsers,
        ]);
    }

    public function login(): void
    {
        try {
            $credential = (string) ($_POST['credential'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $user = $this->authService->login($credential, $password);
            $this->setActiveUserSession($user->id);

            $target = match ($user->role) {
                'librarian' => 'librarian/loans',
                'admin' => 'admin',
                default => 'catalog',
            };
            $this->redirect($target, "Hoş geldiniz, {$user->name} ({$user->memberNumber}). Giriş başarılı.");
        } catch (DomainException $e) {
            $this->redirect('login', null, $e->getMessage());
        }
    }

    public function quickSwitch(): void
    {
        $userId = (int) ($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
        $user = $this->userRepository->findById($userId);
        if ($user === null || !$user->active) {
            $this->redirect('login', null, 'Seçilen kullanıcı aktif değil veya bulunamadı.');
        }

        $this->setActiveUserSession($user->id);
        $target = (string) ($_POST['redirect'] ?? $_GET['redirect'] ?? '');
        if ($target === '') {
            $target = match ($user->role) {
                'librarian' => 'librarian/loans',
                'admin' => 'admin',
                default => 'catalog',
            };
        }
        $this->redirect($target, "Aktif hesap değiştirildi: {$user->name} ({$user->memberNumber})");
    }

    public function showRegister(): void
    {
        $this->render('register', [
            'pageTitle' => 'Yeni Üye Kaydı',
        ]);
    }

    public function register(): void
    {
        try {
            $name = (string) ($_POST['name'] ?? '');
            $email = (string) ($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $user = $this->authService->register($name, $email, $password);

            $this->setActiveUserSession($user->id);
            $this->redirect('catalog', "Kaydınız tamamlandı, {$user->name}. Kendi hesabınızla giriş yapıldı (Üye No: {$user->memberNumber}).");
        } catch (DomainException $e) {
            $this->redirect('register', null, $e->getMessage());
        }
    }

    public function logout(): void
    {
        $this->setActiveUserSession(null);
        $this->redirect('login', 'Oturumunuz güvenli bir şekilde sonlandırıldı.');
    }
}