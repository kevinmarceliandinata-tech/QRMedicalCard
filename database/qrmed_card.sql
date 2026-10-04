-- =========================================================
-- QRMed Card - Database Schema
-- Kartu Informasi Kesehatan Darurat Berbasis QR Code
-- =========================================================

CREATE DATABASE IF NOT EXISTS qrmed_card CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qrmed_card;

-- ---------------------------------------------------------
-- Tabel users : akun login (admin & pasien)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin','patient') NOT NULL DEFAULT 'patient',
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel patients : data identitas pasien
-- ---------------------------------------------------------
CREATE TABLE patients (
    patient_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(20) NOT NULL UNIQUE,      -- kode publik, mis. QRM-000001
    user_id INT UNSIGNED NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    golongan_darah VARCHAR(3) DEFAULT NULL,        -- A, B, AB, O, A+, dst
    gol_darah_verified TINYINT(1) NOT NULL DEFAULT 0,
    nik VARCHAR(20) DEFAULT NULL,                  -- data privat, TIDAK ditampilkan di emergency
    alamat TEXT DEFAULT NULL,                      -- data privat, TIDAK ditampilkan di emergency
    catatan_khusus TEXT DEFAULT NULL,               -- kondisi khusus penting saat emergency
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_patients_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel emergency_contacts : kontak darurat (bisa lebih dari satu)
-- ---------------------------------------------------------
CREATE TABLE emergency_contacts (
    contact_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    nama_kontak VARCHAR(150) NOT NULL,
    hubungan VARCHAR(50) DEFAULT NULL,             -- Ayah, Ibu, Saudara, dll
    no_telepon VARCHAR(20) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contacts_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel disease_history : riwayat / penyakit yang sedang dialami
-- ---------------------------------------------------------
CREATE TABLE disease_history (
    disease_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    nama_penyakit VARCHAR(150) NOT NULL,
    tanggal_diagnosis DATE DEFAULT NULL,
    status ENUM('aktif','tidak aktif') NOT NULL DEFAULT 'aktif',
    catatan TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_disease_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel allergies : alergi obat/makanan
-- ---------------------------------------------------------
CREATE TABLE allergies (
    allergy_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    jenis_alergi VARCHAR(150) NOT NULL,
    keterangan TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_allergy_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel medications : obat yang sedang digunakan
-- ---------------------------------------------------------
CREATE TABLE medications (
    medication_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    nama_obat VARCHAR(150) NOT NULL,
    dosis VARCHAR(50) DEFAULT NULL,
    frekuensi VARCHAR(100) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medication_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel qr_codes : token dinamis QR (1 pasien = 1 QR aktif)
-- ---------------------------------------------------------
CREATE TABLE qr_codes (
    qr_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    unique_token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    qr_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_qr_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    UNIQUE KEY uq_qr_patient (patient_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel access_logs : log setiap kali QR/emergency page diakses (opsional, untuk keamanan)
-- ---------------------------------------------------------
CREATE TABLE access_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Seed data awal
-- =========================================================

-- Admin default -> username: admin | password: admin123
INSERT INTO users (role, username, email, password, status) VALUES
('admin', 'admin', 'admin@qrmedcard.local', '$2y$10$qx9puezCszoXsLrcxmOQYen2nGpwHHV.QZZ70lzexoT5QFe2A.bbK', 'active');

-- =========================================================
-- Catatan hash password di atas adalah untuk 'admin123'
-- (bcrypt, dibuat dengan password_hash() PHP)
-- =========================================================
