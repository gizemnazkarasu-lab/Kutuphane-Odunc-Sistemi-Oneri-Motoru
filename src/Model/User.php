<?php

declare(strict_types=1);

namespace App\Model;

final class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public string $role = 'member',
        public bool $active = true,
        public string $memberNumber = ''
    ) {
        if ($this->memberNumber === '') {
            $this->memberNumber = sprintf('UYE-%04d', $this->id);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            email: (string) $data['email'],
            passwordHash: (string) $data['passwordHash'],
            role: (string) ($data['role'] ?? 'member'),
            active: (bool) ($data['active'] ?? true),
            memberNumber: (string) ($data['memberNumber'] ?? sprintf('UYE-%04d', (int) $data['id']))
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'passwordHash' => $this->passwordHash,
            'role' => $this->role,
            'active' => $this->active,
            'memberNumber' => $this->memberNumber,
        ];
    }
}
