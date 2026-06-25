<?php
// models/Comment.php
// Model untuk entitas komentar pada tiket

class Comment
{
    private $conn;
    private $table = 'komentar';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create(array $data): bool
    {
        $query = "INSERT INTO {$this->table} (tiket_id, user_id, isi, created_at)
                  VALUES (:tiket_id, :user_id, :isi, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tiket_id', $data['tiket_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':isi', $data['isi']);
        return $stmt->execute();
    }

    public function getByTiket(int $tiketId)
    {
        $query = "SELECT c.*, u.nama AS penulis_nama, u.role_id
                  FROM {$this->table} c
                  LEFT JOIN users u ON c.user_id = u.id
                  WHERE c.tiket_id = :tiket_id
                  ORDER BY c.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tiket_id', $tiketId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
