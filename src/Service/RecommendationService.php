<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Author;
use App\Model\Book;
use App\Repository\AuthorRepositoryInterface;
use App\Repository\BookRepositoryInterface;
use App\Repository\LoanRepositoryInterface;

final class RecommendationService
{
    public function __construct(
        private LoanRepositoryInterface $loanRepository,
        private BookRepositoryInterface $bookRepository,
        private ?AuthorRepositoryInterface $authorRepository = null
    ) {}

    /**
     * "Bunu okuyanlar şunu da okudu" — Ortak ödünç geçmişine göre sıralı öneriler.
     *
     * @return Book[]
     */
    public function recommendForBook(int $bookId, int $limit = 5): array
    {
        $counts = $this->loanRepository->findCoBorrowCounts($bookId);
        $recommended = [];

        foreach ($counts as $otherBookId => $sharedCount) {
            if (count($recommended) >= $limit) {
                break;
            }
            $book = $this->bookRepository->findById((int) $otherBookId);
            if ($book !== null) {
                $recommended[] = $book;
            }
        }

        return $recommended;
    }

    /**
     * Ekran gösterimi için ortak okur sayısı ve yazar bilgileriyle zenginleştirilmiş öneri listesi.
     *
     * @return array<int, array{book: Book, authors: string, sharedReaderCount: int}>
     */
    public function recommendDetailedForBook(int $bookId, int $limit = 5): array
    {
        $counts = $this->loanRepository->findCoBorrowCounts($bookId);
        $result = [];

        foreach ($counts as $otherBookId => $sharedCount) {
            if (count($result) >= $limit) {
                break;
            }
            $book = $this->bookRepository->findById((int) $otherBookId);
            if ($book === null) {
                continue;
            }

            $authorNames = 'Bilinmeyen Yazar';
            if ($this->authorRepository !== null) {
                $authors = $this->authorRepository->findByIds($book->authorIds);
                $names = array_map(static fn(Author $a): string => $a->name, $authors);
                if ($names !== []) {
                    $authorNames = implode(', ', $names);
                }
            }

            $result[] = [
                'book' => $book,
                'authors' => $authorNames,
                'sharedReaderCount' => (int) $sharedCount,
            ];
        }

        return $result;
    }
}