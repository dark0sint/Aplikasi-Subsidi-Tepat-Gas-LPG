<?php
/**
 * includes/header.php
 * Dipanggil oleh setiap halaman setelah wajibLogin().
 * Variabel opsional $halaman_aktif dipakai untuk menandai menu aktif.
 */
$halaman_aktif = $halaman_aktif ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= isset($judul_halaman) ? htmlspecialchars($judul_halaman) . ' - ' : '' ?><?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrap">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="fs-4">⛽</span>
            <div>
                <div class="fw-bold small lh-sm">Subsidi Tepat</div>
                <div class="text-white-50" style="font-size:11px;">Gas LPG</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $halaman_aktif === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="penerima.php" class="<?= $halaman_aktif === 'penerima' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Data Penerima
            </a>
            <a href="transaksi.php" class="<?= $halaman_aktif === 'transaksi' ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i> Transaksi Pembelian
            </a>
            <a href="laporan.php" class="<?= $halaman_aktif === 'laporan' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i> Laporan &amp; Analisis
            </a>
            <a href="agen.php" class="<?= $halaman_aktif === 'agen' ? 'active' : '' ?>">
                <i class="bi bi-shop"></i> Data Agen/Pangkalan
            </a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="pengguna.php" class="<?= $halaman_aktif === 'pengguna' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Pengguna
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="small text-white-50">Masuk sebagai</div>
            <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?></div>
            <a href="logout.php" class="btn btn-sm btn-outline-light w-100 mt-2">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- KONTEN -->
    <main class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center d-lg-none">
            <span class="fw-bold">⛽ <?= APP_NAME ?></span>
        </div>
        <div class="content-inner">
