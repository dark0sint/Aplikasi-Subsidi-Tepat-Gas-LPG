<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'dashboard';
$judul_halaman = 'Dashboard';

// ---- Statistik ringkas ----
$totalPenerima = $koneksi->query("SELECT COUNT(*) c FROM penerima WHERE status_aktif=1")->fetch_assoc()['c'];
$totalTransaksiBulanIni = $koneksi->query("SELECT COUNT(*) c FROM transaksi WHERE MONTH(tanggal_transaksi)=MONTH(CURDATE()) AND YEAR(tanggal_transaksi)=YEAR(CURDATE())")->fetch_assoc()['c'];
$totalTabungBulanIni = $koneksi->query("SELECT COALESCE(SUM(jumlah_tabung),0) c FROM transaksi WHERE MONTH(tanggal_transaksi)=MONTH(CURDATE()) AND YEAR(tanggal_transaksi)=YEAR(CURDATE())")->fetch_assoc()['c'];
$totalSubsidiBulanIni = $koneksi->query("SELECT COALESCE(SUM(nilai_subsidi),0) c FROM transaksi WHERE MONTH(tanggal_transaksi)=MONTH(CURDATE()) AND YEAR(tanggal_transaksi)=YEAR(CURDATE())")->fetch_assoc()['c'];

// Jumlah penerima yang melebihi batas wajar bulan berjalan (indikasi ditinjau)
$sqlAnomali = "SELECT COUNT(*) c FROM (
    SELECT penerima_id FROM transaksi
    WHERE MONTH(tanggal_transaksi)=MONTH(CURDATE()) AND YEAR(tanggal_transaksi)=YEAR(CURDATE())
    GROUP BY penerima_id
    HAVING SUM(jumlah_tabung) > " . (int)BATAS_TABUNG_PER_BULAN . "
) x";
$totalAnomali = $koneksi->query($sqlAnomali)->fetch_assoc()['c'];

// Grafik 6 bulan terakhir
$grafikLabel = [];
$grafikTabung = [];
$grafikSubsidi = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i month"));
    $labelBln = date('M Y', strtotime("-$i month"));
    $row = $koneksi->query("SELECT COALESCE(SUM(jumlah_tabung),0) tb, COALESCE(SUM(nilai_subsidi),0) sb
                             FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi,'%Y-%m')='$bln'")->fetch_assoc();
    $grafikLabel[] = $labelBln;
    $grafikTabung[] = (int)$row['tb'];
    $grafikSubsidi[] = (float)$row['sb'];
}

// Distribusi per kecamatan (top 6)
$distribusi = $koneksi->query("SELECT kecamatan, COUNT(*) jml FROM penerima WHERE status_aktif=1 GROUP BY kecamatan ORDER BY jml DESC LIMIT 6");

// Transaksi terbaru
$transaksiTerbaru = $koneksi->query("SELECT t.*, p.nama_lengkap, p.nik FROM transaksi t
    JOIN penerima p ON p.id=t.penerima_id ORDER BY t.created_at DESC LIMIT 8");

include __DIR__ . '/includes/header.php';
?>
<h4 class="page-title">Dashboard</h4>
<p class="page-subtitle">Ringkasan penyaluran subsidi gas LPG 3 kg &mdash; <?= date('d F Y') ?></p>

<div class="row g-3 mb-2">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Penerima Aktif</div>
            <div class="stat-value"><?= number_format($totalPenerima,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card accent">
            <div class="stat-label">Transaksi Bulan Ini</div>
            <div class="stat-value"><?= number_format($totalTransaksiBulanIni,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Tabung Tersalurkan (Bulan Ini)</div>
            <div class="stat-value"><?= number_format($totalTabungBulanIni,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card danger">
            <div class="stat-label">Perlu Ditinjau (Bulan Ini)</div>
            <div class="stat-value"><?= number_format($totalAnomali,0,',','.') ?></div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="stat-card" style="border-left-color:#2563eb;">
            <div class="stat-label">Total Nilai Subsidi Tersalurkan (Bulan Ini)</div>
            <div class="stat-value"><?= rupiah($totalSubsidiBulanIni) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Tren Penyaluran 6 Bulan Terakhir</h6>
            <canvas id="chartTren" height="110"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Distribusi Penerima per Kecamatan</h6>
            <canvas id="chartDistribusi" height="180"></canvas>
        </div>
    </div>
</div>

<div class="card-panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Transaksi Terbaru</h6>
        <a href="transaksi.php" class="btn btn-sm btn-outline-secondary">Lihat semua</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Kode</th><th>Tanggal</th><th>NIK</th><th>Nama</th><th>Jenis</th><th>Qty</th><th>Total Bayar</th>
            </tr></thead>
            <tbody>
            <?php if ($transaksiTerbaru->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
            <?php else: while ($r = $transaksiTerbaru->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['kode_transaksi']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['tanggal_transaksi'])) ?></td>
                    <td><?= htmlspecialchars($r['nik']) ?></td>
                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($r['jenis_tabung']) ?></td>
                    <td><?= (int)$r['jumlah_tabung'] ?></td>
                    <td><?= rupiah($r['total_bayar']) ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartTren'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($grafikLabel) ?>,
        datasets: [
            {
                label: 'Tabung Tersalurkan',
                data: <?= json_encode($grafikTabung) ?>,
                backgroundColor: '#0d6e4f',
                yAxisID: 'y'
            },
            {
                label: 'Nilai Subsidi (Rp)',
                data: <?= json_encode($grafikSubsidi) ?>,
                type: 'line',
                borderColor: '#e07b00',
                backgroundColor: '#e07b00',
                tension: .3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Tabung' } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Rp' } }
        }
    }
});

new Chart(document.getElementById('chartDistribusi'), {
    type: 'doughnut',
    data: {
        labels: [<?php $distribusi->data_seek(0); while($d=$distribusi->fetch_assoc()) echo "'".addslashes($d['kecamatan'])."',"; ?>],
        datasets: [{
            data: [<?php $distribusi->data_seek(0); while($d=$distribusi->fetch_assoc()) echo $d['jml'].","; ?>],
            backgroundColor: ['#0d6e4f','#e07b00','#2563eb','#9333ea','#dc2626','#0891b2']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
