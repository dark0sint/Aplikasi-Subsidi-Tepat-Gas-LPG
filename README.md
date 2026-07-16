# Subsidi Tepat Gas LPG

Aplikasi web untuk pendataan, monitoring, dan analisis penyaluran subsidi gas LPG 3 Kg tingkat desa/kecamatan/kabupaten.

## Fitur Utama

1. **Data Penerima** — pendataan warga/pelaku usaha mikro penerima subsidi (NIK, KK, alamat, kategori, status ekonomi), lengkap dengan pencarian dan validasi NIK 16 digit.
2. **Transaksi Pembelian** — pencatatan setiap pembelian gas: tanggal, jenis tabung, jumlah, harga subsidi vs harga normal, agen/pangkalan penyalur.
3. **Dashboard** — ringkasan real-time: jumlah penerima aktif, transaksi & tabung tersalurkan bulan berjalan, total nilai subsidi, grafik tren 6 bulan, dan distribusi wilayah.
4. **Laporan & Analisis** — analisis bulanan lengkap: tren harian, distribusi per kecamatan, proporsi kategori penerima, dan **deteksi potensi penyalahgunaan subsidi** (penerima yang membeli melebihi batas wajar per bulan).
5. **Ekspor CSV** — unduh data transaksi per periode untuk pelaporan ke instansi terkait.
6. **Manajemen Pengguna** — multi-akun dengan peran Admin/Petugas, login aman dengan password ter-hash (bcrypt).
7. **Data Agen/Pangkalan** — kelola daftar agen resmi penyalur.

## Kebutuhan Server

- PHP 8.0 atau lebih baru (dengan ekstensi `mysqli`)
- MySQL 5.7+ / MariaDB 10.3+
- Web server: Apache atau Nginx (mendukung PHP)
- Umumnya sudah tersedia otomatis di hosting cPanel, Plesk, atau server LAMP/LEMP manapun

## Langkah Instalasi

### 1. Unggah File
Salin seluruh folder `subsidi-tepat-gas/` ke direktori web server Anda, misalnya:
- cPanel: `public_html/subsidi-gas/`
- VPS Apache: `/var/www/html/subsidi-gas/`

### 2. Buat Database
Buat database MySQL baru (misalnya lewat phpMyAdmin atau CLI), lalu impor skema:

```bash
mysql -u root -p -e "CREATE DATABASE subsidi_gas CHARACTER SET utf8mb4;"
mysql -u root -p subsidi_gas < database/subsidi_gas.sql
```

Atau lewat phpMyAdmin: buat database `subsidi_gas`, lalu menu **Import** → pilih file `database/subsidi_gas.sql`.

### 3. Atur Koneksi Database
Edit file `config.php`, sesuaikan dengan kredensial database Anda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'user_database_anda');
define('DB_PASS', 'password_database_anda');
define('DB_NAME', 'subsidi_gas');
```

### 4. Setel Izin Folder (khusus Linux/VPS)
```bash
chown -R www-data:www-data /var/www/html/subsidi-gas
chmod -R 755 /var/www/html/subsidi-gas
```

### 5. Buat Akun Admin Pertama
Buka aplikasi di browser, misalnya `https://domainanda.com/subsidi-gas/`.
Karena database masih kosong, Anda akan otomatis diarahkan ke halaman **Setup Awal** (`setup.php`) untuk membuat akun administrator pertama. Isi nama, username, dan password (minimal 6 karakter).

### 6. Masuk & Mulai Gunakan
Setelah akun admin dibuat, Anda akan diarahkan ke halaman login. Masuk, lalu mulai:
1. Tambahkan data **Agen/Pangkalan** (opsional, contoh data sudah tersedia).
2. Tambahkan **Data Penerima** subsidi.
3. Catat **Transaksi Pembelian** setiap kali warga mengambil gas.
4. Pantau **Dashboard** dan **Laporan & Analisis** secara berkala.

## Konfigurasi Deteksi Anomali

Batas wajar jumlah transaksi/tabung per NIK per bulan dapat diubah di `config.php`:

```php
define('BATAS_TRANSAKSI_PER_BULAN', 4);
define('BATAS_TABUNG_PER_BULAN', 4);
```

Sistem akan menampilkan **peringatan** (bukan pemblokiran otomatis) saat transaksi baru dicatat untuk penerima yang sudah melebihi batas ini pada bulan yang sama, serta menampilkan daftarnya di halaman Laporan & Analisis.

## Struktur Folder

```
subsidi-tepat-gas/
├── config.php                 # Konfigurasi koneksi database
├── setup.php                  # Wizard pembuatan akun admin pertama
├── login.php / logout.php     # Autentikasi
├── index.php                  # Router awal
├── dashboard.php              # Dashboard ringkasan
├── penerima.php               # Daftar data penerima
├── penerima_form.php          # Form tambah/edit penerima
├── transaksi.php              # Daftar transaksi
├── transaksi_form.php         # Form catat/edit transaksi
├── laporan.php                # Laporan & analisis + deteksi anomali
├── export_csv.php             # Ekspor laporan ke CSV
├── agen.php                   # CRUD data agen/pangkalan
├── pengguna.php                # Manajemen akun (khusus admin)
├── includes/
│   ├── auth.php                # Fungsi autentikasi & utilitas
│   ├── header.php               # Layout atas + sidebar
│   └── footer.php               # Layout penutup
├── assets/css/style.css        # Tema visual aplikasi
└── database/subsidi_gas.sql    # Skema database + data contoh
```

## Keamanan

- Password disimpan dengan `password_hash()` (bcrypt), tidak pernah dalam bentuk teks biasa.
- Seluruh query database menggunakan **prepared statements** (mysqli) untuk mencegah SQL Injection.
- Seluruh output ke HTML melalui `htmlspecialchars()` untuk mencegah XSS.
- Validasi format NIK (16 digit angka) di sisi server.
- Disarankan mengaktifkan **HTTPS** di server produksi dan mengganti kredensial database default.

## Lisensi & Kustomisasi
Kode ini dapat dimodifikasi bebas sesuai kebutuhan instansi/desa Anda (misalnya menambahkan integrasi dengan data DTKS, cetak kartu kendali, atau notifikasi WhatsApp).
