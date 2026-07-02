<?php

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
            header('Location: ' . BASE_URL . 'index.php?page=user_dashboard&created=1');
            exit;
        }

        $error = "Gagal membuat tiket. Silakan coba lagi.";
        require __DIR__ . '/../views/user/create_ticket.php';
    }

    public function myTickets()
    {
        $tickets = $this->ticketModel->getByUser($_SESSION['user_id']);
        require __DIR__ . '/../views/user/my_tickets.php';
    }

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

        $this->ticketModel->updateStatus($id, $status);
        header('Location: ' . BASE_URL . 'index.php?page=ticket_detail&id=' . $id);
        exit;
    }

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
        header('Location: ' . BASE_URL . 'index.php?page=admin_all_tickets&assigned=1');
        exit;
    }
}
