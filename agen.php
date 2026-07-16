<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'agen';
$judul_halaman = 'Data Agen/Pangkalan';

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM agen WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: agen.php?msg=terhapus');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = bersihkan($_POST['kode_agen']);
    $nama = bersihkan($_POST['nama_agen']);
    $alamat = bersihkan($_POST['alamat']);
    $desa = bersihkan($_POST['desa_kelurahan']);
    $kec = bersihkan($_POST['kecamatan']);
    $kab = bersihkan($_POST['kabupaten_kota']);
    $hp = bersihkan($_POST['no_hp']);
    $idEdit = (int)($_POST['id'] ?? 0);

    if ($kode === '' || $nama === '') {
        $error = 'Kode agen dan nama agen wajib diisi.';
    } else {
        if ($idEdit > 0) {
            $stmt = $koneksi->prepare("UPDATE agen SET kode_agen=?, nama_agen=?, alamat=?, desa_kelurahan=?, kecamatan=?, kabupaten_kota=?, no_hp=? WHERE id=?");
            $stmt->bind_param('sssssssi', $kode,$nama,$alamat,$desa,$kec,$kab,$hp,$idEdit);
        } else {
            $stmt = $koneksi->prepare("INSERT INTO agen (kode_agen, nama_agen, alamat, desa_kelurahan, kecamatan, kabupaten_kota, no_hp) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssss', $kode,$nama,$alamat,$desa,$kec,$kab,$hp);
        }
        if ($stmt->execute()) {
            header('Location: agen.php?msg=tersimpan');
            exit;
        }
        $error = 'Gagal menyimpan (kode agen mungkin sudah dipakai).';
    }
}

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $koneksi->prepare("SELECT * FROM agen WHERE id=?");
    $stmt->bind_param('i', (int)$_GET['edit']);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

$listAgen = $koneksi->query("SELECT * FROM agen ORDER BY nama_agen");

include __DIR__ . '/includes/header.php';
?>
<h4 class="page-title">Data Agen / Pangkalan</h4>
<p class="page-subtitle">Kelola daftar agen resmi penyalur gas LPG bersubsidi</p>

<?php if (($_GET['msg'] ?? '') === 'terhapus'): ?><div class="alert alert-success py-2">Data agen dihapus.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'tersimpan'): ?><div class="alert alert-success py-2">Data agen tersimpan.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card-panel">
            <h6 class="fw-bold mb-3"><?= $editData ? 'Edit Agen' : 'Tambah Agen' ?></h6>
            <form method="post">
                <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                <div class="mb-2"><label class="form-label small">Kode Agen *</label>
                    <input type="text" name="kode_agen" class="form-control" value="<?= htmlspecialchars($editData['kode_agen'] ?? '') ?>" required></div>
                <div class="mb-2"><label class="form-label small">Nama Agen *</label>
                    <input type="text" name="nama_agen" class="form-control" value="<?= htmlspecialchars($editData['nama_agen'] ?? '') ?>" required></div>
                <div class="mb-2"><label class="form-label small">Alamat</label>
                    <input type="text" name="alamat" class="form-control" value="<?= htmlspecialchars($editData['alamat'] ?? '') ?>"></div>
                <div class="row g-2 mb-2">
                    <div class="col-4"><label class="form-label small">Desa</label>
                        <input type="text" name="desa_kelurahan" class="form-control" value="<?= htmlspecialchars($editData['desa_kelurahan'] ?? '') ?>"></div>
                    <div class="col-4"><label class="form-label small">Kecamatan</label>
                        <input type="text" name="kecamatan" class="form-control" value="<?= htmlspecialchars($editData['kecamatan'] ?? '') ?>"></div>
                    <div class="col-4"><label class="form-label small">Kab/Kota</label>
                        <input type="text" name="kabupaten_kota" class="form-control" value="<?= htmlspecialchars($editData['kabupaten_kota'] ?? '') ?>"></div>
                </div>
                <div class="mb-3"><label class="form-label small">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($editData['no_hp'] ?? '') ?>"></div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan</button>
                <?php if ($editData): ?><a href="agen.php" class="btn btn-light w-100 mt-2">Batal Edit</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Daftar Agen</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Wilayah</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if ($listAgen->num_rows === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data agen.</td></tr>
                    <?php else: while ($a = $listAgen->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['kode_agen']) ?></td>
                            <td><?= htmlspecialchars($a['nama_agen']) ?></td>
                            <td><?= htmlspecialchars($a['desa_kelurahan'].', '.$a['kecamatan']) ?></td>
                            <td class="text-end">
                                <a href="agen.php?edit=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="agen.php?hapus=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus agen ini?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
