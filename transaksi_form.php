<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'transaksi';
$id = (int)($_GET['id'] ?? 0);
$editMode = $id > 0;
$judul_halaman = $editMode ? 'Edit Transaksi' : 'Catat Transaksi';

$data = [
    'penerima_id' => (int)($_GET['penerima_id'] ?? 0),
    'agen_id' => '',
    'tanggal_transaksi' => date('Y-m-d'),
    'jenis_tabung' => '3kg',
    'jumlah_tabung' => 1,
    'harga_subsidi' => 18000,
    'harga_normal' => 40000,
    'keterangan' => ''
];

if ($editMode) {
    $stmt = $koneksi->prepare("SELECT * FROM transaksi WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { header('Location: transaksi.php'); exit; }
    $data = $row;
}

// Override penerima_id dari klik dropdown (navigasi GET) sebelum form disimpan
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['penerima_id'])) {
    $data['penerima_id'] = (int)$_GET['penerima_id'];
}

$error = '';
$peringatanAnomali = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['penerima_id'] = (int)$_POST['penerima_id'];
    $data['agen_id'] = $_POST['agen_id'] !== '' ? (int)$_POST['agen_id'] : null;
    $data['tanggal_transaksi'] = bersihkan($_POST['tanggal_transaksi']);
    $data['jenis_tabung'] = bersihkan($_POST['jenis_tabung']);
    $data['jumlah_tabung'] = max(1, (int)$_POST['jumlah_tabung']);
    $data['harga_subsidi'] = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_subsidi']);
    $data['harga_normal'] = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_normal']);
    $data['keterangan'] = bersihkan($_POST['keterangan']);

    if ($data['penerima_id'] <= 0) {
        $error = 'Silakan pilih penerima terlebih dahulu.';
    } elseif ($data['harga_subsidi'] <= 0 || $data['harga_normal'] <= 0) {
        $error = 'Harga harus lebih dari 0.';
    } else {
        $totalBayar = $data['harga_subsidi'] * $data['jumlah_tabung'];
        $nilaiSubsidi = ($data['harga_normal'] - $data['harga_subsidi']) * $data['jumlah_tabung'];

        if ($editMode) {
            $stmt = $koneksi->prepare("UPDATE transaksi SET penerima_id=?, agen_id=?, tanggal_transaksi=?, jenis_tabung=?, jumlah_tabung=?, harga_subsidi=?, harga_normal=?, total_bayar=?, nilai_subsidi=?, keterangan=? WHERE id=?");
            $stmt->bind_param('iissiddddsi', $data['penerima_id'],$data['agen_id'],$data['tanggal_transaksi'],$data['jenis_tabung'],$data['jumlah_tabung'],$data['harga_subsidi'],$data['harga_normal'],$totalBayar,$nilaiSubsidi,$data['keterangan'],$id);
        } else {
            $kode = buatKodeTransaksi($koneksi);
            $petugasId = $_SESSION['user_id'];
            $stmt = $koneksi->prepare("INSERT INTO transaksi (kode_transaksi, penerima_id, agen_id, tanggal_transaksi, jenis_tabung, jumlah_tabung, harga_subsidi, harga_normal, total_bayar, nilai_subsidi, petugas_id, keterangan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('siissiddddis', $kode,$data['penerima_id'],$data['agen_id'],$data['tanggal_transaksi'],$data['jenis_tabung'],$data['jumlah_tabung'],$data['harga_subsidi'],$data['harga_normal'],$totalBayar,$nilaiSubsidi,$petugasId,$data['keterangan']);
        }

        if ($stmt->execute()) {
            header('Location: transaksi.php?msg=tersimpan');
            exit;
        }
        $error = 'Gagal menyimpan transaksi. Periksa kembali data yang dimasukkan.';
    }
}

// Cek potensi anomali untuk penerima yang dipilih (bulan berjalan dari tanggal transaksi)
if ($data['penerima_id'] > 0 && $data['tanggal_transaksi']) {
    $bln = date('Y-m', strtotime($data['tanggal_transaksi']));
    $stmt = $koneksi->prepare("SELECT COALESCE(SUM(jumlah_tabung),0) t FROM transaksi WHERE penerima_id=? AND DATE_FORMAT(tanggal_transaksi,'%Y-%m')=? " . ($editMode ? "AND id<>$id" : ""));
    $stmt->bind_param('is', $data['penerima_id'], $bln);
    $stmt->execute();
    $sudahAmbil = (int)$stmt->get_result()->fetch_assoc()['t'];
    if ($sudahAmbil >= BATAS_TABUNG_PER_BULAN) {
        $peringatanAnomali = "Penerima ini sudah mengambil $sudahAmbil tabung pada bulan yang sama. Batas wajar adalah " . BATAS_TABUNG_PER_BULAN . " tabung/bulan. Mohon periksa kembali kewajaran transaksi ini.";
    }
}

$listPenerima = $koneksi->query("SELECT id, nik, nama_lengkap, desa_kelurahan FROM penerima WHERE status_aktif=1 ORDER BY nama_lengkap");
$listAgen = $koneksi->query("SELECT id, nama_agen FROM agen ORDER BY nama_agen");

include __DIR__ . '/includes/header.php';
?>
<h4 class="page-title"><?= $judul_halaman ?></h4>
<p class="page-subtitle">Formulir pendataan pembelian gas LPG bersubsidi</p>

<?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($peringatanAnomali): ?>
<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle"></i> <b>Peringatan:</b> <?= htmlspecialchars($peringatanAnomali) ?></div>
<?php endif; ?>

<div class="card-panel">
    <form method="post" id="formTransaksi">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Penerima (NIK / Nama) *</label>
                <select name="penerima_id" id="selectPenerima" class="form-select" required onchange="cekAnomaliSebelumIsi(this.value)">
                    <option value="">-- Pilih Penerima --</option>
                    <?php $listPenerima->data_seek(0); while ($p = $listPenerima->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= $data['penerima_id']==$p['id']?'selected':'' ?>>
                        <?= htmlspecialchars($p['nik'].' - '.$p['nama_lengkap'].' ('.$p['desa_kelurahan'].')') ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <div class="form-text">Belum terdaftar? <a href="penerima_form.php" target="_blank">Tambah penerima baru</a></div>
                <script>
                // Memuat ulang halaman lewat GET (bukan submit POST) agar peringatan
                // anomali tampil sebelum transaksi benar-benar disimpan.
                function cekAnomaliSebelumIsi(penerimaId) {
                    if (!penerimaId) return;
                    var params = new URLSearchParams(window.location.search);
                    <?php if ($editMode): ?>params.set('id', '<?= $id ?>');<?php endif; ?>
                    params.set('penerima_id', penerimaId);
                    window.location.href = 'transaksi_form.php?' + params.toString();
                }
                </script>
            </div>
            <div class="col-md-6">
                <label class="form-label">Agen / Pangkalan</label>
                <select name="agen_id" class="form-select">
                    <option value="">-- Tidak diketahui --</option>
                    <?php $listAgen->data_seek(0); while ($a = $listAgen->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>" <?= ($data['agen_id']??'')==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['nama_agen']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Transaksi *</label>
                <input type="date" name="tanggal_transaksi" value="<?= htmlspecialchars($data['tanggal_transaksi']) ?>" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jenis Tabung *</label>
                <select name="jenis_tabung" class="form-select">
                    <option value="3kg" <?= $data['jenis_tabung']=='3kg'?'selected':'' ?>>3 Kg (Subsidi)</option>
                    <option value="5.5kg" <?= $data['jenis_tabung']=='5.5kg'?'selected':'' ?>>5.5 Kg</option>
                    <option value="12kg" <?= $data['jenis_tabung']=='12kg'?'selected':'' ?>>12 Kg</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Jumlah Tabung *</label>
                <input type="number" name="jumlah_tabung" min="1" value="<?= (int)$data['jumlah_tabung'] ?>" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Harga Subsidi/Tabung *</label>
                <input type="number" name="harga_subsidi" min="1" step="100" value="<?= (float)$data['harga_subsidi'] ?>" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Harga Normal/Tabung *</label>
                <input type="number" name="harga_normal" min="1" step="100" value="<?= (float)$data['harga_normal'] ?>" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">Keterangan</label>
                <input type="text" name="keterangan" value="<?= htmlspecialchars($data['keterangan']) ?>" class="form-control" placeholder="Opsional">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Transaksi</button>
            <a href="transaksi.php" class="btn btn-light">Batal</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
