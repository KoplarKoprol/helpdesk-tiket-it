<?php
// models/User.php
// Model untuk entitas users — menggunakan PDO prepared statements (mencegah SQL Injection)

class User
{
    private $conn;
    private $table = 'users';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function findByEmail(string $email)
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findById(int $id)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create(array $data): bool
    {
        $query = "INSERT INTO {$this->table} (role_id, nama, email, password, created_at)
                  VALUES (:role_id, :nama, :email, :password, NOW())";
        $stmt = $this->conn->prepare($query);

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt->bindParam(':role_id', $data['role_id'], PDO::PARAM_INT);
        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);

        return $stmt->execute();
    }

    public function getAllTeknisi()
    {
        // Asumsi role_id 2 = teknisi (sesuaikan dengan isi tabel roles)
        $query = "SELECT id, nama, email FROM {$this->table} WHERE role_id = 2";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
