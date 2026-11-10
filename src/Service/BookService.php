<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Author;
use App\Model\Book;
use App\Model\Category;
use App\Model\Copy;
use App\Repository\AuthorRepositoryInterface;
use App\Repository\BookRepositoryInterface;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\CopyRepositoryInterface;
use App\Repository\JsonStorage;
use App\Repository\LoanRepositoryInterface;
use App\Repository\ReservationRepositoryInterface;
use DomainException;

final class BookService
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private AuthorRepositoryInterface $authorRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private CopyRepositoryInterface $copyRepository,
        private ?LoanRepositoryInterface $loanRepository = null,
        private ?ReservationRepositoryInterface $reservationRepository = null,
        private ?AuthorizationService $authorizationService = null,
        private ?JsonStorage $storage = null
    ) {}

    /**
     * Katalog araması, kategori filtreleme ve sayfalama.
     *
     * @return array{
     *   items: array<int, array{book: Book, authors: string, category: string, availableCount: int, totalCopies: int}>,
     *   total: int,
     *   page: int,
     *   perPage: int,
     *   totalPages: int
     * }
     */
    public function searchCatalog(string $query = '', int $categoryId = 0, int $page = 1, int $perPage = 6): array
    {
        $query = mb_strtolower(trim($query));
        $books = $this->bookRepository->findAll();
        $enriched = [];

        foreach ($books as $book) {
            if ($categoryId > 0 && $book->categoryId !== $categoryId) {
                continue;
            }

            $authors = $this->authorRepository->findByIds($book->authorIds);
            $authorNames = implode(', ', array_map(static fn(Author $a): string => $a->name, $authors));
            $category = $this->categoryRepository->findById($book->categoryId);
            $categoryName = $category !== null ? $category->name : 'Genel';

            if ($query !== '') {
                $haystack = mb_strtolower($book->title . ' ' . $authorNames . ' ' . $book->isbn);
                if (!str_contains($haystack, $query)) {
                    continue;
                }
            }

            $allCopies = $this->copyRepository->findByBookId($book->id);
            $availableCopies = $this->copyRepository->findAvailableByBookId($book->id);

            $enriched[] = [
                'book' => $book,
                'authors' => $authorNames !== '' ? $authorNames : 'Bilinmeyen Yazar',
                'category' => $categoryName,
                'availableCount' => count($availableCopies),
                'totalCopies' => count($allCopies),
            ];
        }

        $total = count($enriched);
        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($enriched, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * @return array{
     *   book: Book,
     *   authors: string,
     *   category: string,
     *   copies: Copy[],
     *   availableCopies: Copy[],
     *   availableCount: int
     * }
     */
    public function getBookDetails(int $bookId): array
    {
        $book = $this->bookRepository->findById($bookId);
        if ($book === null) {
            throw new DomainException('Kitap kaydı bulunamadı.');
        }

        $authors = $this->authorRepository->findByIds($book->authorIds);
        $authorNames = implode(', ', array_map(static fn(Author $a): string => $a->name, $authors));
        $category = $this->categoryRepository->findById($book->categoryId);
        $copies = $this->copyRepository->findByBookId($book->id);
        $availableCopies = $this->copyRepository->findAvailableByBookId($book->id);

        return [
            'book' => $book,
            'authors' => $authorNames !== '' ? $authorNames : 'Bilinmeyen Yazar',
            'category' => $category !== null ? $category->name : 'Genel',
            'copies' => $copies,
            'availableCopies' => $availableCopies,
            'availableCount' => count($availableCopies),
        ];
    }

    /**
     * @return Category[]
     */
    public function listCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    public function createBook(
        int $actorId,
        string $title,
        string $authorName,
        string $isbn,
        int $categoryId,
        int $initialCopies = 1
    ): Book {
        $actor = $this->authorizationService?->requireRole($actorId, ['librarian', 'admin']);

        $title = trim($title);
        $authorName = trim($authorName);
        $isbn = trim($isbn);

        if ($title === '' || $authorName === '' || $isbn === '') {
            throw new DomainException('Kitap adı, yazar adı ve ISBN alanları zorunludur.');
        }

        if ($this->bookRepository->findByIsbn($isbn) !== null) {
            throw new DomainException('Bu ISBN numarası ile kayıtlı bir kitap zaten mevcut.');
        }

        // Yazar kontrolü veya oluşturma
        $author = $this->authorRepository->findByName($authorName);
        if ($author === null) {
            $author = $this->authorRepository->save(new Author(0, $authorName));
        }

        $book = new Book(
            id: 0,
            title: $title,
            isbn: $isbn,
            categoryId: $categoryId > 0 ? $categoryId : 1,
            coverPath: 'default-book.jpg',
            authorIds: [$author->id]
        );

        $savedBook = $this->bookRepository->save($book);

        $copyCount = max(1, min(10, $initialCopies));
        $allCopies = $this->copyRepository->findAll();
        $nextNum = count($allCopies) + 1;

        for ($i = 0; $i < $copyCount; $i++) {
            $code = sprintf('K-%03d', $nextNum + $i);
            while ($this->copyRepository->findByInventoryCode($code) !== null) {
                $nextNum++;
                $code = sprintf('K-%03d', $nextNum + $i);
            }
            $this->copyRepository->save(new Copy(
                id: 0,
                bookId: $savedBook->id,
                inventoryCode: $code,
                status: Copy::STATUS_AVAILABLE
            ));
        }

        $this->storage?->logAudit(
            $actorId,
            $actor !== null ? $actor->name : 'Görevli',
            'BOOK_CREATE',
            "Yeni kitap eklendi: {$savedBook->title} ({$copyCount} kopya)."
        );

        return $savedBook;
    }

    public function addCopy(int $actorId, int $bookId, string $inventoryCode): Copy
    {
        $actor = $this->authorizationService?->requireRole($actorId, ['librarian', 'admin']);
        $book = $this->bookRepository->findById($bookId);
        if ($book === null) {
            throw new DomainException('Kitap bulunamadı.');
        }

        $inventoryCode = strtoupper(trim($inventoryCode));
        if ($inventoryCode === '') {
            throw new DomainException('Envanter kodu boş bırakılamaz.');
        }

        if ($this->copyRepository->findByInventoryCode($inventoryCode) !== null) {
            throw new DomainException("'{$inventoryCode}' envanter kodlu kopya zaten mevcut.");
        }

        $copy = new Copy(0, $bookId, $inventoryCode, Copy::STATUS_AVAILABLE);
        $this->copyRepository->save($copy);

        $this->storage?->logAudit(
            $actorId,
            $actor !== null ? $actor->name : 'Görevli',
            'COPY_ADD',
            "{$book->title} kitabına yeni kopya eklendi: {$inventoryCode}."
        );

        return $copy;
    }

    public function removeBook(int $actorId, int $bookId): void
    {
        $actor = $this->authorizationService?->requireRole($actorId, ['librarian', 'admin']);
        $book = $this->bookRepository->findById($bookId);
        if ($book === null) {
            throw new DomainException('Silinmek istenen kitap kaydı sistemde bulunamadı.');
        }

        $copies = $this->copyRepository->findByBookId($bookId);
        foreach ($copies as $copy) {
            if ($copy->status !== Copy::STATUS_AVAILABLE) {
                throw new DomainException('Ödünçte veya rezervasyona ayrılmış kopyası bulunan kitap silinemez.');
            }
        }

        $this->copyRepository->deleteByBookId($bookId);
        $this->bookRepository->delete($bookId);

        $this->storage?->logAudit(
            $actorId,
            $actor !== null ? $actor->name : 'Görevli',
            'BOOK_DELETE',
            "Kitap katalogdan kaldırıldı: {$book->title} (#{$bookId})."
        );
    }
}