<?php

declare(strict_types=1);

namespace App\Model;

final class Book
{
    /**
     * @param int[] $authorIds
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $isbn = '',
        public int $categoryId = 1,
        public string $coverPath = '',
        public array $authorIds = []
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $authorIds = array_map('intval', (array) ($data['authorIds'] ?? []));
        return new self(
            id: (int) $data['id'],
            title: (string) $data['title'],
            isbn: (string) ($data['isbn'] ?? ''),
            categoryId: (int) ($data['categoryId'] ?? 1),
            coverPath: (string) ($data['coverPath'] ?? ''),
            authorIds: array_values($authorIds)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'isbn' => $this->isbn,
            'categoryId' => $this->categoryId,
            'coverPath' => $this->coverPath,
            'authorIds' => $this->authorIds,
        ];
    }
}
