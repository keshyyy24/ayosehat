# Ayosehat

Aplikasi web manajemen antrian dan resep obat untuk klinik/puskesmas, dibangun dengan PHP native dan MySQL. Mendukung dua role: **Admin** (petugas) dan **User** (pasien).

---

## Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
- [Akun Bawaan](#akun-bawaan)
- [Struktur Folder](#struktur-folder)
- [Alur Penggunaan](#alur-penggunaan)
- [Skema Database](#skema-database)
- [Konfigurasi](#konfigurasi)
- [Kontribusi](#kontribusi)

---

## Fitur

### User (Pasien)
- Register dan login
- Pendaftaran berobat multi-step (pilih RS → data pasien → pembayaran → tanggal → dokter → konfirmasi)
- Session guard di tiap langkah — tidak bisa lompat langkah
- Pantau status antrian secara real-time
- Pesan obat dari resep yang sudah diatur admin
- Lihat jadwal pengambilan obat di dashboard
- Invoice pemesanan obat

### Admin (Petugas)
- Dashboard statistik operasional
- Kelola antrian: ubah status, atur resep obat per pasien
- Pengambilan obat: jadwalkan tanggal & jam, tandai sudah diambil
- Kelola jadwal dokter per hari dan shift
- Manajemen master data dokter dan poli
- Manajemen master data obat (nama, harga, stok)

### Sistem
- Status antrian terkunci setelah Selesai / Dibatalkan
- Cascade cancel: antrian dibatalkan → pesanan obat terkait otomatis dibatalkan
- Pencegahan duplikasi: user tidak bisa buat antrian baru jika masih ada yang aktif
- Notifikasi jadwal pengambilan obat di dashboard user

---

## Tech Stack

| Layer     | Teknologi                                |
|-----------|------------------------------------------|
| Backend   | PHP 8+ (native, tanpa framework)         |
| Database  | MySQL / MariaDB via PDO                  |
| Frontend  | Bootstrap 5.3, Font Awesome 6, Vanilla JS |
| Server    | Apache (XAMPP)                           |
| Session   | PHP file-based sessions                  |

---

## Prasyarat

- [XAMPP](https://www.apachefriends.org/) dengan PHP 8.0+ dan MySQL / MariaDB
- Browser modern

---

## Instalasi

### 1. Clone atau salin project

Letakkan project di folder `htdocs` XAMPP:

```
C:\xampp\htdocs\izqi\healthverse-spa\
```

### 2. Import database

1. Pastikan XAMPP **Apache** dan **MySQL** sudah berjalan
2. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Tab **Import** → klik **Choose File** → pilih:
   ```
   database/rumahsakit.sql
   ```
4. Klik **Go**

> File SQL sudah mencakup pembuatan database `ayosehat`, semua tabel, index, foreign key, dan seed data awal (dokter, obat, jadwal, akun admin).

### 3. Sesuaikan konfigurasi database (jika perlu)

Edit file `app/db.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ayosehat');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Buka di browser

```
http://localhost/izqi/healthverse-spa/
```

---

## Akun Bawaan

| Role  | Username   | Password   | Keterangan                        |
|-------|------------|------------|-----------------------------------|
| Admin | `ayosehat` | `admin123` | Akun petugas, akses halaman admin |
| User  | —          | —          | Daftar sendiri via halaman register |

---

## Struktur Folder

```
healthverse-spa/
│
├── admin/                      # Halaman-halaman admin
│   ├── index.php               # Dashboard statistik operasional
│   ├── antrian.php             # Kelola antrian & resep obat
│   ├── pesanan.php             # Jadwal & status pengambilan obat
│   ├── jadwal.php              # Jadwal shift dokter
│   ├── dokter.php              # Master data dokter
│   └── obat.php                # Master data obat
│
├── user/                       # Halaman-halaman user
│   ├── home.php                # Dashboard user
│   ├── hospital.php            # Step 1: Pilih rumah sakit
│   ├── pasien.php              # Step 2: Isi data pasien
│   ├── pembayaran.php          # Step 3: Pilih metode pembayaran
│   ├── tanggal.php             # Step 4: Pilih tanggal kunjungan
│   ├── dokter.php              # Step 5: Pilih dokter
│   ├── konfirmasi.php          # Step 6: Konfirmasi & simpan pendaftaran
│   ├── riwayat.php             # Riwayat antrian
│   ├── pesanan.php             # Pesan obat dari resep admin
│   ├── obat.php                # Riwayat pesanan obat
│   ├── invoice.php             # Invoice pesanan obat
│   └── jadwal.php              # Jadwal dokter (info publik)
│
├── app/                        # Core aplikasi (shared)
│   ├── bootstrap.php           # Entry point bersama — inisialisasi session, konstanta, require chain
│   ├── db.php                  # Konfigurasi koneksi PDO MySQL
│   ├── helpers.php             # Fungsi utilitas, routing (routeUrl, redirect, flash, dll.)
│   └── layout.php              # Fungsi render HTML (renderHeader, renderNav, renderFooter, dll.)
│
├── public/                     # Aset statis yang diakses browser
│   ├── app.js                  # JavaScript global (sidebar, search, dll.)
│   ├── css/
│   │   └── gaya.css            # CSS kustom tambahan
│   └── assets/
│       ├── ayosehat.png        # Logo aplikasi
│       └── backgound.jpg       # Gambar latar
│
├── database/
│   └── rumahsakit.sql          # Schema lengkap + seed data untuk MySQL
│
├── storage/
│   └── sessions/               # Penyimpanan file session PHP
│
├── index.php                   # Halaman publik: login, register, about, contact, dll.
├── logout.php                  # Handler logout (POST)
├── FLOW.md                     # Dokumentasi detail alur aplikasi
└── README.md                   # Dokumentasi ini
```

---

## Alur Penggunaan

### Alur User

```
Register / Login
    │
    ▼
Dashboard User
    │
    ▼
Daftar Berobat (multi-step)
  1. Pilih Rumah Sakit
  2. Isi Data Pasien
  3. Pilih Metode Pembayaran
  4. Pilih Tanggal Kunjungan
  5. Pilih Dokter
  6. Konfirmasi
    │
    ▼
Pantau Status Antrian
  • On Progress  → menunggu diproses admin
  • Dipanggil    → sedang dilayani
  • Selesai      → pemeriksaan selesai
  • Dibatalkan   → dibatalkan admin
    │
    ▼  (setelah admin mengatur resep)
Pesan Obat dari Resep
    │
    ▼  (setelah admin menjadwalkan pengambilan)
Lihat Jadwal Pengambilan Obat
```

### Alur Admin

```
Login Admin
    │
    ▼
Dashboard Operasional
    │
    ├── Kelola Antrian
    │     • Ubah status antrian (On Progress → Dipanggil → Selesai / Dibatalkan)
    │     • Pilih obat & qty untuk resep pasien
    │
    ├── Pengambilan Obat
    │     • Tentukan tanggal & jam pengambilan
    │     • Tandai obat sudah diambil
    │
    ├── Jadwal Dokter
    │     • Tambah / hapus shift dokter per hari
    │
    ├── Data Dokter
    │     • Tambah / edit / hapus dokter dan poli
    │
    └── Daftar Obat
          • Tambah / edit / hapus obat, harga, stok
```

> Dokumentasi alur lebih lengkap ada di [FLOW.md](FLOW.md).

---

## Skema Database

| Tabel               | Kolom Utama                                                                 | Keterangan                              |
|---------------------|-----------------------------------------------------------------------------|-----------------------------------------|
| `admin`             | id, username, password                                                      | Akun petugas admin                      |
| `pasien`            | id, username, password                                                      | Akun user / pasien                      |
| `dokter`            | id, nama, poli                                                              | Master data dokter                      |
| `jadwal_dokter`     | id, dokter_id, hari, shift                                                  | Jadwal praktik dokter per hari          |
| `obat`              | id, nama, stok, harga                                                       | Master data obat                        |
| `riwayat_antrian`   | id, username, kode, nama, tanggal, dokter, poli, metode, status, resep_obat_json | Data pendaftaran berobat per user  |
| `pesanan_obat`      | id, username, antrian_id, obat_json, waktu, tanggal_pengambilan, jam_pengambilan, status | Pesanan obat dari resep admin |

**Relasi:**
- `jadwal_dokter.dokter_id` → `dokter.id` (CASCADE DELETE)
- `pesanan_obat.antrian_id` → `riwayat_antrian.id` (SET NULL on DELETE)

---

## Konfigurasi

| File             | Yang Bisa Dikonfigurasi                        |
|------------------|------------------------------------------------|
| `app/db.php`     | Host, nama database, username, password MySQL  |
| `app/bootstrap.php` | Path session, konstanta APP_BASE            |

Tidak ada file `.env` — konfigurasi langsung di file PHP. Pastikan `app/db.php` tidak di-commit ke repository publik jika berisi kredensial.

---

## Kontribusi

1. Fork repository ini
2. Buat branch fitur: `git checkout -b fitur/nama-fitur`
3. Commit perubahan: `git commit -m "Tambah: nama fitur"`
4. Push ke branch: `git push origin fitur/nama-fitur`
5. Buat Pull Request

Lihat [KEKURANGAN.md](KEKURANGAN.md) untuk daftar item yang belum selesai dan bisa dikontribusikan.

---

## Lisensi

Project ini dibuat untuk keperluan akademis. Bebas digunakan dan dimodifikasi.
