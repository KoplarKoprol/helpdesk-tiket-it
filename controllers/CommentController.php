<?php
// controllers/CommentController.php
// Anggota 3 — Komentar, Lampiran & Notifikasi

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/Mailer.php';

class CommentController
{
    private Comment      $commentModel;
    private Attachment   $attachmentModel;
    private Notification $notifModel;

    // Direktori penyimpanan fisik lampiran
    private const UPLOAD_DIR = __DIR__ . '/../public/uploads/lampiran/';

    public function __construct()
    {
        $this->commentModel    = new Comment();
        $this->attachmentModel = new Attachment();
        $this->notifModel      = new Notification();
    }

    // ──────────────────────────────────────────────────────────────────────
    // ROUTER sederhana — dipanggil dari index.php / routes.php
    // Contoh: GET  /comments?ticket_id=5
    //         POST /comments          (body: ticket_id, body, file?)
    //         POST /comments/delete   (body: id)
    //         GET  /attachments/download?id=3
    //         POST /notifications/read (body: id | all=1)
    // ──────────────────────────────────────────────────────────────────────

    public function handleRequest(string $action): void
    {
        $this->requireLogin();

        match ($action) {
            'list'              => $this->listComments(),
            'store'             => $this->store(),
            'delete'            => $this->deleteComment(),
            'download'          => $this->downloadAttachment(),
            'deleteAttachment'  => $this->deleteAttachment(),
            'notifications'     => $this->listNotifications(),
            'markRead'          => $this->markNotificationRead(),
            'markAllRead'       => $this->markAllNotificationsRead(),
            default             => $this->respond(['error' => 'Aksi tidak ditemukan.'], 404),
        };
    }

    // ── Komentar ──────────────────────────────────────────────────────────

    /**
     * GET /comments?ticket_id=X
     * Kembalikan daftar komentar + lampiran tiap komentar.
     */
    private function listComments(): void
    {
        $ticketId = $this->intParam('ticket_id');
        if (!$ticketId) {
            $this->respond(['error' => 'ticket_id diperlukan.'], 422);
            return;
        }

        $comments = $this->commentModel->getByTicket($ticketId);

        // Sertakan lampiran per komentar
        foreach ($comments as &$c) {
            $c['attachments'] = $this->attachmentModel->getByComment((int) $c['id']);
        }

        $this->respond(['comments' => $comments]);
    }

    /**
     * POST /comments
     * Form fields: ticket_id (int), body (string)
     * File upload: attachment[] (opsional, max 5 file)
     */
    private function store(): void
    {
        $this->requireMethod('POST');

        $ticketId = $this->intPost('ticket_id');
        $body     = trim($this->sanitize($_POST['body'] ?? ''));

        // ── Validasi dasar ─────────────────────────────────────────────
        $errors = [];
        if (!$ticketId)          $errors[] = 'ticket_id diperlukan.';
        if ($body === '')        $errors[] = 'Komentar tidak boleh kosong.';
        if (strlen($body) > 5000) $errors[] = 'Komentar terlalu panjang (maks 5000 karakter).';

        if ($errors) {
            $this->respond(['errors' => $errors], 422);
            return;
        }

        // ── Proses lampiran (jika ada) ──────────────────────────────────
        $uploadedFiles = [];
        if (!empty($_FILES['attachment']['name'][0])) {
            $result = $this->handleUploads($ticketId);
            if (isset($result['errors'])) {
                $this->respond(['errors' => $result['errors']], 422);
                return;
            }
            $uploadedFiles = $result['files'];
        }

        // ── Simpan komentar ─────────────────────────────────────────────
        $userId    = $_SESSION['user_id'];
        $commentId = $this->commentModel->create($ticketId, $userId, $body);

        // ── Simpan rekaman lampiran ke DB ───────────────────────────────
        foreach ($uploadedFiles as $f) {
            $this->attachmentModel->create(
                ticketId:     $ticketId,
                commentId:    $commentId,
                uploadedBy:   $userId,
                originalName: $f['original_name'],
                storedName:   $f['stored_name'],
                mimeType:     $f['mime_type'],
                fileSize:     $f['file_size'],
            );
        }

        // ── Notifikasi ke pihak terkait ─────────────────────────────────
        $this->notifyOnComment($ticketId, $commentId, $userId, $body);

        $this->respond([
            'message'    => 'Komentar berhasil ditambahkan.',
            'comment_id' => $commentId,
        ], 201);
    }

    /**
     * POST /comments/delete   body: id
     * Hanya pemilik komentar atau admin yang boleh menghapus.
     */
    private function deleteComment(): void
    {
        $this->requireMethod('POST');

        $id      = $this->intPost('id');
        $comment = $this->commentModel->findById($id);

        if (!$comment) {
            $this->respond(['error' => 'Komentar tidak ditemukan.'], 404);
            return;
        }

        $session = $_SESSION;
        $isOwner = $comment['user_id'] === $session['user_id'];
        $isAdmin = $session['role'] === 'admin';

        if (!$isOwner && !$isAdmin) {
            $this->respond(['error' => 'Akses ditolak.'], 403);
            return;
        }

        // Hapus lampiran fisik milik komentar ini
        $attachments = $this->attachmentModel->getByComment($id);
        foreach ($attachments as $a) {
            $path = self::UPLOAD_DIR . $a['stored_name'];
            if (file_exists($path)) unlink($path);
            $this->attachmentModel->delete((int) $a['id']);
        }

        $this->commentModel->delete($id);
        $this->respond(['message' => 'Komentar berhasil dihapus.']);
    }

    // ── Lampiran ──────────────────────────────────────────────────────────

    /**
     * GET /attachments/download?id=X
     * Stream file ke browser dengan header yang benar.
     */
    private function downloadAttachment(): void
    {
        $id         = $this->intParam('id');
        $attachment = $this->attachmentModel->findById($id);

        if (!$attachment) {
            $this->respond(['error' => 'Lampiran tidak ditemukan.'], 404);
            return;
        }

        $path = self::UPLOAD_DIR . $attachment['stored_name'];
        if (!file_exists($path)) {
            $this->respond(['error' => 'File tidak ada di server.'], 404);
            return;
        }

        // Paksa download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: must-revalidate');
        ob_clean();
        flush();
        readfile($path);
        exit;
    }

    /**
     * POST /attachments/delete   body: id
     */
    private function deleteAttachment(): void
    {
        $this->requireMethod('POST');

        $id         = $this->intPost('id');
        $attachment = $this->attachmentModel->findById($id);

        if (!$attachment) {
            $this->respond(['error' => 'Lampiran tidak ditemukan.'], 404);
            return;
        }

        $isOwner = $attachment['uploaded_by'] === $_SESSION['user_id'];
        $isAdmin = $_SESSION['role'] === 'admin';

        if (!$isOwner && !$isAdmin) {
            $this->respond(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $path = self::UPLOAD_DIR . $attachment['stored_name'];
        if (file_exists($path)) unlink($path);

        $this->attachmentModel->delete($id);
        $this->respond(['message' => 'Lampiran berhasil dihapus.']);
    }

    // ── Notifikasi ────────────────────────────────────────────────────────

    /**
     * GET /notifications
     */
    private function listNotifications(): void
    {
        $userId = $_SESSION['user_id'];
        $notifs = $this->notifModel->getByUser($userId);
        $unread = $this->notifModel->countUnread($userId);

        $this->respond(['notifications' => $notifs, 'unread_count' => $unread]);
    }

    /**
     * POST /notifications/read   body: id=X  atau  all=1
     */
    private function markNotificationRead(): void
    {
        $this->requireMethod('POST');
        $userId = $_SESSION['user_id'];

        if (!empty($_POST['all'])) {
            $this->notifModel->markAllRead($userId);
            $this->respond(['message' => 'Semua notifikasi ditandai dibaca.']);
            return;
        }

        $id = $this->intPost('id');
        $this->notifModel->markRead($id);
        $this->respond(['message' => 'Notifikasi ditandai dibaca.']);
    }

    private function markAllNotificationsRead(): void
    {
        $this->notifModel->markAllRead($_SESSION['user_id']);
        $this->respond(['message' => 'Semua notifikasi ditandai dibaca.']);
    }

    // ── Helper Privat ─────────────────────────────────────────────────────

    /**
     * Validasi, simpan file fisik, dan kembalikan metadata.
     *
     * @return array ['files' => [...]] | ['errors' => [...]]
     */
    private function handleUploads(int $ticketId): array
    {
        $files  = $_FILES['attachment'];
        $count  = count($files['name']);
        $errors = [];
        $saved  = [];

        if ($count > 5) {
            return ['errors' => ['Maksimal 5 file per komentar.']];
        }

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "File ke-" . ($i + 1) . " gagal diunggah (kode error: {$files['error'][$i]}).";
                continue;
            }

            $originalName = basename($files['name'][$i]);
            $tmpPath      = $files['tmp_name'][$i];
            $fileSize     = $files['size'][$i];

            // Validasi ukuran
            if ($fileSize > Attachment::MAX_SIZE_BYTES) {
                $errors[] = "File \"{$originalName}\" melebihi batas 5 MB.";
                continue;
            }

            // Validasi MIME via finfo (lebih aman daripada ekstensi)
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);

            if (!in_array($mimeType, Attachment::ALLOWED_MIME, true)) {
                $errors[] = "Tipe file \"{$originalName}\" tidak diizinkan ({$mimeType}).";
                continue;
            }

            // Nama unik untuk menghindari konflik
            $ext        = pathinfo($originalName, PATHINFO_EXTENSION);
            $storedName = sprintf('%s_%s.%s', $ticketId, bin2hex(random_bytes(8)), $ext);

            if (!is_dir(self::UPLOAD_DIR)) {
                mkdir(self::UPLOAD_DIR, 0755, true);
            }

            if (!move_uploaded_file($tmpPath, self::UPLOAD_DIR . $storedName)) {
                $errors[] = "Gagal menyimpan file \"{$originalName}\" ke server.";
                continue;
            }

            $saved[] = [
                'original_name' => $originalName,
                'stored_name'   => $storedName,
                'mime_type'     => $mimeType,
                'file_size'     => $fileSize,
            ];
        }

        if ($errors) return ['errors' => $errors];
        return ['files' => $saved];
    }

    /**
     * Kirim notifikasi in-app + email ke semua pihak terkait tiket.
     */
    private function notifyOnComment(int $ticketId, int $commentId, int $commenterId, string $body): void
    {
        $db = getDB();

        // Ambil data tiket + pemilik + teknisi
        $stmt = $db->prepare(
            'SELECT t.*, 
                    u_owner.id    AS owner_id,    u_owner.name    AS owner_name,    u_owner.email    AS owner_email,
                    u_tech.id     AS tech_id,     u_tech.name     AS tech_name,     u_tech.email     AS tech_email
             FROM tickets t
             JOIN users u_owner ON u_owner.id = t.user_id
             LEFT JOIN users u_tech ON u_tech.id = t.assigned_to
             WHERE t.id = :tid'
        );
        $stmt->execute([':tid' => $ticketId]);
        $ticket = $stmt->fetch();

        if (!$ticket) return;

        // Data commenter
        $stmtC    = $db->prepare('SELECT name FROM users WHERE id = :id');
        $stmtC->execute([':id' => $commenterId]);
        $commenter = $stmtC->fetch();

        $message      = "Komentar baru pada tiket #{$ticketId}: {$ticket['subject']}";
        $ticketArr    = ['id' => $ticketId, 'subject' => $ticket['subject']];
        $commenterArr = ['name' => $commenter['name'] ?? 'Pengguna'];

        // Notifikasi ke pemilik tiket (kecuali dia sendiri yang komentar)
        if ($ticket['owner_id'] && $ticket['owner_id'] !== $commenterId) {
            $this->notifModel->create($ticket['owner_id'], $ticketId, 'comment', $message);
            Mailer::sendNewCommentNotification(
                $ticket['owner_email'],
                $ticket['owner_name'],
                $ticketArr,
                $commenterArr,
                $body
            );
        }

        // Notifikasi ke teknisi (jika ada & bukan commenter)
        if ($ticket['tech_id'] && $ticket['tech_id'] !== $commenterId) {
            $this->notifModel->create($ticket['tech_id'], $ticketId, 'comment', $message);
            Mailer::sendNewCommentNotification(
                $ticket['tech_email'],
                $ticket['tech_name'],
                $ticketArr,
                $commenterArr,
                $body
            );
        }
    }

    // ── Utility ───────────────────────────────────────────────────────────

    private function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    private function intParam(string $key): int
    {
        return (int) filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    }

    private function intPost(string $key): int
    {
        return (int) filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    }

    private function respond(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            $this->respond(['error' => 'Silakan login terlebih dahulu.'], 401);
        }
    }

    private function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->respond(['error' => 'Method tidak diizinkan.'], 405);
        }
    }
}
