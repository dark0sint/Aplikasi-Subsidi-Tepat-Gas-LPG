<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'transaksi';
$judul_halaman = 'Transaksi Pembelian';

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM transaksi WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: transaksi.php?msg=terhapus');
    exit;
}

$dariTgl = bersihkan($_GET['dari'] ?? date('Y-m-01'));
$sampaiTgl = bersihkan($_GET['sampai'] ?? date('Y-m-d'));
$cari = bersihkan($_GET['cari'] ?? '');

$where = "WHERE t.tanggal_transaksi BETWEEN '" . $koneksi->real_escape_string($dariTgl) . "' AND '" . $koneksi->real_escape_string($sampaiTgl) . "'";
if ($cari !== '') {
    $cariLike = $koneksi->real_escape_string($cari);
    $where .= " AND (p.nik LIKE '%$cariLike%' OR p.nama_lengkap LIKE '%$cariLike%' OR t.kode_transaksi LIKE '%$cariLike%')";
}

$halamanNo = max(1, (int)($_GET['halaman'] ?? 1));
$perHalaman = 15;
$offset = ($halamanNo - 1) * $perHalaman;

$totalData = $koneksi->query("SELECT COUNT(*) c FROM transaksi t JOIN penerima p ON p.id=t.penerima_id $where")->fetch_assoc()['c'];
$totalHalaman = max(1, ceil($totalData / $perHalaman));

$data = $koneksi->query("SELECT t.*, p.nik, p.nama_lengkap FROM transaksi t
    JOIN penerima p ON p.id=t.penerima_id $where
    ORDER BY t.tanggal_transaksi DESC, t.id DESC LIMIT $perHalaman OFFSET $offset");

$rekap = $koneksi->query("SELECT COUNT(*) jml_trx, COALESCE(SUM(jumlah_tabung),0) jml_tabung, COALESCE(SUM(total_bayar),0) total_bayar, COALESCE(SUM(nilai_subsidi),0) total_subsidi
    FROM transaksi t JOIN penerima p ON p.id=t.penerima_id $where")->fetch_assoc();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
    <div>
        <h4 class="page-title">Transaksi Pembelian Gas Subsidi</h4>
        <p class="page-subtitle">Catat setiap penyaluran gas LPG bersubsidi ke penerima</p>
    </div>
    <a href="transaksi_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Transaksi</a>
</div>

<?php if (($_GET['msg'] ?? '') === 'terhapus'): ?><div class="alert alert-success py-2">Transaksi berhasil dihapus.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'tersimpan'): ?><div class="alert alert-success py-2">Transaksi berhasil dicatat.</div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Jumlah Transaksi</div><div class="stat-value"><?= number_format($rekap['jml_trx'],0,',','.') ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card accent"><div class="stat-label">Total Tabung</div><div class="stat-value"><?= number_format($rekap['jml_tabung'],0,',','.') ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Total Dibayar Warga</div><div class="stat-value" style="font-size:18px;"><?= rupiah($rekap['total_bayar']) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card" style="border-left-color:#2563eb;"><div class="stat-label">Total Nilai Subsidi</div><div class="stat-value" style="font-size:18px;"><?= rupiah($rekap['total_subsidi']) ?></div></div></div>
</div>

<div class="card-panel">
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small mb-1">Dari Tanggal</label>
            <input type="date" name="dari" value="<?= htmlspecialchars($dariTgl) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Sampai Tanggal</label>
            <input type="date" name="sampai" value="<?= htmlspecialchars($sampaiTgl) ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Cari (NIK/Nama/Kode)</label>
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" class="form-control">
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Kode</th><th>Tanggal</th><th>NIK</th><th>Nama</th><th>Jenis</th><th>Qty</th>
                <th>Harga Subsidi</th><th>Total Bayar</th><th>Nilai Subsidi</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if ($data->num_rows === 0): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada transaksi pada rentang ini.</td></tr>
            <?php else: while ($r = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['kode_transaksi']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['tanggal_transaksi'])) ?></td>
                    <td><?= htmlspecialchars($r['nik']) ?></td>
                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($r['jenis_tabung']) ?></td>
                    <td><?= (int)$r['jumlah_tabung'] ?></td>
                    <td><?= rupiah($r['harga_subsidi']) ?></td>
                    <td><?= rupiah($r['total_bayar']) ?></td>
                    <td><?= rupiah($r['nilai_subsidi']) ?></td>
                    <td class="text-end">
                        <a href="transaksi_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="transaksi.php?hapus=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Hapus transaksi ini?')"><i class="bi bi-trash"></i></a>
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
                <a class="page-link" href="?halaman=<?= $i ?>&dari=<?= $dariTgl ?>&sampai=<?= $sampaiTgl ?>&cari=<?= urlencode($cari) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
