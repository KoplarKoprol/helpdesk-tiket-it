<?php
// controllers/TicketController.php
// Menangani CRUD tiket, assign teknisi, filter, dan update status

require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Validator.php';

class TicketController
{
    private $db;
    private $ticketModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ticketModel = new Ticket($db);
    }

    // Form buat tiket baru (role: user)
    public function showCreateForm()
    {
        require __DIR__ . '/../views/user/create_ticket.php';
    }

    public function create()
    {
        $judul = Validator::sanitize($_POST['judul'] ?? '');
        $deskripsi = Validator::sanitize($_POST['deskripsi'] ?? '');
        $kategoriId = (int) ($_POST['kategori_id'] ?? 0);
        $prioritas = Validator::sanitize($_POST['prioritas'] ?? 'low');

        if (!Validator::isNotEmpty($judul) || !Validator::isNotEmpty($deskripsi) || $kategoriId <= 0) {
            $error = "Pastikan semua field wajib diisi.";
            require __DIR__ . '/../views/user/create_ticket.php';
            return;
        }

        $success = $this->ticketModel->create([
            'user_id' => $_SESSION['user_id'],
            'kategori_id' => $kategoriId,
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'prioritas' => $prioritas
        ]);

        if ($success) {
            $ticketId = (int) $this->db->lastInsertId();
            $userModel = new User($this->db);
            $user = $userModel->findById($_SESSION['user_id']);
            $ticket = $this->ticketModel->findById($ticketId);

            if ($user && $ticket) {
                require_once __DIR__ . '/../helpers/Mailer.php';
                Mailer::sendTicketCreated($user['email'], $user['nama'], [
                    'id' => $ticketId,
                    'subject' => $ticket['judul'],
                    'priority' => $ticket['prioritas'],
                    'category' => $ticket['kategori_nama'] ?? 'Lainnya'
                ]);

                // Kirim notifikasi in-app ke semua Admin
                $stmt = $this->db->prepare("SELECT id FROM users WHERE role_id = 3");
                $stmt->execute();
                $admins = $stmt->fetchAll();

                require_once __DIR__ . '/../models/Notification.php';
                $notifModel = new Notification();
                foreach ($admins as $admin) {
                    $notifModel->create(
                        $admin['id'],
                        $ticketId,
                        'ticket_created',
                        "Tiket baru #" . $ticketId . " telah dibuat oleh " . $user['nama']
                    );
                }
            }

            header('Location: ' . BASE_URL . 'index.php?page=user_dashboard&created=1');
            exit;
        }

        $error = "Gagal membuat tiket. Silakan coba lagi.";
        require __DIR__ . '/../views/user/create_ticket.php';
    }

    // Daftar tiket milik user yang login
    public function myTickets()
    {
        $tickets = $this->ticketModel->getByUser($_SESSION['user_id']);
        require __DIR__ . '/../views/user/my_tickets.php';
    }

    // Detail tiket (dipakai oleh user, teknisi, dan admin — view berbeda per role)
    public function detail()
    {
        $id = (int) ($_GET['id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            http_response_code(404);
            echo "Tiket tidak ditemukan.";
            return;
        }

        require __DIR__ . '/../views/user/ticket_detail.php';
    }

    // Daftar tiket yang ditugaskan ke teknisi yang login
    public function myAssignedTickets()
    {
        $tickets = $this->ticketModel->getByTeknisi($_SESSION['user_id']);
        require __DIR__ . '/../views/teknisi/assigned_tickets.php';
    }

    public function updateStatus()
    {
        $id = (int) ($_POST['ticket_id'] ?? 0);
        $status = Validator::sanitize($_POST['status'] ?? '');

        $allowedStatus = ['open', 'in_progress', 'resolved', 'closed', 'reopened'];
        if (!in_array($status, $allowedStatus)) {
            http_response_code(400);
            echo "Status tidak valid.";
            return;
        }

        $ticket = $this->ticketModel->findById($id);
        if ($ticket) {
            $oldStatus = $ticket['status'];
            if ($oldStatus !== $status) {
                $this->ticketModel->updateStatus($id, $status);

                $userModel = new User($this->db);
                $owner = $userModel->findById($ticket['user_id']);
                if ($owner) {
                    require_once __DIR__ . '/../helpers/Mailer.php';
                    Mailer::sendStatusChanged($owner['email'], $owner['nama'], [
                        'id' => $ticket['id'],
                        'subject' => $ticket['judul']
                    ], $oldStatus, $status);

                    require_once __DIR__ . '/../models/Notification.php';
                    $notifModel = new Notification();
                    $notifModel->create(
                        $ticket['user_id'],
                        $id,
                        'status_change',
                        "Status tiket #" . $id . " diubah dari " . $oldStatus . " menjadi " . $status
                    );
                }
            }
        }

        header('Location: ' . BASE_URL . 'index.php?page=ticket_detail&id=' . $id);
        exit;
    }

    // Admin: lihat semua tiket dengan filter
    public function allTickets()
    {
        $filters = [
            'status' => Validator::sanitize($_GET['status'] ?? ''),
            'kategori_id' => (int) ($_GET['kategori_id'] ?? 0),
            'prioritas' => Validator::sanitize($_GET['prioritas'] ?? ''),
        ];

        $tickets = $this->ticketModel->getAll($filters);
        require __DIR__ . '/../views/admin/all_tickets.php';
    }

    // Admin: assign tiket ke teknisi
    public function assignTeknisi()
    {
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $teknisiId = (int) ($_POST['teknisi_id'] ?? 0);

        if ($ticketId <= 0 || $teknisiId <= 0) {
            http_response_code(400);
            echo "Data tidak valid.";
            return;
        }

        $this->ticketModel->assignTeknisi($ticketId, $teknisiId);

        $userModel = new User($this->db);
        $teknisi = $userModel->findById($teknisiId);
        $ticket = $this->ticketModel->findById($ticketId);

        if ($teknisi && $ticket) {
            require_once __DIR__ . '/../helpers/Mailer.php';
            Mailer::sendTicketAssigned($teknisi['email'], $teknisi['nama'], [
                'id' => $ticket['id'],
                'subject' => $ticket['judul'],
                'priority' => $ticket['prioritas'],
                'category' => $ticket['kategori_nama'] ?? 'Lainnya'
            ]);

            require_once __DIR__ . '/../models/Notification.php';
            $notifModel = new Notification();
            $notifModel->create(
                $teknisiId,
                $ticketId,
                'assign',
                "Tiket #" . $ticketId . " telah ditugaskan kepada Anda: " . $ticket['judul']
            );
        }

        header('Location: ' . BASE_URL . 'index.php?page=admin_all_tickets&assigned=1');
        exit;
    }
}
