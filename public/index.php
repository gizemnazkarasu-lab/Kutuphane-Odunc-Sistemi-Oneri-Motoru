<?php

declare(strict_types=1);

$sessionDir = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0775, true);
}
if (session_status() === PHP_SESSION_NONE) {
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    session_start();
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\BaseController;
use App\Controller\BookController;
use App\Controller\FineController;
use App\Controller\LoanController;
use App\Controller\NotificationController;
use App\Controller\RecommendationController;
use App\Controller\ReportController;
use App\Controller\ReservationController;
use App\Repository\JsonAuthorRepository;
use App\Repository\JsonBookRepository;
use App\Repository\JsonCategoryRepository;
use App\Repository\JsonCopyRepository;
use App\Repository\JsonFineRepository;
use App\Repository\JsonLoanRepository;
use App\Repository\JsonNotificationRepository;
use App\Repository\JsonPaymentRepository;
use App\Repository\JsonReservationRepository;
use App\Repository\JsonStorage;
use App\Repository\JsonUserRepository;
use App\Router;
use App\Service\AdminService;
use App\Service\AuthorizationService;
use App\Service\AuthService;
use App\Service\BookService;
use App\Service\CalendarDayFinePolicy;
use App\Service\EventDispatcher;
use App\Service\FineService;
use App\Service\LoanService;
use App\Service\NotificationService;
use App\Service\RecommendationService;
use App\Service\ReportService;
use App\Service\ReservationNotificationListener;
use App\Service\ReservationService;
use App\Service\ReservationStateFactory;
use App\Service\WeekdayFinePolicy;

$storageDir = __DIR__ . '/../storage';
$viewsDir = __DIR__ . '/../views';

$storage = new JsonStorage($storageDir);
$storage->seedDefaults(false);
$settings = $storage->readSettings();

$userRepo = new JsonUserRepository($storage);
$categoryRepo = new JsonCategoryRepository($storage);
$authorRepo = new JsonAuthorRepository($storage);
$bookRepo = new JsonBookRepository($storage);
$copyRepo = new JsonCopyRepository($storage);
$loanRepo = new JsonLoanRepository($storage);
$reservationRepo = new JsonReservationRepository($storage);
$fineRepo = new JsonFineRepository($storage);
$paymentRepo = new JsonPaymentRepository($storage);
$notificationRepo = new JsonNotificationRepository($storage);

$notificationService = new NotificationService($notificationRepo, $copyRepo, $bookRepo);
$eventDispatcher = new EventDispatcher();
$eventDispatcher->subscribe(new ReservationNotificationListener($notificationService));

$finePolicy = ($settings['finePolicy'] ?? 'calendar') === 'weekday'
    ? new WeekdayFinePolicy()
    : new CalendarDayFinePolicy();

$fineService = new FineService(
    policy: $finePolicy,
    dailyRateInKurus: (int) ($settings['dailyFineRateInKurus'] ?? 500),
    maximumFineInKurus: (int) ($settings['maximumFineInKurus'] ?? 10000),
    fineRepository: $fineRepo,
    paymentRepository: $paymentRepo
);

$authorizationService = new AuthorizationService($userRepo);
$authService = new AuthService($userRepo, $storage);

$reservationService = new ReservationService(
    reservationRepository: $reservationRepo,
    copyRepository: $copyRepo,
    bookRepository: $bookRepo,
    stateFactory: new ReservationStateFactory(),
    loanRepository: $loanRepo,
    eventDispatcher: $eventDispatcher,
    holdDays: (int) ($settings['reservationHoldDays'] ?? 3)
);

$loanService = new LoanService(
    loanRepository: $loanRepo,
    copyRepository: $copyRepo,
    reservationRepository: $reservationRepo,
    reservationService: $reservationService,
    fineService: $fineService,
    fineRepository: $fineRepo,
    eventDispatcher: $eventDispatcher,
    userRepository: $userRepo,
    authorizationService: $authorizationService,
    storage: $storage,
    maxActiveLoans: (int) ($settings['maxActiveLoans'] ?? 5),
    loanDurationDays: (int) ($settings['loanDurationDays'] ?? 14),
    maxRenewals: (int) ($settings['maxRenewals'] ?? 1)
);

$bookService = new BookService(
    bookRepository: $bookRepo,
    authorRepository: $authorRepo,
    categoryRepository: $categoryRepo,
    copyRepository: $copyRepo,
    loanRepository: $loanRepo,
    reservationRepository: $reservationRepo,
    authorizationService: $authorizationService,
    storage: $storage
);

$recommendationService = new RecommendationService($loanRepo, $bookRepo, $authorRepo);
$reportService = new ReportService($loanRepo, $bookRepo, $copyRepo, $userRepo);
$adminService = new AdminService($userRepo, $authorizationService, $storage);

// Oturum ve yedek çerez kontrolü
if (!isset($_SESSION['user_id']) && isset($_COOKIE['kutuphane_uid']) && (int) $_COOKIE['kutuphane_uid'] > 0) {
    $_SESSION['user_id'] = (int) $_COOKIE['kutuphane_uid'];
}
if (!isset($_SESSION['user_id']) && empty($_SESSION['logged_out']) && !isset($_COOKIE['kutuphane_uid'])) {
    $_SESSION['user_id'] = 1;
}

$currentUser = null;
if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0) {
    $currentUser = $userRepo->findById((int) $_SESSION['user_id']);
    if ($currentUser === null || !$currentUser->active) {
        unset($_SESSION['user_id']);
        $currentUser = null;
    }
}

BaseController::$allUsersForBar = $userRepo->findAll();

$unreadCount = 0;
if ($currentUser !== null) {
    foreach ($notificationService->listForUser($currentUser->id) as $n) {
        if ($n->readAt === null) {
            $unreadCount++;
        }
    }
}

$authController = new AuthController($viewsDir, $currentUser, $unreadCount, $authService, $userRepo);
$bookController = new BookController($viewsDir, $currentUser, $unreadCount, $bookService, $recommendationService, $reservationService, $reservationRepo, $userRepo);
$loanController = new LoanController($viewsDir, $currentUser, $unreadCount, $loanService, $loanRepo, $copyRepo, $bookRepo, $userRepo, $fineRepo, $reservationRepo);
$reservationController = new ReservationController($viewsDir, $currentUser, $unreadCount, $reservationService, $reservationRepo, $bookRepo, $copyRepo, $userRepo);
$fineController = new FineController($viewsDir, $currentUser, $unreadCount, $fineService, $fineRepo, $paymentRepo, $loanRepo, $copyRepo, $bookRepo, $userRepo);
$notificationController = new NotificationController($viewsDir, $currentUser, $unreadCount, $notificationService);
$recommendationController = new RecommendationController($viewsDir, $currentUser, $unreadCount, $recommendationService, $bookRepo);
$reportController = new ReportController($viewsDir, $currentUser, $unreadCount, $reportService, $userRepo, $loanRepo, $reservationRepo);
$adminController = new AdminController($viewsDir, $currentUser, $unreadCount, $adminService, $userRepo, $storage);

$router = new Router();

$router->get('/', fn() => $currentUser !== null ? $bookController->index() : $authController->showLogin());
$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->post('/auth/quick-switch', [$authController, 'quickSwitch']);
$router->get('/auth/quick-switch', [$authController, 'quickSwitch']);
$router->get('/register', [$authController, 'showRegister']);
$router->post('/register', [$authController, 'register']);
$router->get('/logout', [$authController, 'logout']);

$router->get('/catalog', [$bookController, 'index']);
$router->get('/books/show', [$bookController, 'show']);
$router->get('/my-loans', [$loanController, 'myLoans']);
$router->get('/my-reservations', [$reservationController, 'index']);
$router->get('/my-fines', [$fineController, 'myFines']);
$router->get('/notifications', [$notificationController, 'index']);
$router->post('/notifications/read', [$notificationController, 'markAsRead']);
$router->get('/recommendations', [$recommendationController, 'show']);

$router->post('/loans/borrow', [$loanController, 'borrow']);
$router->post('/loans/return', [$loanController, 'returnCopy']);
$router->post('/loans/renew', [$loanController, 'renew']);
$router->post('/reservations/create', [$reservationController, 'create']);
$router->post('/reservations/cancel', [$reservationController, 'cancel']);

$router->get('/librarian/loans', [$loanController, 'manage']);
$router->get('/librarian/reservations', [$reservationController, 'queue']);
$router->post('/librarian/reservations/expire', [$reservationController, 'expireCheck']);
$router->get('/librarian/books', [$bookController, 'manage']);
$router->post('/librarian/books/store', [$bookController, 'store']);
$router->post('/librarian/books/add-copy', [$bookController, 'addCopy']);
$router->post('/librarian/books/delete', [$bookController, 'delete']);
$router->get('/librarian/fines', [$fineController, 'manage']);
$router->post('/librarian/fines/pay', [$fineController, 'pay']);
$router->get('/librarian/reports', [$reportController, 'index']);

$router->get('/admin', [$adminController, 'index']);
$router->post('/admin/users/update', [$adminController, 'updateUser']);
$router->post('/admin/users/create', [$adminController, 'createUser']);
$router->post('/admin/settings', [$adminController, 'updateSettings']);
$router->post('/admin/backup', [$adminController, 'backup']);
$router->post('/admin/reset-demo', [$adminController, 'resetDemo']);

$route = (string) ($_GET['r'] ?? '');
if ($route === '') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    if ($scriptDir !== '' && str_starts_with($uriPath, $scriptDir)) {
        $uriPath = substr($uriPath, strlen($scriptDir)) ?: '/';
    }
    if ($uriPath === '/index.php') {
        $uriPath = '/';
    }
    $route = $uriPath;
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $route);