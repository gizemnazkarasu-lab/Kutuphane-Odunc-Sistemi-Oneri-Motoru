<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\JsonStorage;
use App\Repository\UserRepositoryInterface;
use App\Service\AdminService;
use DomainException;

final class AdminController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private AdminService $adminService,
        private UserRepositoryInterface $userRepository,
        private JsonStorage $storage
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function index(): void
    {
        $this->requireRoles(['librarian', 'admin']);
        $users = $this->userRepository->findAll();
        $settings = $this->storage->readSettings();
        $auditLogs = array_reverse($this->storage->read('audit_logs'));

        $backupDir = $this->storage->getStorageDir() . '/backups';
        $backups = [];
        if (is_dir($backupDir)) {
            foreach (scandir($backupDir) ?: [] as $f) {
                if (str_ends_with($f, '.json')) {
                    $backups[] = $f;
                }
            }
            rsort($backups);
        }

        $this->render('admin', [
            'pageTitle' => 'Sistem Yöneticisi Paneli',
            'users' => $users,
            'settings' => $settings,
            'auditLogs' => array_slice($auditLogs, 0, 25),
            'backups' => $backups,
        ]);
    }

    public function updateUser(): void
    {
        $actor = $this->requireRoles(['librarian', 'admin']);
        try {
            $targetId = (int) ($_POST['target_user_id'] ?? 0);
            $role = (string) ($_POST['role'] ?? '');
            $active = isset($_POST['active']) && $_POST['active'] === '1';

            $updated = $this->adminService->updateUserRoleAndStatus($actor->id, $targetId, $role, $active);
            $this->redirect('admin', "Kullanıcı '{$updated->name}' rol ve yetkileri başarıyla güncellendi.");
        } catch (DomainException $e) {
            $this->redirect('admin', null, $e->getMessage());
        }
    }

    public function createUser(): void
    {
        $actor = $this->requireRoles(['admin']);
        try {
            $name = (string) ($_POST['name'] ?? '');
            $email = (string) ($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? 'member');
            $loginAsNew = isset($_POST['login_as_new']) && $_POST['login_as_new'] === '1';

            $created = $this->adminService->createUser($actor->id, $name, $email, $password, $role);
            if ($loginAsNew) {
                $this->setActiveUserSession($created->id);
                $this->redirect('catalog', "Yeni kullanıcı oluşturuldu ve '{$created->name}' ({$created->memberNumber}) hesabıyla giriş yapıldı!");
            }
            $this->redirect('admin', "Yeni kullanıcı hesabı oluşturuldu: {$created->name} ({$created->memberNumber}). Üst çubuktan veya giriş ekranından bu hesaba geçebilirsiniz.");
        } catch (DomainException $e) {
            $this->redirect('admin', null, $e->getMessage());
        }
    }

    public function updateSettings(): void
    {
        $actor = $this->requireRoles(['admin']);
        try {
            $loanDays = (int) ($_POST['loan_duration_days'] ?? 14);
            $dailyFineTl = (float) str_replace(',', '.', (string) ($_POST['daily_fine_tl'] ?? '5'));
            $maxFineTl = (float) str_replace(',', '.', (string) ($_POST['max_fine_tl'] ?? '100'));
            $policy = (string) ($_POST['fine_policy'] ?? 'calendar');

            $this->adminService->updateSettings(
                $actor->id,
                $loanDays,
                (int) round($dailyFineTl * 100),
                (int) round($maxFineTl * 100),
                $policy
            );
            $this->redirect('admin', 'Sistem kuralları (ödünç süresi, gecikme cezası ve hafta sonu politikası) başarıyla güncellendi.');
        } catch (DomainException $e) {
            $this->redirect('admin', null, $e->getMessage());
        }
    }

    public function backup(): void
    {
        $actor = $this->requireRoles(['admin']);
        try {
            $fileName = $this->adminService->createBackup($actor->id);
            $this->redirect('admin', "Veri yedekleme işlemi başarıyla tamamlandı! Yedek dosyası: storage/backups/{$fileName}");
        } catch (DomainException $e) {
            $this->redirect('admin', null, $e->getMessage());
        }
    }

    public function resetDemo(): void
    {
        $this->storage->seedDefaults(true);
        $this->setActiveUserSession(1);
        $this->redirect('catalog', 'Tüm JSON verileri ilk sunum/demo başlangıç durumuna başarıyla sıfırlandı!');
    }
}