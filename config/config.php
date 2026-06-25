<?php
// config/config.php
// Konfigurasi umum aplikasi

// Mulai session di satu tempat saja agar tidak duplikat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL — sesuaikan dengan folder project di htdocs/XAMPP
define('BASE_URL', 'http://localhost/helpdesk-tiket-it/');

// Path absolut folder upload lampiran
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/lampiran/');

// Pengaturan SMTP untuk PHPMailer (isi sesuai akun Google App Password)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'email_kamu@gmail.com');
define('SMTP_PASSWORD', 'app_password_google');
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'Helpdesk Tiket IT');

// Tampilkan error saat development (matikan saat production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
