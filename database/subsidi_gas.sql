-- =====================================================================
-- DATABASE: subsidi_gas
-- Aplikasi: SUBSIDI TEPAT GAS LPG
-- Deskripsi: Skema database untuk pendataan penyaluran subsidi LPG 3kg
-- =====================================================================

CREATE DATABASE IF NOT EXISTS subsidi_gas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE subsidi_gas;

-- ---------------------------------------------------------------------
-- Tabel: users  (akun petugas/admin aplikasi)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin','petugas') NOT NULL DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: agen  (pangkalan / agen resmi penyalur LPG)
-- ---------------------------------------------------------------------
CREATE TABLE agen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_agen VARCHAR(20) NOT NULL UNIQUE,
    nama_agen VARCHAR(150) NOT NULL,
    alamat VARCHAR(255),
    desa_kelurahan VARCHAR(100),
    kecamatan VARCHAR(100),
    kabupaten_kota VARCHAR(100),
    no_hp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: penerima  (data warga / pelaku usaha mikro penerima subsidi)
-- ---------------------------------------------------------------------
CREATE TABLE penerima (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik CHAR(16) NOT NULL UNIQUE,
    no_kk CHAR(16) DEFAULT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL DEFAULT 'L',
    alamat VARCHAR(255) NOT NULL,
    rt VARCHAR(5),
    rw VARCHAR(5),
    desa_kelurahan VARCHAR(100) NOT NULL,
    kecamatan VARCHAR(100) NOT NULL,
    kabupaten_kota VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    kategori ENUM('rumah_tangga','usaha_mikro') NOT NULL DEFAULT 'rumah_tangga',
    status_ekonomi ENUM('miskin','rentan_miskin','umum') DEFAULT 'umum',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    catatan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nik (nik),
    INDEX idx_wilayah (kecamatan, desa_kelurahan)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: transaksi  (catatan setiap transaksi pembelian gas subsidi)
-- ---------------------------------------------------------------------
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(30) NOT NULL UNIQUE,
    penerima_id INT NOT NULL,
    agen_id INT DEFAULT NULL,
    tanggal_transaksi DATE NOT NULL,
    jenis_tabung ENUM('3kg','5.5kg','12kg') NOT NULL DEFAULT '3kg',
    jumlah_tabung INT NOT NULL DEFAULT 1,
    harga_subsidi DECIMAL(12,2) NOT NULL COMMENT 'Harga per tabung yang dibayar warga (harga bersubsidi)',
    harga_normal DECIMAL(12,2) NOT NULL COMMENT 'Harga pasar/non-subsidi per tabung sebagai pembanding',
    total_bayar DECIMAL(12,2) NOT NULL COMMENT 'harga_subsidi x jumlah_tabung',
    nilai_subsidi DECIMAL(12,2) NOT NULL COMMENT '(harga_normal - harga_subsidi) x jumlah_tabung',
    petugas_id INT DEFAULT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penerima_id) REFERENCES penerima(id) ON DELETE CASCADE,
    FOREIGN KEY (agen_id) REFERENCES agen(id) ON DELETE SET NULL,
    FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tanggal (tanggal_transaksi),
    INDEX idx_penerima (penerima_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- View bantu: rekap transaksi per NIK per bulan (dipakai untuk deteksi
-- anomali / potensi penyalahgunaan subsidi pada modul laporan)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_rekap_bulanan AS
SELECT
    p.id AS penerima_id,
    p.nik,
    p.nama_lengkap,
    p.kecamatan,
    p.desa_kelurahan,
    DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') AS periode,
    COUNT(t.id) AS jumlah_transaksi,
    SUM(t.jumlah_tabung) AS total_tabung,
    SUM(t.nilai_subsidi) AS total_nilai_subsidi
FROM transaksi t
JOIN penerima p ON p.id = t.penerima_id
GROUP BY p.id, periode;

-- ---------------------------------------------------------------------
-- Data awal (contoh) - boleh dihapus setelah aplikasi berjalan
-- ---------------------------------------------------------------------
INSERT INTO agen (kode_agen, nama_agen, alamat, desa_kelurahan, kecamatan, kabupaten_kota, no_hp) VALUES
('AGN-001', 'Pangkalan Sumber Rejeki', 'Jl. Merdeka No. 12', 'Terbanggi Besar', 'Terbanggi Besar', 'Lampung Tengah', '081234567890'),
('AGN-002', 'Pangkalan Berkah Jaya', 'Jl. Raya Timur No. 45', 'Bandar Jaya', 'Terbanggi Besar', 'Lampung Tengah', '081298765432');

-- Catatan: akun admin dibuat melalui halaman setup.php saat instalasi pertama
-- (bukan lewat SQL) agar password di-hash dengan aman oleh PHP password_hash().
