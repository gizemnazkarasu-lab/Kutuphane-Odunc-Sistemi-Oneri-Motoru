<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByCredential(string $credential): ?User;
    /** @return User[] */
    public function findAll(): array;
    public function save(User $user): User;
}