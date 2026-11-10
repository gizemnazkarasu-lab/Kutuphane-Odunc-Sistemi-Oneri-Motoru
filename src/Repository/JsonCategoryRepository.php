<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;

final class JsonCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private JsonStorage $storage) {}

    public function findById(int $id): ?Category
    {
        foreach ($this->storage->read('categories') as $row) {
            if ((int) $row['id'] === $id) {
                return Category::fromArray($row);
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_map(static fn(array $r): Category => Category::fromArray($r), $this->storage->read('categories'));
    }

    public function save(Category $category): Category
    {
        $records = $this->storage->read('categories');
        if ($category->id <= 0) {
            $category->id = $this->storage->nextId($records);
            $records[] = $category->toArray();
        } else {
            foreach ($records as $i => $row) {
                if ((int) $row['id'] === $category->id) {
                    $records[$i] = $category->toArray();
                    break;
                }
            }
        }
        $this->storage->write('categories', $records);
        return $category;
    }
}