<?php
// models/Attachment.php
// Model untuk tabel lampiran

require_once __DIR__ . '/../config/database.php';

class Attachment
{
    private PDO $db;

    // Ekstensi & MIME yang diizinkan
    public const ALLOWED_MIME = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/zip',
    ];

    // Batas ukuran: 5 MB
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Ambil semua lampiran untuk 1 tiket.
     */
    public function getByTicket(int $ticketId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.tiket_id, a.comment_id, a.uploaded_by, 
                    a.nama_file AS original_name, a.path_file AS stored_name, 
                    a.tipe_file AS mime_type, a.ukuran_file AS file_size, a.uploaded_at,
                    u.nama AS uploader_name
             FROM lampiran a
             LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE a.tiket_id = :tid
             ORDER BY a.uploaded_at DESC'
        );
        $stmt->execute([':tid' => $ticketId]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil lampiran milik komentar tertentu.
     */
    public function getByComment(int $commentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tiket_id, comment_id, uploaded_by, 
                    nama_file AS original_name, path_file AS stored_name, 
                    tipe_file AS mime_type, ukuran_file AS file_size, uploaded_at 
             FROM lampiran 
             WHERE comment_id = :cid 
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute([':cid' => $commentId]);
        return $stmt->fetchAll();
    }

    /**
     * Temukan 1 lampiran by id.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT id, tiket_id, comment_id, uploaded_by, 
                    nama_file AS original_name, path_file AS stored_name, 
                    tipe_file AS mime_type, ukuran_file AS file_size, uploaded_at 
             FROM lampiran 
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Simpan rekaman lampiran ke database.
     */
    public function create(
        int    $ticketId,
        ?int   $commentId,
        int    $uploadedBy,
        string $originalName,
        string $storedName,
        string $mimeType,
        int    $fileSize
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO lampiran
                (tiket_id, comment_id, uploaded_by, nama_file, path_file, tipe_file, ukuran_file)
             VALUES
                (:tid, :cid, :uid, :orig, :stored, :mime, :size)'
        );
        $stmt->execute([
            ':tid'    => $ticketId,
            ':cid'    => $commentId,
            ':uid'    => $uploadedBy,
            ':orig'   => $originalName,
            ':stored' => $storedName,
            ':mime'   => $mimeType,
            ':size'   => $fileSize,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Hapus rekaman lampiran dari database.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM lampiran WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
