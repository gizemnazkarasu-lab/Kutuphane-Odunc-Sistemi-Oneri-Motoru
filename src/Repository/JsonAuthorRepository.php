<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Author;

final class JsonAuthorRepository implements AuthorRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Author
    {
        foreach ($this->storage->read('authors') as $row) {
            if ((int) $row['id'] === $id) {
                return Author::fromArray($row);
            }
        }
        return null;
    }

    public function findByIds(array $ids): array
    {
        $idMap = array_flip(array_map('intval', $ids));
        $result = [];
        foreach ($this->storage->read('authors') as $row) {
            if (isset($idMap[(int) $row['id']])) {
                $result[] = Author::fromArray($row);
            }
        }
        return $result;
    }

    public function findByName(string $name): ?Author
    {
        $needle = mb_strtolower(trim($name));
        foreach ($this->storage->read('authors') as $row) {
            if (mb_strtolower(trim((string) $row['name'])) === $needle) {
                return Author::fromArray($row);
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_map(static fn(array $r): Author => Author::fromArray($r), $this->storage->read('authors'));
    }

    public function save(Author $author): Author
    {
        $records = $this->storage->read('authors');
        if ($author->id <= 0) {
            $author->id = $this->storage->nextId($records);
            $records[] = $author->toArray();
        } else {
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $author->id) {
                    $records[$i] = $author->toArray();
                    break;
                }
            }
        }
        $this->storage->write('authors', $records);
        return $author;
    }
}