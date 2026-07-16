<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
wajibLogin();

$periode = bersihkan($_GET['periode'] ?? date('Y-m'));
$periodeSql = $koneksi->real_escape_string($periode);

$data = $koneksi->query("SELECT t.kode_transaksi, t.tanggal_transaksi, p.nik, p.nama_lengkap, p.kecamatan, p.desa_kelurahan,
    t.jenis_tabung, t.jumlah_tabung, t.harga_subsidi, t.harga_normal, t.total_bayar, t.nilai_subsidi, a.nama_agen
    FROM transaksi t
    JOIN penerima p ON p.id = t.penerima_id
    LEFT JOIN agen a ON a.id = t.agen_id
    WHERE DATE_FORMAT(t.tanggal_transaksi,'%Y-%m')='$periodeSql'
    ORDER BY t.tanggal_transaksi");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laporan_subsidi_gas_' . $periode . '.csv');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
fputcsv($out, ['Kode Transaksi','Tanggal','NIK','Nama','Kecamatan','Desa/Kelurahan','Jenis Tabung','Jumlah','Harga Subsidi','Harga Normal','Total Bayar','Nilai Subsidi','Agen']);

while ($r = $data->fetch_assoc()) {
    fputcsv($out, [
        $r['kode_transaksi'], $r['tanggal_transaksi'], $r['nik'], $r['nama_lengkap'],
        $r['kecamatan'], $r['desa_kelurahan'], $r['jenis_tabung'], $r['jumlah_tabung'],
        $r['harga_subsidi'], $r['harga_normal'], $r['total_bayar'], $r['nilai_subsidi'], $r['nama_agen'] ?? '-'
    ]);
}
fclose($out);
exit;
