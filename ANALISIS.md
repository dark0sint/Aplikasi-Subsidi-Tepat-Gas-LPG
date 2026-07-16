# Analisis Sistem — Subsidi Tepat Gas LPG

## 1. Latar Belakang & Permasalahan

Penyaluran subsidi gas LPG 3 Kg di Indonesia menghadapi beberapa persoalan klasik di tingkat pangkalan/desa:

| Masalah | Dampak |
|---|---|
| Pendataan manual berbasis buku/kertas | Data mudah hilang, sulit direkap, rawan human error |
| Tidak ada verifikasi NIK terpusat | Satu orang bisa membeli berkali-kali di pangkalan berbeda |
| Tidak ada batas pembelian yang terpantau | Potensi penimbunan/pengalihan ke sektor komersial (restoran, usaha besar) |
| Laporan ke instansi (Pertamina/Dinas) dikerjakan manual | Lambat, tidak real-time, sulit diaudit |
| Tidak ada visibilitas distribusi antar wilayah | Sulit mengetahui desa mana yang kekurangan/kelebihan kuota |

Aplikasi **Subsidi Tepat Gas LPG** dirancang untuk mendigitalkan proses ini di level pangkalan/desa/kecamatan, dengan fokus pada tiga pilar: **pendataan akurat**, **transparansi transaksi**, dan **deteksi dini potensi penyalahgunaan**.

## 2. Target Pengguna

- **Petugas pangkalan/desa**: mencatat transaksi harian.
- **Admin kecamatan/kabupaten**: memantau dashboard, menganalisis laporan, mengelola akun petugas.
- **Instansi terkait (opsional, tahap lanjut)**: menerima laporan CSV berkala untuk keperluan audit/kuota.

## 3. Alur Kerja (Workflow)

```
1. Petugas login ke sistem
2. Warga datang ingin membeli gas subsidi
3. Petugas mencari NIK warga di sistem
   ├── Jika belum terdaftar → isi form Data Penerima terlebih dahulu
   └── Jika sudah terdaftar → lanjut ke pencatatan transaksi
4. Sistem menampilkan riwayat pembelian bulan berjalan warga tsb
   └── Jika sudah mendekati/melebihi batas wajar → tampil peringatan
5. Petugas mencatat transaksi (jenis tabung, jumlah, harga, tanggal, agen)
6. Sistem menyimpan & menghitung otomatis nilai subsidi
7. Data terakumulasi otomatis ke Dashboard & Laporan
```

## 4. Rancangan Data (ERD Ringkas)

```
penerima (1) ──< (N) transaksi (N) >── (1) agen
    │                                    
    └── NIK unik, kategori, status ekonomi
```

- **penerima**: satu baris = satu warga/pelaku usaha, NIK sebagai kunci unik.
- **transaksi**: satu baris = satu kejadian pembelian, terhubung ke penerima & agen.
- **agen**: pangkalan resmi tempat transaksi terjadi.
- **users**: akun petugas/admin yang mengoperasikan sistem.

Nilai subsidi dihitung otomatis di level transaksi:
```
total_bayar    = harga_subsidi × jumlah_tabung
nilai_subsidi  = (harga_normal − harga_subsidi) × jumlah_tabung
```
Sehingga total anggaran subsidi yang tersalurkan bisa direkap kapan saja tanpa perhitungan manual.

## 5. Metodologi Deteksi Potensi Penyalahgunaan

Pendekatan yang dipakai adalah **rule-based threshold per NIK per bulan** — pendekatan paling sederhana dan transparan untuk diaudit dibanding model statistik yang kompleks, sesuai skala data di tingkat desa/kecamatan:

```sql
SELECT nik, SUM(jumlah_tabung) AS total_tabung, COUNT(*) AS total_transaksi
FROM transaksi
WHERE bulan = periode_berjalan
GROUP BY nik
HAVING total_tabung > BATAS_TABUNG_PER_BULAN
    OR total_transaksi > BATAS_TRANSAKSI_PER_BULAN
```

**Mengapa rule-based, bukan machine learning?**
- Data awal relatif kecil (skala desa/kecamatan), model ML akan overfit atau tidak bermakna secara statistik.
- Rule-based mudah dijelaskan ke warga/petugas ("kenapa saya ditandai?") — penting untuk akuntabilitas publik.
- Ambang batas (`BATAS_TABUNG_PER_BULAN`, `BATAS_TRANSAKSI_PER_BULAN`) dapat disesuaikan admin di `config.php` sesuai kebijakan daerah masing-masing, tanpa mengubah kode.

**Penting:** sistem hanya memberi **tanda untuk ditinjau petugas** (badge "Tinjau"), bukan otomatis memblokir. Keputusan akhir tetap di tangan manusia, karena bisa saja ada alasan sah (keluarga besar, acara khusus, dll).

## 6. Simulasi Data

Skema database (`database/subsidi_gas.sql`) sudah menyertakan 2 data agen contoh sebagai starting point. Untuk simulasi dashboard yang lebih realistis, admin dapat:
1. Menambahkan beberapa data penerima lewat menu **Data Penerima**.
2. Mencatat beberapa transaksi dengan tanggal bervariasi (termasuk sengaja membuat satu penerima dengan >4 tabung/bulan) lewat menu **Transaksi Pembelian**.
3. Membuka menu **Laporan & Analisis** untuk melihat grafik tren harian, distribusi kecamatan, dan daftar "Perlu Ditinjau" otomatis muncul.

Dashboard dan grafik (Chart.js) akan otomatis terisi begitu ada data transaksi — tidak perlu data dummy statis karena semua angka dihitung langsung dari database (real-time, bukan simulasi hardcode).

## 7. Batasan & Pengembangan Lanjutan

Batasan versi saat ini:
- Deteksi anomali berbasis NIK tunggal; belum mendeteksi pola per Kartu Keluarga (KK) atau relasi antar-NIK dalam satu rumah.
- Belum ada integrasi otomatis dengan data DTKS/Kemensos untuk verifikasi status ekonomi.
- Belum ada cetak kartu kendali fisik (barcode/QR) — saat ini pencarian penerima manual via NIK/nama.

Rekomendasi pengembangan lanjutan (di luar cakupan versi ini):
- Tambah verifikasi berbasis KK untuk menangkap penyalahgunaan lintas-NIK dalam satu keluarga.
- Tambah QR code per penerima untuk mempercepat pencarian di lapangan.
- Tambah notifikasi WhatsApp/SMS ke petugas kecamatan saat anomali terdeteksi.
- Tambah role "Kecamatan"/"Kabupaten" dengan hak akses laporan lintas-desa (saat ini semua petugas melihat data yang sama; multi-tenant per wilayah bisa ditambahkan sesuai kebutuhan).

## 8. Ringkasan Teknis

| Aspek | Pilihan | Alasan |
|---|---|---|
| Bahasa server | PHP 8+ (native, tanpa framework) | Kompatibel dengan hampir semua hosting/cPanel di Indonesia, mudah dipelihara instansi daerah |
| Database | MySQL/MariaDB | Standar de facto hosting shared/VPS lokal |
| Frontend | Bootstrap 5 + Chart.js (CDN) | Ringan, responsif, tidak perlu build tool/Node.js di server |
| Autentikasi | Session PHP + bcrypt | Aman, tanpa dependensi tambahan |
| Proteksi | Prepared statements, `htmlspecialchars()`, validasi NIK | Mencegah SQL Injection & XSS dasar |
