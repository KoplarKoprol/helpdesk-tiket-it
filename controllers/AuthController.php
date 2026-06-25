<?php
// controllers/AuthController.php
// Menangani login, register, dan logout

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AuthController
{
    private $db;
    private $userModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    public function showLoginForm()
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    public function showRegisterForm()
    {
        require __DIR__ . '/../views/auth/register.php';
    }

    public function login()
    {
        $email = Validator::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!Validator::isValidEmail($email) || !Validator::isNotEmpty($password)) {
            $error = "Email atau password tidak valid.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $error = "Email atau password salah.";
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        // Set session setelah autentikasi berhasil
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $this->mapRoleIdToName($user['role_id']);

        $this->redirectByRole($_SESSION['role']);
    }

    public function register()
    {
        $nama = Validator::sanitize($_POST['nama'] ?? '');
        $email = Validator::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!Validator::isNotEmpty($nama) || !Validator::isValidEmail($email) || !Validator::isValidPassword($password)) {
            $error = "Pastikan semua field diisi dengan benar dan password minimal 8 karakter.";
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            $error = "Email sudah terdaftar.";
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        // role_id 1 = user (default untuk pendaftar baru)
        $success = $this->userModel->create([
            'nama' => $nama,
            'email' => $email,
            'password' => $password,
            'role_id' => 1
        ]);

        if ($success) {
            header('Location: ' . BASE_URL . 'index.php?page=login&registered=1');
            exit;
        } else {
            $error = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
            require __DIR__ . '/../views/auth/register.php';
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'index.php?page=login');
        exit;
    }

    private function mapRoleIdToName(int $roleId): string
    {
        // Sesuaikan dengan isi tabel roles di database
        $map = [1 => 'user', 2 => 'teknisi', 3 => 'admin'];
        return $map[$roleId] ?? 'user';
    }

    private function redirectByRole(string $role)
    {
        switch ($role) {
            case 'admin':
                header('Location: ' . BASE_URL . 'index.php?page=admin_dashboard');
                break;
            case 'teknisi':
                header('Location: ' . BASE_URL . 'index.php?page=teknisi_dashboard');
                break;
            default:
                header('Location: ' . BASE_URL . 'index.php?page=user_dashboard');
        }
        exit;
    }
}
