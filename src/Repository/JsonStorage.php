<?php

declare(strict_types=1);

namespace App\Repository;

use RuntimeException;

final class JsonStorage
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/');
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0775, true);
        }
    }

    public function getStorageDir(): string
    {
        return $this->storageDir;
    }

    public function getFilePath(string $collection): string
    {
        return $this->storageDir . '/' . $collection . '.json';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(string $collection): array
    {
        $path = $this->getFilePath($collection);
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values($decoded);
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    public function write(string $collection, array $records): void
    {
        $path = $this->getFilePath($collection);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException("JSON kodlama hatası: {$collection}");
        }

        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new RuntimeException("JSON dosyası yazılamadı: {$path}");
        }
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    public function nextId(array $records): int
    {
        $max = 0;
        foreach ($records as $record) {
            $id = (int) ($record['id'] ?? 0);
            if ($id > $max) {
                $max = $id;
            }
        }
        return $max + 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function readSettings(): array
    {
        $path = $this->getFilePath('settings');
        $defaults = [
            'loanDurationDays' => 14,
            'maxActiveLoans' => 5,
            'maxRenewals' => 1,
            'reservationHoldDays' => 3,
            'dailyFineRateInKurus' => 500, // 5.00 TL / gün
            'maximumFineInKurus' => 10000, // 100.00 TL üst sınır
            'finePolicy' => 'calendar', // 'calendar' veya 'weekday'
        ];

        if (!file_exists($path)) {
            $this->writeSettings($defaults);
            return $defaults;
        }

        $content = file_get_contents($path);
        $decoded = is_string($content) ? json_decode($content, true) : null;
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function writeSettings(array $settings): void
    {
        $path = $this->getFilePath('settings');
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            file_put_contents($path, $json, LOCK_EX);
        }
    }

    public function logAudit(int $actorId, string $actorName, string $action, string $details): void
    {
        $logs = $this->read('audit_logs');
        $logs[] = [
            'id' => $this->nextId($logs),
            'actorId' => $actorId,
            'actorName' => $actorName,
            'action' => $action,
            'details' => $details,
            'createdAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->write('audit_logs', $logs);
    }

    public function seedDefaults(bool $force = false): void
    {
        if (!$force && file_exists($this->getFilePath('books')) && count($this->read('books')) > 0) {
            return;
        }

        $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

        $users = [
            [
                'id' => 1,
                'name' => 'Ümmügülsüm Kitapçıoğlu',
                'email' => 'gulsum@kutuphane.edu.tr',
                'passwordHash' => $passwordHash,
                'role' => 'member',
                'active' => true,
                'memberNumber' => 'UYE-0001',
            ],
            [
                'id' => 2,
                'name' => 'Gizem Naz Karasu',
                'email' => 'gizem@kutuphane.edu.tr',
                'passwordHash' => $passwordHash,
                'role' => 'member',
                'active' => true,
                'memberNumber' => 'UYE-0002',
            ],
            [
                'id' => 3,
                'name' => 'Remziye Ersun',
                'email' => 'remziye@kutuphane.edu.tr',
                'passwordHash' => $passwordHash,
                'role' => 'member',
                'active' => true,
                'memberNumber' => 'UYE-0003',
            ],
            [
                'id' => 4,
                'name' => 'Ahmet Yılmaz (Üye)',
                'email' => 'ahmet@kutuphane.edu.tr',
                'passwordHash' => $passwordHash,
                'role' => 'member',
                'active' => true,
                'memberNumber' => 'UYE-0004',
            ],
            [
                'id' => 5,
                'name' => 'Ayşe Demir (Kütüphaneci)',
                'email' => 'kutuphaneci@kutuphane.edu.tr',
                'passwordHash' => $passwordHash,
                'role' => 'librarian',
                'active' => true,
                'memberNumber' => 'GOR-0001',
            ],
            [
                'id' => 6,
                'name' => 'Sistem Yöneticisi',
                'email' => 'admin@kutuphane.edu.tr',
                'passwordHash' => $passwordHash,
                'role' => 'admin',
                'active' => true,
                'memberNumber' => 'ADM-0001',
            ],
        ];

        $categories = [
            ['id' => 1, 'name' => 'Roman'],
            ['id' => 2, 'name' => 'Dünya Klasikleri'],
            ['id' => 3, 'name' => 'Bilim Kurgu'],
            ['id' => 4, 'name' => 'Yazılım ve Teknoloji'],
            ['id' => 5, 'name' => 'Tarih ve Felsefe'],
        ];

        $authors = [
            ['id' => 1, 'name' => 'Sabahattin Ali'],
            ['id' => 2, 'name' => 'Victor Hugo'],
            ['id' => 3, 'name' => 'Ahmet Hamdi Tanpınar'],
            ['id' => 4, 'name' => 'Fyodor Dostoyevski'],
            ['id' => 5, 'name' => 'Oğuz Atay'],
            ['id' => 6, 'name' => 'Frank Herbert'],
            ['id' => 7, 'name' => 'Robert C. Martin'],
        ];

        $books = [
            [
                'id' => 1,
                'title' => 'Kürk Mantolu Madonna',
                'isbn' => '978-975-363-802-9',
                'categoryId' => 1,
                'coverPath' => 'kurk-mantolu-madonna.jpg',
                'authorIds' => [1],
            ],
            [
                'id' => 2,
                'title' => 'Sefiller',
                'isbn' => '978-975-458-717-3',
                'categoryId' => 2,
                'coverPath' => 'sefiller.jpg',
                'authorIds' => [2],
            ],
            [
                'id' => 3,
                'title' => 'İçimizdeki Şeytan',
                'isbn' => '978-975-363-803-6',
                'categoryId' => 1,
                'coverPath' => 'icimizdeki-seytan.jpg',
                'authorIds' => [1],
            ],
            [
                'id' => 4,
                'title' => 'Kuyucaklı Yusuf',
                'isbn' => '978-975-363-804-3',
                'categoryId' => 1,
                'coverPath' => 'kuyucakli-yusuf.jpg',
                'authorIds' => [1],
            ],
            [
                'id' => 5,
                'title' => 'Saatleri Ayarlama Enstitüsü',
                'isbn' => '978-975-995-576-2',
                'categoryId' => 1,
                'coverPath' => 'saatleri-ayarlama.jpg',
                'authorIds' => [3],
            ],
            [
                'id' => 6,
                'title' => 'Suç ve Ceza',
                'isbn' => '978-975-458-902-3',
                'categoryId' => 2,
                'coverPath' => 'suc-ve-ceza.jpg',
                'authorIds' => [4],
            ],
            [
                'id' => 7,
                'title' => 'Tutunamayanlar',
                'isbn' => '978-975-470-011-4',
                'categoryId' => 1,
                'coverPath' => 'tutunamayanlar.jpg',
                'authorIds' => [5],
            ],
            [
                'id' => 8,
                'title' => 'Dune',
                'isbn' => '978-605-375-479-4',
                'categoryId' => 3,
                'coverPath' => 'dune.jpg',
                'authorIds' => [6],
            ],
            [
                'id' => 9,
                'title' => 'Temiz Kod (Clean Code)',
                'isbn' => '978-605-912-901-5',
                'categoryId' => 4,
                'coverPath' => 'clean-code.jpg',
                'authorIds' => [7],
            ],
        ];

        $copies = [
            ['id' => 1, 'bookId' => 1, 'inventoryCode' => 'K-001', 'status' => 'available'],
            ['id' => 2, 'bookId' => 1, 'inventoryCode' => 'K-002', 'status' => 'available'],
            ['id' => 3, 'bookId' => 2, 'inventoryCode' => 'K-003', 'status' => 'loaned'],
            ['id' => 4, 'bookId' => 3, 'inventoryCode' => 'K-004', 'status' => 'available'],
            ['id' => 5, 'bookId' => 3, 'inventoryCode' => 'K-005', 'status' => 'available'],
            ['id' => 6, 'bookId' => 4, 'inventoryCode' => 'K-006', 'status' => 'loaned'],
            ['id' => 7, 'bookId' => 4, 'inventoryCode' => 'K-007', 'status' => 'available'],
            ['id' => 8, 'bookId' => 5, 'inventoryCode' => 'K-008', 'status' => 'loaned'],
            ['id' => 9, 'bookId' => 6, 'inventoryCode' => 'K-009', 'status' => 'available'],
            ['id' => 10, 'bookId' => 7, 'inventoryCode' => 'K-010', 'status' => 'available'],
            ['id' => 11, 'bookId' => 8, 'inventoryCode' => 'K-011', 'status' => 'available'],
            ['id' => 12, 'bookId' => 9, 'inventoryCode' => 'K-012', 'status' => 'available'],
        ];

        $now = new \DateTimeImmutable();

        $loans = [
            [
                'id' => 1,
                'userId' => 2,
                'copyId' => 1,
                'borrowedAt' => $now->modify('-45 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-31 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-33 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 2,
                'userId' => 2,
                'copyId' => 4,
                'borrowedAt' => $now->modify('-30 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-16 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-18 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 3,
                'userId' => 2,
                'copyId' => 7,
                'borrowedAt' => $now->modify('-25 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-11 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-12 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 4,
                'userId' => 3,
                'copyId' => 2,
                'borrowedAt' => $now->modify('-40 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-26 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-28 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 5,
                'userId' => 3,
                'copyId' => 5,
                'borrowedAt' => $now->modify('-28 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-14 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-15 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 6,
                'userId' => 3,
                'copyId' => 7,
                'borrowedAt' => $now->modify('-20 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-6 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-7 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 7,
                'userId' => 4,
                'copyId' => 1,
                'borrowedAt' => $now->modify('-35 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-21 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-22 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 8,
                'userId' => 4,
                'copyId' => 4,
                'borrowedAt' => $now->modify('-20 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-6 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-8 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 9,
                'userId' => 1,
                'copyId' => 9,
                'borrowedAt' => $now->modify('-30 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-16 days')->format('Y-m-d H:i:s'),
                'returnedAt' => $now->modify('-12 days')->format('Y-m-d H:i:s'),
                'renewalCount' => 0,
            ],
            [
                'id' => 10,
                'userId' => 1,
                'copyId' => 3,
                'borrowedAt' => $now->modify('-5 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('+9 days')->format('Y-m-d H:i:s'),
                'returnedAt' => null,
                'renewalCount' => 0,
            ],
            [
                'id' => 11,
                'userId' => 1,
                'copyId' => 6,
                'borrowedAt' => $now->modify('-25 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('-5 days')->format('Y-m-d H:i:s'),
                'returnedAt' => null,
                'renewalCount' => 1,
            ],
            [
                'id' => 12,
                'userId' => 2,
                'copyId' => 8,
                'borrowedAt' => $now->modify('-10 days')->format('Y-m-d H:i:s'),
                'dueAt' => $now->modify('+4 days')->format('Y-m-d H:i:s'),
                'returnedAt' => null,
                'renewalCount' => 0,
            ],
        ];

        $reservations = [
            [
                'id' => 1,
                'userId' => 2,
                'bookId' => 2,
                'allocatedCopyId' => null,
                'createdAt' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
                'allocatedAt' => null,
                'expiresAt' => null,
                'status' => 'waiting',
            ],
            [
                'id' => 2,
                'userId' => 3,
                'bookId' => 2,
                'allocatedCopyId' => null,
                'createdAt' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
                'allocatedAt' => null,
                'expiresAt' => null,
                'status' => 'waiting',
            ],
            [
                'id' => 3,
                'userId' => 1,
                'bookId' => 5,
                'allocatedCopyId' => null,
                'createdAt' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
                'allocatedAt' => null,
                'expiresAt' => null,
                'status' => 'waiting',
            ],
        ];

        $fines = [
            [
                'id' => 1,
                'loanId' => 9,
                'amountInKurus' => 2000,
                'calculatedAt' => $now->modify('-12 days')->format('Y-m-d H:i:s'),
            ],
        ];

        $this->write('users', $users);
        $this->write('categories', $categories);
        $this->write('authors', $authors);
        $this->write('books', $books);
        $this->write('copies', $copies);
        $this->write('loans', $loans);
        $this->write('reservations', $reservations);
        $this->write('fines', $fines);
        $this->write('payments', []);
        $this->write('notifications', []);
        $this->write('audit_logs', [
            [
                'id' => 1,
                'actorId' => 6,
                'actorName' => 'Sistem Yöneticisi',
                'action' => 'SEED_DATA',
                'details' => 'Sistem başlangıç JSON verileri oluşturuldu.',
                'createdAt' => $now->format('Y-m-d H:i:s'),
            ],
        ]);
        $this->readSettings();
    }
}
