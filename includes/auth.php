<?php
/**
 * includes/auth.php
 * Fungsi bantu autentikasi & utilitas umum aplikasi.
 */

function wajibLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function wajibAdmin() {
    wajibLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: dashboard.php?error=akses_ditolak');
        exit;
    }
}

function sudahAdaUser($koneksi) {
    $hasil = $koneksi->query("SELECT COUNT(*) AS jml FROM users");
    $row = $hasil->fetch_assoc();
    return ((int)$row['jml']) > 0;
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function bersihkan($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function validasiNIK($nik) {
    return preg_match('/^\d{16}$/', $nik) === 1;
}

function buatKodeTransaksi($koneksi) {
    $prefix = 'TRX' . date('Ymd');
    $hasil = $koneksi->query("SELECT COUNT(*) AS jml FROM transaksi WHERE kode_transaksi LIKE '$prefix%'");
    $row = $hasil->fetch_assoc();
    $urut = ((int)$row['jml']) + 1;
    return $prefix . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
}
