<?php
// models/Notification.php
// Model untuk tabel notifikasi (in-app)

require_once __DIR__ . '/../config/database.php';

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Buat notifikasi baru.
     */
    public function create(int $userId, ?int $ticketId, string $type, string $message): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifikasi (user_id, tiket_id, tipe, pesan, sudah_dibaca)
             VALUES (:uid, :tid, :type, :msg, 0)'
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':tid'  => $ticketId,
            ':type' => $type,
            ':msg'  => $message,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Ambil semua notifikasi milik user (terbaru dulu).
     */
    public function getByUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, tiket_id AS ticket_id, tipe AS type, pesan AS message, 
                    sudah_dibaca AS is_read, created_at
             FROM notifikasi
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Hitung notifikasi yang belum dibaca milik user.
     */
    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifikasi WHERE user_id = :uid AND sudah_dibaca = 0'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Tandai 1 notifikasi sebagai sudah dibaca.
     */
    public function markRead(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifikasi SET sudah_dibaca = 1 WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca.
     */
    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifikasi SET sudah_dibaca = 1 WHERE user_id = :uid AND sudah_dibaca = 0'
        );
        return $stmt->execute([':uid' => $userId]);
    }

    /**
     * Hapus notifikasi yang sudah lebih dari N hari.
     */
    public function pruneOld(int $days = 30): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notifikasi WHERE created_at < NOW() - INTERVAL :d DAY'
        );
        $stmt->bindValue(':d', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
