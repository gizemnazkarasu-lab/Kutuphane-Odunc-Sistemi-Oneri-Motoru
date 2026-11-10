<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\JsonStorage;
use App\Repository\UserRepositoryInterface;
use DomainException;

final class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ?JsonStorage $storage = null
    ) {}

    public function login(string $credential, string $password): User
    {
        $credential = trim($credential);
        if ($credential === '' || $password === '') {
            throw new DomainException('Kullanıcı adı/e-posta ve şifre alanları zorunludur.');
        }

        $user = $this->userRepository->findByCredential($credential);
        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new DomainException('Kullanıcı adı veya şifre hatalı.');
        }

        if (!$user->active) {
            throw new DomainException('Hesabınız devre dışı bırakılmıştır. Yönetici ile iletişime geçiniz.');
        }

        $this->storage?->logAudit($user->id, $user->name, 'LOGIN', 'Sisteme giriş yapıldı.');
        return $user;
    }

    public function register(string $name, string $email, string $password): User
    {
        $name = trim($name);
        $email = trim($email);

        if ($name === '' || $email === '' || $password === '') {
            throw new DomainException('Tüm kayıt alanları zorunludur.');
        }

        if (mb_strlen($name) < 3 || preg_match('/[<>{};$]/', $name)) {
            throw new DomainException('Ad Soyad alanında geçersiz karakter veya eksik bilgi bulunmaktadır.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Geçerli bir e-posta adresi giriniz.');
        }

        if (mb_strlen($password) < 4) {
            throw new DomainException('Şifre en az 4 karakter olmalıdır.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new DomainException('Bu e-posta adresi ile kayıtlı bir üye zaten mevcut.');
        }

        $user = new User(
            id: 0,
            name: $name,
            email: $email,
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            role: 'member',
            active: true
        );

        $saved = $this->userRepository->save($user);
        $this->storage?->logAudit($saved->id, $saved->name, 'REGISTER', "Yeni üye kaydı oluşturuldu ({$saved->memberNumber}).");
        return $saved;
    }
}