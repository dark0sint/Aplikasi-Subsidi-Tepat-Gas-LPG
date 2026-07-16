<?php
/**
 * =====================================================================
 * KONFIGURASI APLIKASI - SUBSIDI TEPAT GAS LPG
 * =====================================================================
 * Ubah nilai di bawah ini sesuai kredensial database server Anda.
 */

// ---- Konfigurasi Database ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // ganti sesuai user MySQL server Anda
define('DB_PASS', '');              // ganti sesuai password MySQL server Anda
define('DB_NAME', 'subsidi_gas');

// ---- Konfigurasi Aplikasi ----
define('APP_NAME', 'Subsidi Tepat Gas LPG');
define('APP_VERSION', '1.0.0');

// Batas wajar jumlah transaksi per NIK per bulan.
// Dipakai modul laporan untuk menandai transaksi yang perlu ditinjau
// (indikasi potensi penyalahgunaan subsidi), BUKAN pemblokiran otomatis.
define('BATAS_TRANSAKSI_PER_BULAN', 4);
define('BATAS_TABUNG_PER_BULAN', 4);

// ---- Koneksi Database (mysqli) ----
mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($koneksi->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:40px auto;
         background:#fff3f3;border:1px solid #f5c2c2;border-radius:8px;color:#7a1f1f;">
         <h2>Gagal terhubung ke database</h2>
         <p>Pesan sistem: ' . htmlspecialchars($koneksi->connect_error) . '</p>
         <p>Periksa kembali <b>DB_HOST</b>, <b>DB_USER</b>, <b>DB_PASS</b>, dan <b>DB_NAME</b>
         pada file <code>config.php</code>, serta pastikan Anda sudah mengimpor
         <code>database/subsidi_gas.sql</code>.</p></div>');
}
$koneksi->set_charset('utf8mb4');

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
