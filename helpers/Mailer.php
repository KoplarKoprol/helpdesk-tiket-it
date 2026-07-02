<?php
// helpers/Mailer.php
// Wrapper PHPMailer untuk notifikasi email otomatis

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class Mailer
{
    /**
     * Membuat instance PHPMailer yang sudah dikonfigurasi.
     */
    private static function buildMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host        = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth    = true;
        $mail->Username    = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password    = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $mail->CharSet     = 'UTF-8';

        $senderName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Helpdesk IT';
        $mail->setFrom($mail->Username, $senderName);

        return $mail;
    }

    /**
     * Kirim notifikasi saat tiket baru dibuat.
     */
    public static function sendTicketCreated(
        string $toEmail,
        string $toName,
        array  $ticket
    ): bool {
        try {
            $mail = self::buildMailer();
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "[Helpdesk] Tiket Baru Berhasil Dibuat: #{$ticket['id']}";
            $mail->Body    = self::templateTicketCreated($ticket, $toName);
            $mail->AltBody = strip_tags($mail->Body);

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('Mailer Error (ticketCreated): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notifikasi saat komentar baru ditambahkan ke tiket.
     *
     * @param string $toEmail   Email penerima
     * @param string $toName    Nama penerima
     * @param array  $ticket    Data tiket ['id', 'subject']
     * @param array  $commenter Data pemberi komentar ['name']
     * @param string $body      Isi komentar
     */
    public static function sendNewCommentNotification(
        string $toEmail,
        string $toName,
        array  $ticket,
        array  $commenter,
        string $body
    ): bool {
        try {
            $mail = self::buildMailer();
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "[Tiket #{$ticket['id']}] Komentar Baru: {$ticket['subject']}";
            $mail->Body    = self::templateNewComment($ticket, $commenter, $body);
            $mail->AltBody = strip_tags($mail->Body);

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('Mailer Error (newComment): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notifikasi penugasan tiket ke teknisi.
     *
     * @param string $toEmail Email teknisi
     * @param string $toName  Nama teknisi
     * @param array  $ticket  Data tiket ['id', 'subject', 'priority', 'category']
     */
    public static function sendTicketAssigned(
        string $toEmail,
        string $toName,
        array  $ticket
    ): bool {
        try {
            $mail = self::buildMailer();
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "[Helpdesk] Tiket Baru Ditugaskan: #{$ticket['id']}";
            $mail->Body    = self::templateAssigned($ticket, $toName);
            $mail->AltBody = strip_tags($mail->Body);

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('Mailer Error (assigned): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notifikasi perubahan status tiket ke pelapor.
     *
     * @param string $toEmail   Email pelapor
     * @param string $toName    Nama pelapor
     * @param array  $ticket    Data tiket ['id', 'subject']
     * @param string $oldStatus Status sebelumnya
     * @param string $newStatus Status baru
     */
    public static function sendStatusChanged(
        string $toEmail,
        string $toName,
        array  $ticket,
        string $oldStatus,
        string $newStatus
    ): bool {
        try {
            $mail = self::buildMailer();
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "[Tiket #{$ticket['id']}] Status Diperbarui: {$newStatus}";
            $mail->Body    = self::templateStatusChanged($ticket, $toName, $oldStatus, $newStatus);
            $mail->AltBody = strip_tags($mail->Body);

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('Mailer Error (statusChanged): ' . $e->getMessage());
            return false;
        }
    }

    // ── Template Email ───────────────────────────────────────────────────

    private static function templateTicketCreated(array $ticket, string $userName): string
    {
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">
          <h2 style="background:#2563eb;color:#fff;padding:16px;border-radius:8px 8px 0 0;margin:0;">
            🎫 Tiket Baru Berhasil Dibuat
          </h2>
          <div style="border:1px solid #e5e7eb;border-top:none;padding:20px;border-radius:0 0 8px 8px;">
            <p>Halo <strong>{$userName}</strong>,</p>
            <p>Tiket Anda telah berhasil dibuat di sistem Helpdesk IT:</p>
            <table style="width:100%;border-collapse:collapse;">
              <tr><td style="padding:6px;font-weight:bold;width:120px;">ID Tiket</td><td>#{$ticket['id']}</td></tr>
              <tr style="background:#f9fafb;"><td style="padding:6px;font-weight:bold;">Judul</td><td>{$ticket['subject']}</td></tr>
              <tr><td style="padding:6px;font-weight:bold;">Kategori</td><td>{$ticket['category']}</td></tr>
              <tr style="background:#f9fafb;"><td style="padding:6px;font-weight:bold;">Prioritas</td><td>{$ticket['priority']}</td></tr>
            </table>
            <br>
            <a href="http://localhost/helpdesk-tiket-it/index.php?page=ticket_detail&id={$ticket['id']}"
               style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
              Lihat Detail Tiket
            </a>
          </div>
          <p style="color:#9ca3af;font-size:12px;text-align:center;margin-top:12px;">
            Email ini dikirim otomatis oleh sistem Helpdesk IT. Jangan balas email ini.
          </p>
        </div>
        HTML;
    }

    private static function templateNewComment(array $ticket, array $commenter, string $body): string
    {
        $body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">
          <h2 style="background:#2563eb;color:#fff;padding:16px;border-radius:8px 8px 0 0;margin:0;">
            💬 Komentar Baru pada Tiket #{$ticket['id']}
          </h2>
          <div style="border:1px solid #e5e7eb;border-top:none;padding:20px;border-radius:0 0 8px 8px;">
            <p><strong>Tiket:</strong> {$ticket['subject']}</p>
            <p><strong>Dari:</strong> {$commenter['name']}</p>
            <hr>
            <p><strong>Komentar:</strong></p>
            <blockquote style="background:#f3f4f6;padding:12px;border-left:4px solid #2563eb;border-radius:4px;">
              {$body}
            </blockquote>
            <br>
            <a href="http://localhost/helpdesk-tiket-it/index.php?page=ticket_detail&id={$ticket['id']}"
               style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
              Lihat Tiket
            </a>
          </div>
          <p style="color:#9ca3af;font-size:12px;text-align:center;margin-top:12px;">
            Email ini dikirim otomatis oleh sistem Helpdesk IT. Jangan balas email ini.
          </p>
        </div>
        HTML;
    }

    private static function templateAssigned(array $ticket, string $techName): string
    {
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">
          <h2 style="background:#16a34a;color:#fff;padding:16px;border-radius:8px 8px 0 0;margin:0;">
            📋 Tiket Baru Ditugaskan kepada Anda
          </h2>
          <div style="border:1px solid #e5e7eb;border-top:none;padding:20px;border-radius:0 0 8px 8px;">
            <p>Halo <strong>{$techName}</strong>,</p>
            <p>Sebuah tiket baru telah ditugaskan kepada Anda:</p>
            <table style="width:100%;border-collapse:collapse;">
              <tr><td style="padding:6px;font-weight:bold;width:120px;">ID Tiket</td><td>#{$ticket['id']}</td></tr>
              <tr style="background:#f9fafb;"><td style="padding:6px;font-weight:bold;">Judul</td><td>{$ticket['subject']}</td></tr>
              <tr><td style="padding:6px;font-weight:bold;">Kategori</td><td>{$ticket['category']}</td></tr>
              <tr style="background:#f9fafb;"><td style="padding:6px;font-weight:bold;">Prioritas</td><td>{$ticket['priority']}</td></tr>
            </table>
            <br>
            <a href="http://localhost/helpdesk-tiket-it/index.php?page=ticket_detail&id={$ticket['id']}"
               style="background:#16a34a;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
              Buka Tiket
            </a>
          </div>
        </div>
        HTML;
    }

    private static function templateStatusChanged(array $ticket, string $userName, string $old, string $new): string
    {
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">
          <h2 style="background:#d97706;color:#fff;padding:16px;border-radius:8px 8px 0 0;margin:0;">
            🔄 Status Tiket Diperbarui
          </h2>
          <div style="border:1px solid #e5e7eb;border-top:none;padding:20px;border-radius:0 0 8px 8px;">
            <p>Halo <strong>{$userName}</strong>,</p>
            <p>Status tiket Anda telah diperbarui:</p>
            <table style="width:100%;border-collapse:collapse;">
              <tr><td style="padding:6px;font-weight:bold;width:120px;">ID Tiket</td><td>#{$ticket['id']}</td></tr>
              <tr style="background:#f9fafb;"><td style="padding:6px;font-weight:bold;">Judul</td><td>{$ticket['subject']}</td></tr>
              <tr><td style="padding:6px;font-weight:bold;">Status Lama</td><td><span style="color:#6b7280;">{$old}</span></td></tr>
              <tr style="background:#f9fafb;"><td style="padding:6px;font-weight:bold;">Status Baru</td>
                <td><strong style="color:#16a34a;">{$new}</strong></td></tr>
            </table>
            <br>
            <a href="http://localhost/helpdesk-tiket-it/index.php?page=ticket_detail&id={$ticket['id']}"
               style="background:#d97706;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
              Lihat Detail Tiket
            </a>
          </div>
        </div>
        HTML;
    }
}
