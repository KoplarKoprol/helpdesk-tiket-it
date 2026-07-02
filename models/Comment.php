<?php
// models/Comment.php
// Model untuk entitas komentar pada tiket

require_once __DIR__ . '/../config/database.php';

class Comment
{
    private $conn;
    private $table = 'komentar';

    public function __construct($db = null)
    {
        $this->conn = $db ?: getDB();
    }

    /**
     * Membuat komentar baru dan mengembalikan ID komentar.
     */
    public function create(int $tiketId, int $userId, string $isi): int
    {
        $query = "INSERT INTO {$this->table} (tiket_id, user_id, isi, created_at)
                  VALUES (:tiket_id, :user_id, :isi, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tiket_id', $tiketId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':isi', $isi);
        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Mencari 1 komentar berdasarkan ID.
     */
    public function findById(int $id)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Mengambil komentar berdasarkan ID tiket.
     */
    public function getByTicket(int $ticketId)
    {
        $query = "SELECT c.*, u.nama AS penulis_nama, u.role_id
                  FROM {$this->table} c
                  LEFT JOIN users u ON c.user_id = u.id
                  WHERE c.tiket_id = :tiket_id
                  ORDER BY c.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tiket_id', $ticketId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Menghapus komentar.
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
