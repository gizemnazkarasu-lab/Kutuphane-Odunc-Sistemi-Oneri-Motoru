<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepositoryInterface;
use DomainException;

final class AuthorizationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function requireAuthenticated(?int $userId): User
    {
        if ($userId === null || $userId <= 0) {
            throw new DomainException('Bu işlem için sisteme giriş yapmalısınız.');
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null || !$user->active) {
            throw new DomainException('Kullanıcı hesabı bulunamadı veya devre dışı bırakılmış.');
        }

        return $user;
    }

    /**
     * @param string[] $allowedRoles
     */
    public function requireRole(int $actorId, array $allowedRoles): User
    {
        $user = $this->requireAuthenticated($actorId);
        if (!in_array($user->role, $allowedRoles, true)) {
            throw new DomainException('Bu işlemi gerçekleştirmek için yetkiniz bulunmamaktadır.');
        }
        return $user;
    }

    public function requireOwnerOrStaff(int $actorId, int $ownerUserId): User
    {
        $actor = $this->requireAuthenticated($actorId);
        if ($actor->id !== $ownerUserId && !in_array($actor->role, ['librarian', 'admin'], true)) {
            throw new DomainException('Başka bir üyeye ait kayıt üzerinde işlem yapamazsınız.');
        }
        return $actor;
    }
}