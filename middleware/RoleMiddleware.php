<?php
// middleware/RoleMiddleware.php
// Middleware sederhana untuk autentikasi dan otorisasi berbasis role (RBAC)

class RoleMiddleware
{
    /**
     * Memastikan user sudah login.
     * Jika belum, redirect ke halaman login.
     */
    public static function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
    }

    /**
     * Memastikan user memiliki salah satu role yang diizinkan.
     * Contoh penggunaan: RoleMiddleware::requireRole(['admin']);
     *
     * @param array $allowedRoles Daftar role yang diizinkan, contoh: ['admin', 'teknisi']
     */
    public static function requireRole(array $allowedRoles)
    {
        self::requireLogin();

        $userRole = $_SESSION['role'] ?? null;

        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            echo "Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.";
            exit;
        }
    }
}
