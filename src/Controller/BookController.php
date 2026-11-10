<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\BookService;
use App\Service\RecommendationService;
use App\Service\ReservationService;
use DomainException;

final class BookController extends BaseController
{
    public function __construct(
        string $viewsDir,
        ?User $currentUser,
        int $unreadNotificationCount,
        private BookService $bookService,
        private RecommendationService $recommendationService,
        private ReservationService $reservationService,
        private ReservationRepositoryInterface $reservationRepository,
        private UserRepositoryInterface $userRepository
    ) {
        parent::__construct($viewsDir, $currentUser, $unreadNotificationCount);
    }

    public function index(): void
    {
        $this->requireLogin();

        $query = (string) ($_GET['q'] ?? '');
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $catalog = $this->bookService->searchCatalog($query, $categoryId, $page, 6);
        $categories = $this->bookService->listCategories();

        $this->render('catalog', [
            'pageTitle' => 'Kitap Kataloğu',
            'query' => $query,
            'selectedCategoryId' => $categoryId,
            'categories' => $categories,
            'catalog' => $catalog,
        ]);
    }

    public function show(): void
    {
        $user = $this->requireLogin();
        $bookId = (int) ($_GET['id'] ?? 0);

        try {
            $details = $this->bookService->getBookDetails($bookId);
        } catch (DomainException $e) {
            $this->redirect('catalog', null, $e->getMessage());
            return;
        }

        $recommendations = $this->recommendationService->recommendDetailedForBook($bookId, 5);
        $myReservation = $this->reservationRepository->findActiveByUserAndBook($user->id, $bookId);
        $myQueuePosition = $myReservation !== null
            ? $this->reservationService->getQueuePosition($myReservation->id)
            : null;

        $bookQueue = [];
        foreach ($this->reservationRepository->findByBook($bookId) as $res) {
            if (!in_array($res->status, ['waiting', 'allocated'], true)) {
                continue;
            }
            $resUser = $this->userRepository->findById($res->userId);
            $bookQueue[] = [
                'reservation' => $res,
                'user' => $resUser,
            ];
        }

        $this->render('book-detail', [
            'pageTitle' => $details['book']->title . ' - Kitap Detayı ve Öneriler',
            'details' => $details,
            'recommendations' => $recommendations,
            'myReservation' => $myReservation,
            'myQueuePosition' => $myQueuePosition,
            'bookQueue' => $bookQueue,
        ]);
    }

    public function manage(): void
    {
        $this->requireRoles(['librarian', 'admin']);
        $catalog = $this->bookService->searchCatalog('', 0, 1, 100);
        $categories = $this->bookService->listCategories();

        $booksWithCopies = [];
        foreach ($catalog['items'] as $item) {
            $detail = $this->bookService->getBookDetails($item['book']->id);
            $booksWithCopies[] = $detail;
        }

        $this->render('librarian-books', [
            'pageTitle' => 'Kitap ve Kopya Yönetimi',
            'booksWithCopies' => $booksWithCopies,
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        $actor = $this->requireRoles(['librarian', 'admin']);
        try {
            $title = (string) ($_POST['title'] ?? '');
            $authorName = (string) ($_POST['author_name'] ?? '');
            $isbn = (string) ($_POST['isbn'] ?? '');
            $categoryId = (int) ($_POST['category_id'] ?? 1);
            $initialCopies = (int) ($_POST['initial_copies'] ?? 1);

            $book = $this->bookService->createBook(
                $actor->id,
                $title,
                $authorName,
                $isbn,
                $categoryId,
                $initialCopies
            );
            $this->redirect('librarian/books', "'{$book->title}' kitabı ve {$initialCopies} adet fiziksel kopyası başarıyla eklendi.");
        } catch (DomainException $e) {
            $this->redirect('librarian/books', null, $e->getMessage());
        }
    }

    public function addCopy(): void
    {
        $actor = $this->requireRoles(['librarian', 'admin']);
        try {
            $bookId = (int) ($_POST['book_id'] ?? 0);
            $inventoryCode = (string) ($_POST['inventory_code'] ?? '');
            $copy = $this->bookService->addCopy($actor->id, $bookId, $inventoryCode);
            $this->redirect('librarian/books', "Yeni fiziksel kopya ({$copy->inventoryCode}) başarıyla eklendi.");
        } catch (DomainException $e) {
            $this->redirect('librarian/books', null, $e->getMessage());
        }
    }

    public function delete(): void
    {
        $actor = $this->requireRoles(['librarian', 'admin']);
        try {
            $bookId = (int) ($_POST['book_id'] ?? 0);
            $this->bookService->removeBook($actor->id, $bookId);
            $this->redirect('librarian/books', 'Kitap ve kopyaları katalogdan başarıyla kaldırıldı.');
        } catch (DomainException $e) {
            $this->redirect('librarian/books', null, $e->getMessage());
        }
    }
}