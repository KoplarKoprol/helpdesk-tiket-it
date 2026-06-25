<?php
// index.php
// Front controller — semua request masuk lewat sini (routing sederhana via ?page=)

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/TicketController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/AdminUserController.php';

$database = new Database();
$db = $database->connect();

$page = $_GET['page'] ?? 'login';

switch ($page) {

    // ===== AUTH (tidak perlu login) =====
    case 'login':
        (new AuthController($db))->showLoginForm();
        break;

    case 'do_login':
        (new AuthController($db))->login();
        break;

    case 'register':
        (new AuthController($db))->showRegisterForm();
        break;

    case 'do_register':
        (new AuthController($db))->register();
        break;

    case 'logout':
        (new AuthController($db))->logout();
        break;

    // ===== USER (role: user) =====
    case 'user_dashboard':
        RoleMiddleware::requireRole(['user']);
        (new TicketController($db))->myTickets();
        break;

    case 'create_ticket':
        RoleMiddleware::requireRole(['user']);
        (new TicketController($db))->showCreateForm();
        break;

    case 'store_ticket':
        RoleMiddleware::requireRole(['user']);
        (new TicketController($db))->create();
        break;

    // ===== TEKNISI =====
    case 'teknisi_dashboard':
        RoleMiddleware::requireRole(['teknisi']);
        (new TicketController($db))->myAssignedTickets();
        break;

    // ===== ADMIN =====
    case 'admin_dashboard':
        RoleMiddleware::requireRole(['admin']);
        (new AdminController($db))->dashboard();
        break;

    case 'admin_all_tickets':
        RoleMiddleware::requireRole(['admin']);
        (new TicketController($db))->allTickets();
        break;

    case 'assign_teknisi':
        RoleMiddleware::requireRole(['admin']);
        (new TicketController($db))->assignTeknisi();
        break;

    // ===== DIPAKAI BERSAMA (user, teknisi, admin) =====
    case 'ticket_detail':
        RoleMiddleware::requireRole(['user', 'teknisi', 'admin']);
        (new TicketController($db))->detail();
        break;

    case 'update_ticket_status':
        RoleMiddleware::requireRole(['teknisi', 'admin']);
        (new TicketController($db))->updateStatus();
        break;

    // ===== ADMIN: MANAJEMEN USER =====
    case 'admin_users':
        RoleMiddleware::requireRole(['admin']);
        (new AdminUserController($db))->index();
        break;

    case 'admin_user_create':
        RoleMiddleware::requireRole(['admin']);
        (new AdminUserController($db))->showCreateForm();
        break;

    case 'admin_user_store':
        RoleMiddleware::requireRole(['admin']);
        (new AdminUserController($db))->store();
        break;

    case 'admin_user_edit':
        RoleMiddleware::requireRole(['admin']);
        (new AdminUserController($db))->showEditForm();
        break;

    case 'admin_user_update':
        RoleMiddleware::requireRole(['admin']);
        (new AdminUserController($db))->update();
        break;

    case 'admin_user_delete':
        RoleMiddleware::requireRole(['admin']);
        (new AdminUserController($db))->delete();
        break;

    default:
        http_response_code(404);
        echo "Halaman tidak ditemukan.";
}
