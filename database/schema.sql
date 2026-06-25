-- database/schema.sql
-- DDL untuk Sistem Helpdesk Tiket IT
-- Jalankan file ini di phpMyAdmin / MySQL CLI untuk membuat seluruh tabel

CREATE DATABASE IF NOT EXISTS helpdesk_tiket_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helpdesk_tiket_it;

-- =========================
-- Tabel: roles
-- =========================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (id, nama) VALUES
    (1, 'user'),
    (2, 'teknisi'),
    (3, 'admin');

-- =========================
-- Tabel: users
-- =========================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    foto_profil VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- =========================
-- Tabel: kategori
-- =========================
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO kategori (nama) VALUES
    ('Hardware'), ('Software'), ('Jaringan'), ('Akun & Akses'), ('Lainnya');

-- =========================
-- Tabel: tiket
-- =========================
CREATE TABLE tiket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    teknisi_id INT DEFAULT NULL,
    kategori_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    prioritas ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low',
    status ENUM('open', 'in_progress', 'resolved', 'closed', 'reopened') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (teknisi_id) REFERENCES users(id),
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
) ENGINE=InnoDB;

-- =========================
-- Tabel: komentar
-- =========================
CREATE TABLE komentar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tiket_id INT NOT NULL,
    user_id INT NOT NULL,
    isi TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tiket_id) REFERENCES tiket(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- =========================
-- Tabel: lampiran
-- =========================
CREATE TABLE lampiran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tiket_id INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    path_file VARCHAR(255) NOT NULL,
    tipe_file VARCHAR(20) NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tiket_id) REFERENCES tiket(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Tabel: notifikasi
-- =========================
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tiket_id INT NOT NULL,
    pesan VARCHAR(255) NOT NULL,
    sudah_dibaca BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tiket_id) REFERENCES tiket(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Akun admin default (password: admin1234)
-- Hash di bawah dibuat dengan password_hash() PHP — ganti setelah login pertama
-- =========================
INSERT INTO users (role_id, nama, email, password) VALUES
    (3, 'Admin Utama', 'admin@helpdesk.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
