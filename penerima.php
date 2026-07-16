<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'penerima';
$judul_halaman = 'Data Penerima';

// Hapus data
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM penerima WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: penerima.php?msg=terhapus');
    exit;
}

$cari = bersihkan($_GET['cari'] ?? '');
$where = '';
if ($cari !== '') {
    $cariLike = $koneksi->real_escape_string($cari);
    $where = "WHERE nik LIKE '%$cariLike%' OR nama_lengkap LIKE '%$cariLike%' OR desa_kelurahan LIKE '%$cariLike%'";
}

$halamanNo = max(1, (int)($_GET['halaman'] ?? 1));
$perHalaman = 15;
$offset = ($halamanNo - 1) * $perHalaman;

$totalData = $koneksi->query("SELECT COUNT(*) c FROM penerima $where")->fetch_assoc()['c'];
$totalHalaman = max(1, ceil($totalData / $perHalaman));

$data = $koneksi->query("SELECT * FROM penerima $where ORDER BY created_at DESC LIMIT $perHalaman OFFSET $offset");

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
    <div>
        <h4 class="page-title">Data Penerima Subsidi</h4>
        <p class="page-subtitle">Kelola data warga / pelaku usaha mikro penerima subsidi gas LPG</p>
    </div>
    <a href="penerima_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Penerima</a>
</div>

<?php if (($_GET['msg'] ?? '') === 'terhapus'): ?>
<div class="alert alert-success py-2">Data penerima berhasil dihapus.</div>
<?php elseif (($_GET['msg'] ?? '') === 'tersimpan'): ?>
<div class="alert alert-success py-2">Data penerima berhasil disimpan.</div>
<?php endif; ?>

<div class="card-panel">
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-8">
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" class="form-control"
                   placeholder="Cari berdasarkan NIK, nama, atau desa/kelurahan...">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-search"></i> Cari</button>
        </div>
        <div class="col-md-2">
            <a href="penerima.php" class="btn btn-light w-100">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>NIK</th><th>Nama</th><th>Kategori</th><th>Wilayah</th><th>Status Ekonomi</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if ($data->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data penerima.</td></tr>
            <?php else: while ($r = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nik']) ?></td>
                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td><?= $r['kategori']==='usaha_mikro' ? 'Usaha Mikro' : 'Rumah Tangga' ?></td>
                    <td><?= htmlspecialchars($r['desa_kelurahan'].', '.$r['kecamatan']) ?></td>
                    <td><?= ucfirst(str_replace('_',' ',$r['status_ekonomi'])) ?></td>
                    <td><?= $r['status_aktif'] ? '<span class="badge-normal">Aktif</span>' : '<span class="badge-anomali">Nonaktif</span>' ?></td>
                    <td class="text-end">
                        <a href="penerima_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="transaksi_form.php?penerima_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-cart-plus"></i></a>
                        <a href="penerima.php?hapus=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Hapus data ini? Seluruh transaksi terkait juga akan terhapus.')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalHalaman > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-end mb-0">
            <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
            <li class="page-item <?= $i==$halamanNo?'active':'' ?>">
                <a class="page-link" href="?halaman=<?= $i ?>&cari=<?= urlencode($cari) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
