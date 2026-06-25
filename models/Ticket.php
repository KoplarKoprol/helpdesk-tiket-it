<?php
// models/Ticket.php
// Model untuk entitas tiket — CRUD lengkap dengan prepared statements

class Ticket
{
    private $conn;
    private $table = 'tiket';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create(array $data): bool
    {
        $query = "INSERT INTO {$this->table}
                  (user_id, kategori_id, judul, deskripsi, prioritas, status, created_at, updated_at)
                  VALUES (:user_id, :kategori_id, :judul, :deskripsi, :prioritas, 'open', NOW(), NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':kategori_id', $data['kategori_id'], PDO::PARAM_INT);
        $stmt->bindParam(':judul', $data['judul']);
        $stmt->bindParam(':deskripsi', $data['deskripsi']);
        $stmt->bindParam(':prioritas', $data['prioritas']);
        return $stmt->execute();
    }

    public function findById(int $id)
    {
        $query = "SELECT t.*, k.nama AS kategori_nama, u.nama AS pembuat_nama, te.nama AS teknisi_nama
                  FROM {$this->table} t
                  LEFT JOIN kategori k ON t.kategori_id = k.id
                  LEFT JOIN users u ON t.user_id = u.id
                  LEFT JOIN users te ON t.teknisi_id = te.id
                  WHERE t.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByUser(int $userId)
    {
        $query = "SELECT t.*, k.nama AS kategori_nama
                  FROM {$this->table} t
                  LEFT JOIN kategori k ON t.kategori_id = k.id
                  WHERE t.user_id = :user_id
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByTeknisi(int $teknisiId)
    {
        $query = "SELECT t.*, k.nama AS kategori_nama
                  FROM {$this->table} t
                  LEFT JOIN kategori k ON t.kategori_id = k.id
                  WHERE t.teknisi_id = :teknisi_id
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teknisi_id', $teknisiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mengambil semua tiket dengan filter opsional (untuk admin).
     * $filters bisa berisi: status, kategori_id, prioritas
     */
    public function getAll(array $filters = [])
    {
        $query = "SELECT t.*, k.nama AS kategori_nama, u.nama AS pembuat_nama, te.nama AS teknisi_nama
                  FROM {$this->table} t
                  LEFT JOIN kategori k ON t.kategori_id = k.id
                  LEFT JOIN users u ON t.user_id = u.id
                  LEFT JOIN users te ON t.teknisi_id = te.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['kategori_id'])) {
            $query .= " AND t.kategori_id = :kategori_id";
            $params[':kategori_id'] = $filters['kategori_id'];
        }
        if (!empty($filters['prioritas'])) {
            $query .= " AND t.prioritas = :prioritas";
            $params[':prioritas'] = $filters['prioritas'];
        }

        $query .= " ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $query = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function assignTeknisi(int $id, int $teknisiId): bool
    {
        $query = "UPDATE {$this->table}
                  SET teknisi_id = :teknisi_id, status = 'in_progress', updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teknisi_id', $teknisiId, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function countByStatus()
    {
        $query = "SELECT status, COUNT(*) AS jumlah FROM {$this->table} GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
