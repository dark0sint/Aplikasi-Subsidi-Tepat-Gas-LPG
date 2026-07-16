<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$halaman_aktif = 'penerima';
$id = (int)($_GET['id'] ?? 0);
$editMode = $id > 0;
$judul_halaman = $editMode ? 'Edit Penerima' : 'Tambah Penerima';

$data = [
    'nik'=>'', 'no_kk'=>'', 'nama_lengkap'=>'', 'jenis_kelamin'=>'L', 'alamat'=>'',
    'rt'=>'', 'rw'=>'', 'desa_kelurahan'=>'', 'kecamatan'=>'', 'kabupaten_kota'=>'',
    'no_hp'=>'', 'kategori'=>'rumah_tangga', 'status_ekonomi'=>'umum', 'status_aktif'=>1, 'catatan'=>''
];

if ($editMode) {
    $stmt = $koneksi->prepare("SELECT * FROM penerima WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { header('Location: penerima.php'); exit; }
    $data = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $k => $v) {
        if ($k === 'status_aktif') { $data[$k] = isset($_POST['status_aktif']) ? 1 : 0; continue; }
        $data[$k] = bersihkan($_POST[$k] ?? '');
    }

    if (!validasiNIK($data['nik'])) {
        $error = 'NIK harus terdiri dari tepat 16 digit angka.';
    } elseif ($data['nama_lengkap']==='' || $data['alamat']==='' || $data['desa_kelurahan']==='' || $data['kecamatan']==='' || $data['kabupaten_kota']==='') {
        $error = 'Kolom bertanda * wajib diisi.';
    } else {
        // Cek duplikasi NIK
        $cekStmt = $koneksi->prepare("SELECT id FROM penerima WHERE nik=? AND id<>?");
        $cekStmt->bind_param('si', $data['nik'], $id);
        $cekStmt->execute();
        if ($cekStmt->get_result()->num_rows > 0) {
            $error = 'NIK ini sudah terdaftar pada data penerima lain.';
        } else {
            if ($editMode) {
                $stmt = $koneksi->prepare("UPDATE penerima SET nik=?, no_kk=?, nama_lengkap=?, jenis_kelamin=?, alamat=?, rt=?, rw=?, desa_kelurahan=?, kecamatan=?, kabupaten_kota=?, no_hp=?, kategori=?, status_ekonomi=?, status_aktif=?, catatan=? WHERE id=?");
                $stmt->bind_param('sssssssssssssisi',
                    $data['nik'],$data['no_kk'],$data['nama_lengkap'],$data['jenis_kelamin'],$data['alamat'],
                    $data['rt'],$data['rw'],$data['desa_kelurahan'],$data['kecamatan'],$data['kabupaten_kota'],
                    $data['no_hp'],$data['kategori'],$data['status_ekonomi'],$data['status_aktif'],$data['catatan'],$id);
            } else {
                $stmt = $koneksi->prepare("INSERT INTO penerima (nik, no_kk, nama_lengkap, jenis_kelamin, alamat, rt, rw, desa_kelurahan, kecamatan, kabupaten_kota, no_hp, kategori, status_ekonomi, status_aktif, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('sssssssssssssis',
                    $data['nik'],$data['no_kk'],$data['nama_lengkap'],$data['jenis_kelamin'],$data['alamat'],
                    $data['rt'],$data['rw'],$data['desa_kelurahan'],$data['kecamatan'],$data['kabupaten_kota'],
                    $data['no_hp'],$data['kategori'],$data['status_ekonomi'],$data['status_aktif'],$data['catatan']);
            }
            if ($stmt->execute()) {
                header('Location: penerima.php?msg=tersimpan');
                exit;
            }
            $error = 'Gagal menyimpan data. Coba lagi.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h4 class="page-title"><?= $judul_halaman ?></h4>
<p class="page-subtitle">Lengkapi data kependudukan penerima subsidi gas LPG</p>

<?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card-panel">
    <form method="post" novalidate>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">NIK * (16 digit)</label>
                <input type="text" name="nik" maxlength="16" pattern="\d{16}" value="<?= htmlspecialchars($data['nik']) ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">No. Kartu Keluarga</label>
                <input type="text" name="no_kk" maxlength="16" value="<?= htmlspecialchars($data['no_kk']) ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select">
                    <option value="L" <?= $data['jenis_kelamin']=='L'?'selected':'' ?>>Laki-laki</option>
                    <option value="P" <?= $data['jenis_kelamin']=='P'?'selected':'' ?>>Perempuan</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Alamat *</label>
                <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($data['alamat']) ?></textarea>
            </div>
            <div class="col-md-2">
                <label class="form-label">RT</label>
                <input type="text" name="rt" value="<?= htmlspecialchars($data['rt']) ?>" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">RW</label>
                <input type="text" name="rw" value="<?= htmlspecialchars($data['rw']) ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Desa/Kelurahan *</label>
                <input type="text" name="desa_kelurahan" value="<?= htmlspecialchars($data['desa_kelurahan']) ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kecamatan *</label>
                <input type="text" name="kecamatan" value="<?= htmlspecialchars($data['kecamatan']) ?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kabupaten/Kota *</label>
                <input type="text" name="kabupaten_kota" value="<?= htmlspecialchars($data['kabupaten_kota']) ?>" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kategori Penerima</label>
                <select name="kategori" class="form-select">
                    <option value="rumah_tangga" <?= $data['kategori']=='rumah_tangga'?'selected':'' ?>>Rumah Tangga</option>
                    <option value="usaha_mikro" <?= $data['kategori']=='usaha_mikro'?'selected':'' ?>>Usaha Mikro</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status Ekonomi</label>
                <select name="status_ekonomi" class="form-select">
                    <option value="miskin" <?= $data['status_ekonomi']=='miskin'?'selected':'' ?>>Miskin</option>
                    <option value="rentan_miskin" <?= $data['status_ekonomi']=='rentan_miskin'?'selected':'' ?>>Rentan Miskin</option>
                    <option value="umum" <?= $data['status_ekonomi']=='umum'?'selected':'' ?>>Umum</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Catatan</label>
                <input type="text" name="catatan" value="<?= htmlspecialchars($data['catatan']) ?>" class="form-control">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="status_aktif" id="status_aktif" class="form-check-input" <?= $data['status_aktif']?'checked':'' ?>>
                    <label for="status_aktif" class="form-check-label">Penerima berstatus aktif</label>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Data</button>
            <a href="penerima.php" class="btn btn-light">Batal</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
