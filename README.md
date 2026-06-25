# Helpdesk Tiket IT

Sistem helpdesk berbasis web untuk pelaporan dan penanganan masalah teknis (IT Support Ticketing System), dibangun dengan **Native PHP + MySQL**.

## Fitur

- Autentikasi & RBAC (3 role: User, Teknisi, Admin)
- CRUD tiket dengan alur status: `open → in_progress → resolved → closed` (bisa `reopened`)
- Penugasan tiket ke teknisi oleh admin
- Komentar/diskusi dalam tiket
- Upload lampiran
- Notifikasi email (PHPMailer)
- Dashboard statistik admin
- Export laporan PDF/Excel
- Filter & pencarian tiket

## Tech Stack

- PHP Native (tanpa framework)
- MySQL (PDO)
- Arsitektur: MVC sederhana (Model–View–Controller manual)
- Library eksternal: PHPMailer, (opsional: Dompdf, PhpSpreadsheet)

## Struktur Folder

```
helpdesk-tiket-it/
├── config/             → konfigurasi database & aplikasi
├── controllers/        → logika aplikasi (AuthController, TicketController, dst)
├── models/             → representasi tabel database (User, Ticket, Comment)
├── views/              → tampilan HTML per role (auth, user, teknisi, admin)
│   └── layout/         → header & footer reusable
├── middleware/          → RoleMiddleware untuk RBAC
├── helpers/            → fungsi bantu (Validator untuk sanitasi input)
├── public/
│   ├── css/, js/        → asset front-end
│   └── uploads/lampiran/ → tempat file lampiran tersimpan
├── database/
│   └── schema.sql       → DDL lengkap untuk membuat database
└── index.php            → front controller / routing utama
```

## Instalasi (XAMPP)

1. Clone repository ini ke dalam folder `htdocs`:
   ```bash
   git clone <url-repo> helpdesk-tiket-it
   ```
2. Jalankan Apache & MySQL melalui XAMPP Control Panel.
3. Buka phpMyAdmin, import `database/schema.sql` untuk membuat database & tabel.
4. Sesuaikan kredensial database di `config/database.php` jika perlu.
5. Sesuaikan kredensial SMTP di `config/config.php` untuk fitur notifikasi email (gunakan Google App Password, bukan password akun biasa).
6. Install PHPMailer via Composer:
   ```bash
   composer require phpmailer/phpmailer
   ```
7. Akses aplikasi melalui `http://localhost/helpdesk-tiket-it/`.

## Akun Default

| Role  | Email                  | Password   |
|-------|-------------------------|------------|
| Admin | admin@helpdesk.test     | admin1234  |

> Ganti password ini setelah login pertama kali.

## Alur Kerja Tiket

```
User membuat tiket → Admin assign ke teknisi → Teknisi proses (in_progress)
→ Teknisi selesaikan (resolved) → User konfirmasi (closed)
→ Jika belum selesai, user bisa reopen tiket
```

## Kontribusi Tim (4 Anggota)

| Anggota | Tanggung Jawab |
|---------|-----------------|
| Anggota 1 | Autentikasi & RBAC |
| Anggota 2 | Modul Tiket Inti (CRUD, status, assign) |
| Anggota 3 | Komentar, Lampiran, Notifikasi Email |
| Anggota 4 | Dashboard, Laporan, UI/UX, Dokumentasi |

## Catatan Keamanan

- Password di-hash menggunakan `password_hash()` (bcrypt)
- Semua query database menggunakan PDO prepared statements (mencegah SQL Injection)
- Semua input disanitasi melalui kelas `Validator` (mencegah XSS)
- Akses halaman dibatasi melalui `RoleMiddleware` sesuai role pengguna
