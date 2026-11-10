<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\JsonStorage;
use App\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use DomainException;

final class AdminService
{
    private const VALID_ROLES = ['member', 'librarian', 'admin'];

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthorizationService $authorizationService,
        private JsonStorage $storage
    ) {}

    public function updateUserRoleAndStatus(int $actorId, int $targetUserId, string $role, bool $active): User
    {
        $actor = $this->authorizationService->requireRole($actorId, ['librarian', 'admin']);

        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new DomainException('Geçersiz rol tanımlandı. İzin verilen roller: Üye, Kütüphane Görevlisi, Sistem Yöneticisi.');
        }

        $target = $this->userRepository->findById($targetUserId);
        if ($target === null) {
            throw new DomainException('Düzenlenmek istenen kullanıcı kaydı bulunamadı.');
        }

        $target->role = $role;
        $target->active = $active;
        $saved = $this->userRepository->save($target);

        $this->storage->logAudit(
            $actor->id,
            $actor->name,
            'USER_UPDATE',
            "{$target->name} (#{$target->id}) rolü '{$role}', durumu '" . ($active ? 'Aktif' : 'Pasif') . "' olarak güncellendi."
        );

        return $saved;
    }

    public function createUser(int $actorId, string $name, string $email, string $password, string $role): User
    {
        $actor = $this->authorizationService->requireRole($actorId, ['admin']);

        $name = trim($name);
        $email = trim($email);

        if ($name === '' || $email === '' || $password === '') {
            throw new DomainException('Ad Soyad, e-posta ve şifre alanları zorunludur.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Geçersiz e-posta adresi formatı.');
        }

        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new DomainException('Geçersiz kullanıcı rolü seçildi.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new DomainException('Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        }

        $user = new User(
            id: 0,
            name: $name,
            email: $email,
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            role: $role,
            active: true
        );

        $saved = $this->userRepository->save($user);
        $this->storage->logAudit(
            $actor->id,
            $actor->name,
            'USER_CREATE',
            "Yeni kullanıcı oluşturuldu: {$saved->name} ({$saved->role})."
        );

        return $saved;
    }

    /**
     * US-10: Sistem ayarlarını düzenleme (ödünç süresi, gecikme cezası, hafta sonu politikası).
     *
     * @return array<string, mixed>
     */
    public function updateSettings(
        int $actorId,
        int $loanDurationDays,
        int $dailyFineRateInKurus,
        int $maximumFineInKurus,
        string $finePolicy
    ): array {
        $actor = $this->authorizationService->requireRole($actorId, ['admin']);

        if ($loanDurationDays < 1 || $loanDurationDays > 60) {
            throw new DomainException('Ödünç alma süresi 1 ile 60 gün arasında geçerli bir değer olmalıdır.');
        }

        if ($dailyFineRateInKurus < 0 || $maximumFineInKurus < $dailyFineRateInKurus) {
            throw new DomainException('Gecikme cezası negatif olamaz ve üst sınır günlük ücretten küçük olamaz.');
        }

        if (!in_array($finePolicy, ['calendar', 'weekday'], true)) {
            throw new DomainException('Geçersiz ceza hesaplama politikası.');
        }

        $settings = $this->storage->readSettings();
        $settings['loanDurationDays'] = $loanDurationDays;
        $settings['dailyFineRateInKurus'] = $dailyFineRateInKurus;
        $settings['maximumFineInKurus'] = $maximumFineInKurus;
        $settings['finePolicy'] = $finePolicy;

        $this->storage->writeSettings($settings);

        $this->storage->logAudit(
            $actor->id,
            $actor->name,
            'SETTINGS_UPDATE',
            "Sistem ayarları güncellendi: Süre={$loanDurationDays} gün, Günlük Ceza={$dailyFineRateInKurus} krş, Politika={$finePolicy}."
        );

        return $settings;
    }

    /**
     * US-12: Verilerin yedeğini oluşturma ve yedekleme kontrolü.
     */
    public function createBackup(int $actorId): string
    {
        $actor = $this->authorizationService->requireRole($actorId, ['admin']);
        $backupDir = $this->storage->getStorageDir() . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $collections = ['users', 'categories', 'authors', 'books', 'copies', 'loans', 'reservations', 'fines', 'payments', 'notifications'];
        $snapshot = [
            'backedUpAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'backedUpBy' => $actor->name,
            'settings' => $this->storage->readSettings(),
            'data' => [],
        ];

        foreach ($collections as $col) {
            $snapshot['data'][$col] = $this->storage->read($col);
        }

        $fileName = 'backup_' . (new DateTimeImmutable())->format('Ymd_His') . '.json';
        $filePath = $backupDir . '/' . $fileName;
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false || file_put_contents($filePath, $json, LOCK_EX) === false) {
            throw new DomainException('Veri yedekleme işlemi sırasında bir hata oluştu.');
        }

        $this->storage->logAudit(
            $actor->id,
            $actor->name,
            'BACKUP_CREATE',
            "Sistem verileri yedeklendi ({$fileName})."
        );

        return $fileName;
    }
}