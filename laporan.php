<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'laporan';
$judul_halaman = 'Laporan & Analisis';

$periode = bersihkan($_GET['periode'] ?? date('Y-m'));
$periodeSql = $koneksi->real_escape_string($periode);

// ---- Ringkasan periode ----
$ringkasan = $koneksi->query("SELECT COUNT(*) jml_trx, COALESCE(SUM(jumlah_tabung),0) jml_tabung,
    COALESCE(SUM(total_bayar),0) total_bayar, COALESCE(SUM(nilai_subsidi),0) total_subsidi,
    COUNT(DISTINCT penerima_id) jml_penerima_aktif
    FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi,'%Y-%m')='$periodeSql'")->fetch_assoc();

// ---- Deteksi anomali: penerima dengan pembelian melebihi batas wajar ----
$sqlAnomali = "SELECT p.id, p.nik, p.nama_lengkap, p.desa_kelurahan, p.kecamatan,
    COUNT(t.id) jml_transaksi, SUM(t.jumlah_tabung) jml_tabung
    FROM transaksi t
    JOIN penerima p ON p.id = t.penerima_id
    WHERE DATE_FORMAT(t.tanggal_transaksi,'%Y-%m')='$periodeSql'
    GROUP BY p.id
    HAVING jml_tabung > " . (int)BATAS_TABUNG_PER_BULAN . " OR jml_transaksi > " . (int)BATAS_TRANSAKSI_PER_BULAN . "
    ORDER BY jml_tabung DESC";
$anomaliList = $koneksi->query($sqlAnomali);

// ---- Distribusi per kecamatan ----
$distribusiKec = $koneksi->query("SELECT p.kecamatan, COUNT(t.id) jml_trx, SUM(t.jumlah_tabung) jml_tabung, SUM(t.nilai_subsidi) subsidi
    FROM transaksi t JOIN penerima p ON p.id=t.penerima_id
    WHERE DATE_FORMAT(t.tanggal_transaksi,'%Y-%m')='$periodeSql'
    GROUP BY p.kecamatan ORDER BY jml_tabung DESC");

// ---- Distribusi per kategori penerima ----
$distribusiKategori = $koneksi->query("SELECT p.kategori, COUNT(t.id) jml_trx, SUM(t.jumlah_tabung) jml_tabung
    FROM transaksi t JOIN penerima p ON p.id=t.penerima_id
    WHERE DATE_FORMAT(t.tanggal_transaksi,'%Y-%m')='$periodeSql'
    GROUP BY p.kategori");

// ---- Tren harian dalam periode terpilih ----
$trenHarian = $koneksi->query("SELECT DAY(tanggal_transaksi) hari, SUM(jumlah_tabung) tabung
    FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi,'%Y-%m')='$periodeSql'
    GROUP BY hari ORDER BY hari");
$labelHarian = []; $dataHarian = [];
while ($h = $trenHarian->fetch_assoc()) { $labelHarian[] = (int)$h['hari']; $dataHarian[] = (int)$h['tabung']; }

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
    <div>
        <h4 class="page-title">Laporan &amp; Analisis Penyaluran Subsidi</h4>
        <p class="page-subtitle">Analisis distribusi dan deteksi potensi penyalahgunaan subsidi gas LPG</p>
    </div>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <input type="month" name="periode" value="<?= htmlspecialchars($periode) ?>" class="form-control" onchange="this.form.submit()">
        </form>
        <a href="export_csv.php?periode=<?= htmlspecialchars($periode) ?>" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Ekspor CSV</a>
    </div>
</div>

<div class="row g-3 mb-2">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Total Transaksi</div><div class="stat-value"><?= number_format($ringkasan['jml_trx'],0,',','.') ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card accent"><div class="stat-label">Penerima Aktif</div><div class="stat-value"><?= number_format($ringkasan['jml_penerima_aktif'],0,',','.') ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Tabung Tersalurkan</div><div class="stat-value"><?= number_format($ringkasan['jml_tabung'],0,',','.') ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card" style="border-left-color:#2563eb;"><div class="stat-label">Nilai Subsidi</div><div class="stat-value" style="font-size:18px;"><?= rupiah($ringkasan['total_subsidi']) ?></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Tren Harian Penyaluran &mdash; <?= date('F Y', strtotime($periode.'-01')) ?></h6>
            <canvas id="chartHarian" height="110"></canvas>
        </div>
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Distribusi per Kecamatan</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Kecamatan</th><th>Transaksi</th><th>Tabung</th><th>Nilai Subsidi</th></tr></thead>
                    <tbody>
                    <?php if ($distribusiKec->num_rows === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data pada periode ini.</td></tr>
                    <?php else: while ($d = $distribusiKec->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['kecamatan']) ?></td>
                            <td><?= (int)$d['jml_trx'] ?></td>
                            <td><?= (int)$d['jml_tabung'] ?></td>
                            <td><?= rupiah($d['subsidi']) ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Proporsi Kategori Penerima</h6>
            <canvas id="chartKategori" height="180"></canvas>
        </div>
        <div class="card-panel">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle text-danger"></i> Potensi Penyalahgunaan</h6>
            <p class="small text-muted mb-3">Penerima dengan pembelian melebihi batas wajar
                (&gt; <?= BATAS_TABUNG_PER_BULAN ?> tabung atau &gt; <?= BATAS_TRANSAKSI_PER_BULAN ?> transaksi/bulan)
                pada periode terpilih. Daftar ini adalah indikasi awal untuk ditinjau petugas, bukan keputusan final.</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>NIK</th><th>Nama</th><th>Trx</th><th>Tabung</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($anomaliList->num_rows === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ditemukan anomali pada periode ini.</td></tr>
                    <?php else: while ($a = $anomaliList->fetch_assoc()): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($a['nik']) ?></td>
                            <td class="small"><?= htmlspecialchars($a['nama_lengkap']) ?></td>
                            <td><?= (int)$a['jml_transaksi'] ?></td>
                            <td><?= (int)$a['jml_tabung'] ?></td>
                            <td><span class="badge-anomali">Tinjau</span></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartHarian'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labelHarian) ?>,
        datasets: [{
            label: 'Tabung per hari',
            data: <?= json_encode($dataHarian) ?>,
            borderColor: '#0d6e4f', backgroundColor: 'rgba(13,110,79,.15)',
            fill: true, tension: .3
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});

new Chart(document.getElementById('chartKategori'), {
    type: 'pie',
    data: {
        labels: [<?php $distribusiKategori->data_seek(0); while($k=$distribusiKategori->fetch_assoc()) echo "'".($k['kategori']=='usaha_mikro'?'Usaha Mikro':'Rumah Tangga')."',"; ?>],
        datasets: [{
            data: [<?php $distribusiKategori->data_seek(0); while($k=$distribusiKategori->fetch_assoc()) echo (int)$k['jml_tabung'].","; ?>],
            backgroundColor: ['#0d6e4f','#e07b00']
        }]
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
