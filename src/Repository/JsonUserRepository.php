<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

final class JsonUserRepository implements UserRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?User
    {
        foreach ($this->storage->read('users') as $row) {
            if ((int) $row['id'] === $id) {
                return User::fromArray($row);
            }
        }
        return null;
    }

    public function findByEmail(string $email): ?User
    {
        $needle = mb_strtolower(trim($email));
        foreach ($this->storage->read('users') as $row) {
            if (mb_strtolower(trim((string) $row['email'])) === $needle) {
                return User::fromArray($row);
            }
        }
        return null;
    }

    public function findByCredential(string $credential): ?User
    {
        $needle = mb_strtolower(trim($credential));
        if ($needle === '') {
            return null;
        }

        foreach ($this->storage->read('users') as $row) {
            $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
            $emailPrefix = explode('@', $email)[0] ?? '';
            $memberNo = mb_strtolower(trim((string) ($row['memberNumber'] ?? '')));
            $name = mb_strtolower(trim((string) ($row['name'] ?? '')));

            if (
                $needle === $email
                || $needle === $emailPrefix
                || $needle === $memberNo
                || $needle === $name
                || (string) $row['id'] === $needle
                || ($name !== '' && str_starts_with($name, $needle))
            ) {
                return User::fromArray($row);
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_map(static fn(array $r): User => User::fromArray($r), $this->storage->read('users'));
    }

    public function save(User $user): User
    {
        $records = $this->storage->read('users');
        if ($user->id <= 0) {
            $user->id = $this->storage->nextId($records);
            $rolePrefix = match ($user->role) {
                'librarian' => 'GOR',
                'admin' => 'ADM',
                default => 'UYE',
            };
            $user->memberNumber = sprintf('%s-%04d', $rolePrefix, $user->id);
            $records[] = $user->toArray();
        } else {
            $updated = false;
            foreach ($records as $index => $row) {
                if ((int) $row['id'] === $user->id) {
                    $records[$index] = $user->toArray();
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $records[] = $user->toArray();
            }
        }
        $this->storage->write('users', $records);
        return $user;
    }
}