<?php
require_once __DIR__ . '/../models/Ticket.php';

class AdminController
{
    private $db;
    private $ticketModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ticketModel = new Ticket($db);
    }

    public function dashboard()
    {
        $statusCounts = $this->ticketModel->countByStatus();
        require __DIR__ . '/../views/admin/dashboard.php';
    }
}
