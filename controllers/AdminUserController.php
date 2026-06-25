<?php
// controllers/AdminUserController.php
// Menangani CRUD user + ubah role untuk panel admin

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AdminUserController
{
    private $db;
    private $userModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    // Daftar semua user
    public function index()
    {
        $users = $this->userModel->getAll();
        $roles = $this->userModel->getAllRoles();
        require __DIR__ . '/../views/admin/users/index.php';
    }

    // Form tambah user baru
    public function showCreateForm()
    {
        $roles = $this->userModel->getAllRoles();
        require __DIR__ . '/../views/admin/users/create.php';
    }

    // Simpan user baru
    public function store()
    {
        $nama     = Validator::sanitize($_POST['nama'] ?? '');
        $email    = Validator::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId   = (int) ($_POST['role_id'] ?? 1);

        $errors = [];
        if (!Validator::isNotEmpty($nama))         $errors[] = "Nama tidak boleh kosong.";
        if (!Validator::isValidEmail($email))       $errors[] = "Format email tidak valid.";
        if (!Validator::isValidPassword($password)) $errors[] = "Password minimal 8 karakter.";
        if ($roleId <= 0)                           $errors[] = "Role tidak valid.";

        if (!empty($errors)) {
            $roles = $this->userModel->getAllRoles();
            require __DIR__ . '/../views/admin/users/create.php';
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            $errors[] = "Email sudah terdaftar.";
            $roles = $this->userModel->getAllRoles();
            require __DIR__ . '/../views/admin/users/create.php';
            return;
        }

        $success = $this->userModel->create([
            'nama'     => $nama,
            'email'    => $email,
            'password' => $password,
            'role_id'  => $roleId
        ]);

        if ($success) {
            header('Location: ' . BASE_URL . 'index.php?page=admin_users&success=created');
            exit;
        }

        $errors[] = "Gagal menambahkan user. Silakan coba lagi.";
        $roles = $this->userModel->getAllRoles();
        require __DIR__ . '/../views/admin/users/create.php';
    }

    // Form edit user
    public function showEditForm()
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);
        $roles = $this->userModel->getAllRoles();

        if (!$user) {
            http_response_code(404);
            echo "User tidak ditemukan.";
            return;
        }

        require __DIR__ . '/../views/admin/users/edit.php';
    }

    // Update data user (nama, email, role) — password hanya diupdate jika diisi
    public function update()
    {
        $id     = (int) ($_POST['user_id'] ?? 0);
        $nama   = Validator::sanitize($_POST['nama'] ?? '');
        $email  = Validator::sanitize($_POST['email'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 1);
        $password = $_POST['password'] ?? '';

        $errors = [];
        if (!Validator::isNotEmpty($nama))   $errors[] = "Nama tidak boleh kosong.";
        if (!Validator::isValidEmail($email)) $errors[] = "Format email tidak valid.";
        if ($roleId <= 0)                    $errors[] = "Role tidak valid.";

        // Cek email duplikat (kecuali email milik user ini sendiri)
        $existing = $this->userModel->findByEmail($email);
        if ($existing && $existing['id'] != $id) {
            $errors[] = "Email sudah digunakan user lain.";
        }

        if (!empty($errors)) {
            $user  = $this->userModel->findById($id);
            $roles = $this->userModel->getAllRoles();
            require __DIR__ . '/../views/admin/users/edit.php';
            return;
        }

        $data = [
            'id'      => $id,
            'nama'    => $nama,
            'email'   => $email,
            'role_id' => $roleId,
        ];

        // Update password hanya jika field password diisi
        if (!empty($password)) {
            if (!Validator::isValidPassword($password)) {
                $errors[] = "Password baru minimal 8 karakter.";
                $user  = $this->userModel->findById($id);
                $roles = $this->userModel->getAllRoles();
                require __DIR__ . '/../views/admin/users/edit.php';
                return;
            }
            $data['password'] = $password;
        }

        if ($this->userModel->update($data)) {
            header('Location: ' . BASE_URL . 'index.php?page=admin_users&success=updated');
            exit;
        }

        $errors[] = "Gagal memperbarui data user.";
        $user  = $this->userModel->findById($id);
        $roles = $this->userModel->getAllRoles();
        require __DIR__ . '/../views/admin/users/edit.php';
    }

    // Hapus user
    public function delete()
    {
        $id = (int) ($_POST['user_id'] ?? 0);

        // Tidak boleh hapus diri sendiri
        if ($id === (int) $_SESSION['user_id']) {
            header('Location: ' . BASE_URL . 'index.php?page=admin_users&error=self_delete');
            exit;
        }

        $this->userModel->delete($id);
        header('Location: ' . BASE_URL . 'index.php?page=admin_users&success=deleted');
        exit;
    }
}
