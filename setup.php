<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah ada user terdaftar, halaman setup dikunci demi keamanan
if (sudahAdaUser($koneksi)) {
    header('Location: login.php?info=setup_selesai');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = bersihkan($_POST['username'] ?? '');
    $nama     = bersihkan($_POST['nama_lengkap'] ?? '');
    $password = $_POST['password'] ?? '';
    $ulangi   = $_POST['ulangi_password'] ?? '';

    if ($username === '' || $nama === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $ulangi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param('sss', $username, $hash, $nama);
        if ($stmt->execute()) {
            header('Location: login.php?info=akun_admin_dibuat');
            exit;
        } else {
            $error = 'Gagal membuat akun (username mungkin sudah dipakai).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Setup Awal - <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="text-center mb-4">
        <div class="auth-logo">⛽</div>
        <h4 class="fw-bold mb-0"><?= APP_NAME ?></h4>
        <p class="text-muted small">Setup awal &mdash; buat akun administrator pertama</p>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Ulangi Password</label>
            <input type="password" name="ulangi_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Buat Akun Admin</button>
    </form>
</div>
</body>
</html>
