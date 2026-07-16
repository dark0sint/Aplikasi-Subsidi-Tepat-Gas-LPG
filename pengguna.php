<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibAdmin();

$halaman_aktif = 'pengguna';
$judul_halaman = 'Manajemen Pengguna';

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id !== (int)$_SESSION['user_id']) {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
    header('Location: pengguna.php?msg=terhapus');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = bersihkan($_POST['username']);
    $nama = bersihkan($_POST['nama_lengkap']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'petugas';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $nama === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $username, $hash, $nama, $role);
        if ($stmt->execute()) {
            header('Location: pengguna.php?msg=tersimpan');
            exit;
        }
        $error = 'Gagal menyimpan (username mungkin sudah dipakai).';
    }
}

$listUser = $koneksi->query("SELECT id, username, nama_lengkap, role, created_at FROM users ORDER BY created_at DESC");

include __DIR__ . '/includes/header.php';
?>
<h4 class="page-title">Manajemen Pengguna</h4>
<p class="page-subtitle">Kelola akun petugas yang dapat mengakses aplikasi</p>

<?php if (($_GET['msg'] ?? '') === 'terhapus'): ?><div class="alert alert-success py-2">Pengguna dihapus.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'tersimpan'): ?><div class="alert alert-success py-2">Pengguna baru ditambahkan.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Tambah Pengguna</h6>
            <form method="post">
                <div class="mb-2"><label class="form-label small">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" required></div>
                <div class="mb-2"><label class="form-label small">Username</label><input type="text" name="username" class="form-control" required></div>
                <div class="mb-2"><label class="form-label small">Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small">Peran</label>
                    <select name="role" class="form-select">
                        <option value="petugas">Petugas</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Tambah</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Daftar Pengguna</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php while ($u = $listUser->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= $u['role']==='admin' ? '<span class="badge-normal">Admin</span>' : '<span class="badge text-bg-light">Petugas</span>' ?></td>
                            <td class="text-end">
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="pengguna.php?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pengguna ini?')"><i class="bi bi-trash"></i></a>
                                <?php else: ?>
                                <span class="text-muted small">Akun Anda</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
