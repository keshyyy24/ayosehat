# Penjelasan Lengkap Project Ayosehat

Dokumen ini menjelaskan seluruh aspek project Ayosehat secara mendalam — mulai dari tujuan aplikasi, arsitektur teknis, cara kerja tiap bagian, hingga alur data dari awal sampai akhir. Ditujukan bagi siapa saja yang ingin memahami project ini tanpa harus membaca kode satu per satu.

---

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tech Stack & Arsitektur](#2-tech-stack--arsitektur)
3. [Struktur File](#3-struktur-file)
4. [Sistem Autentikasi](#4-sistem-autentikasi)
5. [Sistem Routing](#5-sistem-routing)
6. [Session & State Management](#6-session--state-management)
7. [Halaman Publik (index.php)](#7-halaman-publik-indexphp)
8. [Fitur User — Penjelasan Per Halaman](#8-fitur-user--penjelasan-per-halaman)
9. [Fitur Admin — Penjelasan Per Halaman](#9-fitur-admin--penjelasan-per-halaman)
10. [Database — Skema & Penjelasan](#10-database--skema--penjelasan)
11. [JavaScript (app.js)](#11-javascript-appjs)
12. [CSS & Tampilan](#12-css--tampilan)
13. [Alur Data End-to-End](#13-alur-data-end-to-end)
14. [Mekanisme Keamanan](#14-mekanisme-keamanan)
15. [Keterbatasan yang Diketahui](#15-keterbatasan-yang-diketahui)

---

## 1. Gambaran Umum

### Apa itu Ayosehat?

Ayosehat adalah aplikasi web manajemen antrian dan resep obat untuk klinik atau puskesmas. Aplikasi ini memfasilitasi seluruh alur layanan kesehatan, mulai dari pasien mendaftar berobat, dokter (via admin) memberikan resep, hingga pasien mengambil obat di apotek.

### Siapa penggunanya?

Ada dua jenis pengguna:

- **User (Pasien)** — Warga/pasien yang mendaftar berobat, memantau antrian, memesan obat dari resep, dan melihat jadwal pengambilan obat.
- **Admin (Petugas)** — Petugas klinik yang memproses antrian, menginput resep dari dokter ke sistem, menjadwalkan pengambilan obat, dan merawat data master (dokter, obat, jadwal).

### Masalah yang dipecahkan

Tanpa sistem ini, pendaftaran antrian dilakukan manual (kertas/telepon), resep ditulis tangan, dan penjadwalan pengambilan obat disampaikan lisan. Ayosehat mendigitalisasi seluruh alur tersebut dalam satu platform web yang bisa diakses dari browser.

---

## 2. Tech Stack & Arsitektur

### Teknologi yang Dipakai

| Komponen      | Teknologi                                      |
|---------------|------------------------------------------------|
| Bahasa backend | PHP 8.0+ (tanpa framework)                    |
| Database      | MySQL / MariaDB                                |
| Koneksi DB    | PDO (PHP Data Objects)                         |
| Frontend      | Bootstrap 5.3 (CSS framework)                  |
| Ikon          | Font Awesome 6                                 |
| JavaScript    | Vanilla JS (tanpa library seperti jQuery)      |
| Server        | Apache via XAMPP                               |
| Session       | PHP file-based session (disimpan di `storage/sessions/`) |

### Pola Arsitektur

Project ini menggunakan pola **Multi-File PHP** — setiap halaman adalah file `.php` tersendiri, bukan satu file monolitik. Tidak ada framework MVC, tidak ada routing library.

```
Browser → Apache → file PHP → PDO → MySQL
                ↕
           app/bootstrap.php (shared)
           app/helpers.php   (fungsi utilitas)
           app/layout.php    (fungsi render HTML)
```

Setiap file PHP di folder `user/` dan `admin/` memulai dengan:

```php
require dirname(__DIR__) . '/app/bootstrap.php';
```

Satu baris ini menginisialisasi seluruh kebutuhan: session, koneksi database, fungsi helper, dan fungsi layout.

---

## 3. Struktur File

```
healthverse-spa/
│
├── index.php              ← Pintu masuk utama: login, register, halaman statis
├── logout.php             ← Handler POST untuk logout
│
├── app/                   ← Inti aplikasi (dipakai bersama semua halaman)
│   ├── bootstrap.php      ← Inisialisasi: session, konstanta, require chain
│   ├── db.php             ← Konfigurasi dan koneksi PDO MySQL
│   ├── helpers.php        ← Semua fungsi utilitas
│   └── layout.php         ← Fungsi render HTML (header, nav, footer, sidebar)
│
├── user/                  ← Semua halaman milik user/pasien
│   ├── home.php           ← Dashboard utama user
│   ├── hospital.php       ← Langkah 1: Pilih rumah sakit
│   ├── pasien.php         ← Langkah 2: Isi data pasien
│   ├── pembayaran.php     ← Langkah 3: Pilih metode pembayaran
│   ├── tanggal.php        ← Langkah 4: Pilih tanggal kunjungan
│   ├── dokter.php         ← Langkah 5: Pilih dokter & shift
│   ├── konfirmasi.php     ← Langkah 6: Ringkasan & simpan
│   ├── riwayat.php        ← Riwayat antrian user
│   ├── pesanan.php        ← Pesan obat dari resep admin
│   ├── obat.php           ← Riwayat pesanan obat
│   ├── invoice.php        ← Detail invoice pesanan obat
│   └── jadwal.php         ← Info jadwal dokter (publik)
│
├── admin/                 ← Semua halaman milik admin
│   ├── index.php          ← Dashboard statistik operasional
│   ├── antrian.php        ← Kelola antrian & atur resep
│   ├── pesanan.php        ← Jadwal & status pengambilan obat
│   ├── jadwal.php         ← Kelola jadwal shift dokter
│   ├── dokter.php         ← Kelola master data dokter
│   └── obat.php           ← Kelola master data obat
│
├── public/                ← Aset statis yang diakses browser langsung
│   ├── app.js             ← JavaScript global
│   ├── css/gaya.css       ← CSS kustom tambahan
│   └── assets/            ← Gambar (logo, background)
│
├── database/
│   └── rumahsakit.sql     ← Schema + seed data MySQL (satu-satunya sumber kebenaran DB)
│
└── storage/
    └── sessions/          ← File session PHP tersimpan di sini
```

---

## 4. Sistem Autentikasi

### Cara Login Bekerja

Login ditangani di `index.php` oleh fungsi `login()`. Alurnya:

1. User mengirim POST dengan `username` dan `password`
2. Sistem cek tabel `admin` dulu — jika cocok, set session dengan `role = 'admin'`
3. Jika tidak ada di admin, cek tabel `pasien` — jika cocok, set session dengan `role = 'user'`
4. Password diverifikasi dengan `verifyPassword()` yang mendukung bcrypt (`password_verify`) maupun plain text lama
5. Setelah login berhasil, `session_regenerate_id(true)` dipanggil untuk mencegah session fixation
6. Session data yang disimpan: `$_SESSION['user'] = ['username' => ..., 'role' => ...]`

### Guard Fungsi

Ada dua fungsi penjaga di `helpers.php`:

```
requireLogin()  → redirect ke login jika belum login
requireAdmin()  → panggil requireLogin() dulu, lalu cek role === 'admin'
```

Setiap file user memanggil `requireLogin()` di baris pertama, setiap file admin memanggil `requireAdmin()`. Artinya akses langsung via URL tanpa login akan selalu di-redirect.

### Logout

`logout.php` hanya menerima POST. Ia menghancurkan session (`session_destroy()`) lalu redirect ke halaman login.

---

## 5. Sistem Routing

Tidak ada routing library. Routing bekerja lewat dua cara:

### a. File-based routing (admin & user)
Setiap halaman adalah file PHP tersendiri. URL langsung menuju file:
```
/user/home.php      → Dashboard user
/admin/antrian.php  → Halaman kelola antrian admin
```

### b. Query string routing (halaman publik)
`index.php` membaca `?page=xxx` untuk menentukan halaman mana yang ditampilkan:
```
/index.php           → Login (default)
/index.php?page=register → Register
/index.php?page=about    → Tentang
```

### Fungsi routeUrl()

Semua URL di seluruh project dibuat via fungsi `routeUrl()` di `helpers.php`. Tidak ada URL yang ditulis manual. Fungsi ini menerima nama halaman dan parameter, lalu mengembalikan URL yang benar.

```php
routeUrl('home')                              → /izqi/healthverse-spa/user/home.php
routeUrl('admin-dashboard', ['tab'=>'antrian']) → /izqi/healthverse-spa/admin/antrian.php
routeUrl('login')                             → /izqi/healthverse-spa/index.php
```

`APP_BASE` dihitung otomatis di `bootstrap.php` dari selisih document root dan path project, sehingga URL benar tanpa perlu hardcode apapun.

---

## 6. Session & State Management

### Session Lokasi

Session PHP disimpan di `storage/sessions/` (bukan di folder temp sistem), dikonfigurasi di `bootstrap.php`:
```php
session_save_path(dirname(__DIR__) . '/storage/sessions');
```

### Data Session

| Key | Isi | Kapan dibuat |
|-----|-----|--------------|
| `$_SESSION['user']` | `['username', 'role']` | Setelah login berhasil |
| `$_SESSION['registration']` | Data pendaftaran multi-step | Dimulai saat login user, diisi tiap langkah |
| `$_SESSION['flash']` | `['message', 'type']` | Sebelum redirect, dibaca sekali lalu dihapus |

### Pendaftaran Multi-Step via Session

Alur pendaftaran berobat menyimpan data tiap langkah ke `$_SESSION['registration']`:

```
Step 1: $_SESSION['registration']['hospital']   = 'RS Sehat Selalu'
Step 2: $_SESSION['registration']['patient']    = ['nama', 'jenis_kelamin', 'tanggal_lahir', 'no_hp']
Step 3: $_SESSION['registration']['payment']    = ['metode', 'nomor_kartu']
Step 4: $_SESSION['registration']['date']       = '2025-06-15'
Step 5: $_SESSION['registration']['doctor']     = ['nama', 'poli', 'shift']
Step 6: Baca semua → INSERT ke database → unset($_SESSION['registration'])
```

Setiap halaman langkah mengecek apakah data langkah sebelumnya sudah ada. Jika tidak, user di-redirect mundur ke langkah yang belum selesai.

### Flash Message

Flash message adalah notifikasi satu kali yang muncul setelah redirect. Cara kerjanya:

```
flash('Berhasil disimpan.')     → simpan ke $_SESSION['flash']
redirect('riwayat')             → header Location, exit
--- halaman baru load ---
flash() tanpa argumen           → baca dan hapus dari session
renderHeader() tampilkan alert  → muncul di atas halaman
```

---

## 7. Halaman Publik (index.php)

`index.php` menangani semua halaman yang tidak butuh login:

| Page | URL | Keterangan |
|------|-----|------------|
| Login | `index.php` | Form login untuk admin dan user |
| Register | `index.php?page=register` | Buat akun pasien baru |
| About | `index.php?page=about` | Profil perusahaan PT Sehat Selalu Indonesia |
| Contact | `index.php?page=contact` | Alamat dan kontak, embed Google Maps |
| Panduan | `index.php?page=panduan` | Panduan penggunaan aplikasi |
| Reward | `index.php?page=reward` | Daftar penghargaan rumah sakit |
| Fasilitas | `index.php?page=fasilitas` | Fasilitas rumah sakit (accordion interaktif) |

Jika user sudah login dan mencoba buka halaman login/register, otomatis di-redirect ke dashboard sesuai role-nya.

---

## 8. Fitur User — Penjelasan Per Halaman

### 8.1 Dashboard (home.php)

Halaman utama setelah user login. Menampilkan:

- **4 kartu statistik**: Total Antrian, Antrian Aktif, Resep Siap Dipesan, Pesanan Obat
- **Notifikasi banner** (muncul jika ada pesanan dengan jadwal pengambilan sudah ditentukan)
- **4 kartu alur layanan**: Daftar Berobat → Pantau Antrian → Pesan Resep → Ambil Obat
- **Grid informasi layanan**: link ke Jadwal Dokter, Fasilitas, Panduan, Tentang, Kontak, Reward
- **Sidebar profil** (geser dari kiri): tombol buka di navbar, berisi username + link Obat & Riwayat

Semua angka statistik dihitung langsung dari query database saat halaman dimuat.

### 8.2 Pendaftaran Berobat (6 Langkah)

Pendaftaran berobat adalah fitur inti user. Terdiri dari 6 halaman berurutan dengan progress bar di atas tiap halaman.

**Langkah 1 — Pilih Rumah Sakit (`hospital.php`)**

- Jika user masih punya antrian aktif (On Progress/Dipanggil), halaman ini menampilkan peringatan dan blokir. User tidak bisa lanjut mendaftar.
- Daftar 8 rumah sakit hard-coded ditampilkan sebagai kartu klik.
- Klik salah satu → simpan ke `$_SESSION['registration']['hospital']` → redirect ke langkah 2.

**Langkah 2 — Data Pasien (`pasien.php`)**

- Guard: cek `$_SESSION['registration']['hospital']` ada. Jika tidak, redirect ke langkah 1.
- Form: Nama Lengkap, Jenis Kelamin, Tanggal Lahir, No HP, Agama, Pekerjaan, Alamat.
- Catatan: hanya Nama, Jenis Kelamin, Tanggal Lahir, No HP yang disimpan ke session dan nantinya ke database. Field Agama, Pekerjaan, Alamat ada di form tapi tidak disimpan (diketahui sebagai kekurangan).
- Submit → simpan ke `$_SESSION['registration']['patient']` → redirect langkah 3.

**Langkah 3 — Pembayaran (`pembayaran.php`)**

- Guard: cek `$_SESSION['registration']['patient']` ada.
- Pilihan metode: Tunai, Kartu Kredit, Debit, BPJS.
- Jika pilih BPJS, field Nomor Kartu muncul secara dinamis via JavaScript.
- Submit → simpan ke `$_SESSION['registration']['payment']` → redirect langkah 4.

**Langkah 4 — Tanggal Kunjungan (`tanggal.php`)**

- Guard: cek `$_SESSION['registration']['payment']` ada.
- Input date picker untuk memilih tanggal kunjungan.
- Submit → simpan ke `$_SESSION['registration']['date']` → redirect langkah 5.

**Langkah 5 — Pilih Dokter (`dokter.php`)**

- Guard: cek `$_SESSION['registration']['date']` ada.
- Menampilkan daftar dokter dari database, dikelompokkan per poli.
- Setiap dokter menampilkan jadwal shift-nya (hari + jam).
- User klik dokter yang diinginkan → simpan ke `$_SESSION['registration']['doctor']` → redirect langkah 6.

**Langkah 6 — Konfirmasi (`konfirmasi.php`)**

- Guard: cek `$_SESSION['registration']['doctor']` ada.
- Menampilkan ringkasan seluruh data yang sudah diisi (summary card).
- Dua tombol: **Simpan** dan **Daftar Ulang**.
- Tombol Simpan → `saveAppointment()`:
  - Validasi ulang semua data dari session (double-check keamanan)
  - Cek sekali lagi apakah ada antrian aktif (cegah race condition)
  - Generate kode antrian: 1 huruf kapital acak + 1 angka acak (misal: `A7`, `Z3`)
  - INSERT ke tabel `riwayat_antrian` dengan status awal `'On Progress'`
  - Hapus `$_SESSION['registration']`
  - Flash + redirect ke riwayat
- Tombol Daftar Ulang → hapus session registration → kembali ke langkah 2.

### 8.3 Riwayat Antrian (`riwayat.php`)

Daftar semua pendaftaran berobat milik user yang sedang login, diurutkan terbaru di atas.

Tiap kartu antrian menampilkan: kode, nama dokter, poli, tanggal, jam, metode pembayaran, dan status badge berwarna:

| Status | Warna |
|--------|-------|
| On Progress | Kuning |
| Dipanggil | Biru muda |
| Selesai | Hijau |
| Dibatalkan | Merah |

User hanya bisa memantau. Perubahan status hanya bisa dilakukan admin.

### 8.4 Pesan Obat (`pesanan.php`)

Menampilkan daftar antrian user yang sudah diresepkan admin (kolom `resep_obat_json` tidak kosong dan status bukan Dibatalkan). Untuk tiap resep:

- Ditampilkan nama obat, qty, dan harga per item dalam tabel
- Jika belum dipesan: tombol **Pesan Obat Resep Ini**
- Jika sudah dipesan: badge "Sudah Dipesan" dan link ke riwayat obat

Klik **Pesan Obat** → `saveMedicineOrder()`:
- Ambil data resep dari `riwayat_antrian.resep_obat_json`
- INSERT ke tabel `pesanan_obat` dengan `status = 'menunggu'`
- Redirect ke halaman riwayat obat

### 8.5 Riwayat Obat (`obat.php`)

Daftar semua pesanan obat milik user. Tiap kartu menampilkan:

- Nomor urut pemesanan
- Status badge (Menunggu Jadwal / Siap Diambil / Sudah Diambil / Dibatalkan)
- Waktu pemesanan dan kode antrian
- Daftar obat beserta qty
- Alert jadwal pengambilan (jika sudah dijadwalkan admin)
- Tombol Lihat Invoice

### 8.6 Invoice (`invoice.php`)

Halaman detail pesanan obat tunggal. Menampilkan semua informasi pesanan termasuk rincian harga per obat dan total.

### 8.7 Jadwal Dokter (`jadwal.php`)

Halaman informasi publik (tetap butuh login) yang menampilkan seluruh jadwal praktik dokter. Dilengkapi kolom pencarian nama dokter / poli yang bekerja secara real-time via JavaScript.

---

## 9. Fitur Admin — Penjelasan Per Halaman

### 9.1 Dashboard Admin (`admin/index.php`)

Halaman pertama setelah admin login. Menampilkan:

**7 kartu statistik:**
- Total Antrian, Antrian Aktif, Belum Ada Resep
- Pesanan Obat, Belum Dijadwalkan, Dokter, Obat

**5 kartu menu navigasi** (shortcut ke tiap halaman pengelolaan):
- Kelola Antrian, Pengambilan Obat, Jadwal Dokter, Data Dokter, Daftar Obat

Semua angka dihitung langsung dari database saat halaman dibuka.

### 9.2 Kelola Antrian (`admin/antrian.php`)

Halaman paling penting untuk admin. Menampilkan tabel semua pendaftaran user.

**Kolom tabel:** Kode, User, Pasien (nama + detail), Jadwal, Dokter, Pembayaran, Resep Obat, Status, Aksi.

**Aturan status antrian:**

Antrian dengan status `Selesai` atau `Dibatalkan` dianggap **terminal** — terkunci, tidak bisa diubah lagi. Tampilan kolom Aksi berubah menjadi badge terkunci dengan ikon gembok.

Untuk antrian yang belum terminal, admin bisa:
- Pilih status baru dari dropdown → klik Simpan
- Saat ubah ke **Dibatalkan**: otomatis semua `pesanan_obat` terkait yang belum diambil ikut dibatalkan (cascade cancel)

**Fitur Resep Obat:**

Untuk antrian yang belum terminal, kolom Resep menampilkan form interaktif:
- Daftar semua obat dari master data dalam container scrollable
- Tiap obat ada checkbox + kontrol qty (tombol − dan +)
- Jika checkbox dicentang, qty otomatis set ke 1 dan tombol qty aktif
- Jika checkbox diuncek, qty kembali ke 0 dan tombol qty di-disable
- Klik **Simpan Resep** → data obat + qty disimpan sebagai JSON ke `riwayat_antrian.resep_obat_json`

Untuk antrian yang sudah terminal dengan resep, kolom Resep menampilkan daftar read-only (tidak bisa diedit lagi).

**Tombol Lihat Pesanan:** Membuka halaman `pesanan.php` dengan filter kode antrian, sehingga admin langsung melihat pesanan obat dari antrian tersebut.

**Hapus Antrian:** Tombol hapus dengan konfirmasi. Menghapus baris dari `riwayat_antrian`. FK `ON DELETE SET NULL` di MySQL akan otomatis set `antrian_id` di `pesanan_obat` menjadi `NULL`.

### 9.3 Pengambilan Obat (`admin/pesanan.php`)

Menampilkan tabel semua pesanan obat dari semua user.

Untuk setiap pesanan yang belum final (bukan diambil/dibatalkan), admin bisa:
- Input tanggal pengambilan (date picker)
- Input jam pengambilan (time picker)
- Klik **Simpan** → update `pesanan_obat.tanggal_pengambilan` dan `jam_pengambilan`
- Setelah jadwal disimpan, muncul tombol **Tandai Diambil** → update status ke `'diambil'`

Status pesanan obat:

| Status DB | Tampilan User | Warna Badge |
|-----------|--------------|-------------|
| `menunggu` | Menunggu Jadwal | Kuning |
| `menunggu` + jadwal terisi | Siap Diambil | Biru |
| `diambil` | Sudah Diambil | Hijau |
| `dibatalkan` | Dibatalkan | Merah |

Pesanan yang sudah `diambil` atau `dibatalkan` ditandai terkunci — form jadwal tidak ditampilkan.

Halaman ini juga mendukung filter by kode antrian via query string `?kode=XX`, yang dipakai oleh tombol "Lihat Pesanan" dari halaman antrian.

### 9.4 Jadwal Dokter (`admin/jadwal.php`)

Tabel daftar semua jadwal: nama dokter, poli, hari, shift (jam mulai–jam selesai).

Tambah jadwal via **modal Bootstrap**:
- Pilih dokter dari dropdown (diambil dari tabel `dokter`)
- Pilih hari dari dropdown (Senin–Minggu)
- Ketik shift bebas teks (contoh: `08:00 - 12:00`)
- Submit → INSERT ke `jadwal_dokter`

Hapus jadwal: tombol Hapus langsung per baris dengan konfirmasi browser.

### 9.5 Data Dokter (`admin/dokter.php`)

Tabel daftar semua dokter dengan nama dan badge poli.

Tambah dokter via modal → INSERT ke `dokter`.

Edit dokter: tombol Edit membuka modal yang sudah terisi data dokter tersebut (data dioper via `data-*` attribute HTML ke JavaScript). Submit → UPDATE `dokter`.

Hapus dokter: konfirmasi dulu sebelum DELETE. Karena ada FK `ON DELETE CASCADE` dari `jadwal_dokter`, semua jadwal dokter tersebut otomatis ikut terhapus.

### 9.6 Daftar Obat (`admin/obat.php`)

Tabel daftar semua obat dengan nama, harga, dan stok.

Stok ditampilkan dengan badge berwarna:
- Merah: stok 0 (Habis)
- Kuning: stok 1–10
- Hijau: stok > 10

Tambah dan edit obat via modal (sama dengan pola di dokter). Form menyertakan field nama, harga, dan stok.

---

## 10. Database — Skema & Penjelasan

### Tabel `admin`

Menyimpan akun petugas admin.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| username | VARCHAR(100) UNIQUE | Username login |
| password | VARCHAR(255) | Hash bcrypt |

Seed data bawaan: username `ayosehat`, password `admin123` (disimpan sebagai hash bcrypt).

### Tabel `pasien`

Menyimpan akun user/pasien.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| username | VARCHAR(100) UNIQUE | Username login |
| password | VARCHAR(255) | Hash bcrypt |

Tidak ada kolom profil (nama, alamat, dll.) — data pasien diisi tiap kali mendaftar, bukan disimpan di profil akun.

### Tabel `dokter`

Master data dokter.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| nama | VARCHAR(150) | Nama lengkap dokter |
| poli | VARCHAR(120) | Nama poli (Umum, Gigi, KIA, KB, dll.) |

### Tabel `jadwal_dokter`

Jadwal praktik tiap dokter per hari.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| dokter_id | INT | FK → dokter.id (CASCADE DELETE) |
| hari | VARCHAR(20) | Nama hari (Senin, Selasa, ...) |
| shift | VARCHAR(50) | Rentang jam (contoh: `08:00 - 12:00`) |

Jika dokter dihapus dari tabel `dokter`, semua jadwalnya ikut terhapus otomatis (CASCADE DELETE).

### Tabel `obat`

Master data obat yang bisa diresepkan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| nama | VARCHAR(150) | Nama obat |
| stok | INT DEFAULT 0 | Jumlah stok tersedia |
| harga | INT DEFAULT 0 | Harga per item dalam Rupiah |

### Tabel `riwayat_antrian`

Inti dari aplikasi — menyimpan setiap pendaftaran berobat.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| username | VARCHAR(100) | Username user yang mendaftar |
| kode | VARCHAR(20) | Kode antrian unik (1 huruf + 1 angka) |
| nama | VARCHAR(150) | Nama pasien saat mendaftar |
| tanggal_lahir | DATE | Tanggal lahir pasien |
| jenis_kelamin | VARCHAR(20) | Laki-laki / Perempuan |
| no_hp | VARCHAR(30) | Nomor HP pasien |
| rumah_sakit | VARCHAR(150) | Nama RS yang dipilih |
| tanggal | DATE | Tanggal kunjungan |
| jam | VARCHAR(50) | Shift dokter (rentang jam) |
| dokter | VARCHAR(150) | Nama dokter yang dipilih |
| poli | VARCHAR(120) | Poli dokter |
| metode | VARCHAR(50) | Metode pembayaran |
| nomor_kartu | VARCHAR(100) | Nomor kartu BPJS (jika pakai BPJS) |
| resep_obat_json | TEXT | JSON array obat yang diresepkan admin |
| status | VARCHAR(30) DEFAULT 'On Progress' | Status antrian |
| created_at | DATETIME DEFAULT NOW() | Waktu pendaftaran |

**Format `resep_obat_json`:**
```json
[
  {"id": 1, "nama": "Paracetamol", "qty": 2, "harga": 3000},
  {"id": 3, "nama": "Ibuprofen", "qty": 1, "harga": 5000}
]
```

### Tabel `pesanan_obat`

Menyimpan pesanan obat yang dibuat user dari resep admin.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary key |
| username | VARCHAR(100) | Username user |
| antrian_id | INT NULL | FK → riwayat_antrian.id (SET NULL on DELETE) |
| kode_antrian | VARCHAR(20) | Kode antrian (disimpan sebagai string untuk referensi) |
| obat_json | TEXT | JSON array obat yang dipesan (copy dari resep saat pesan) |
| waktu | DATETIME DEFAULT NOW() | Waktu pemesanan |
| tanggal_pengambilan | DATE | Tanggal jadwal ambil (diisi admin) |
| jam_pengambilan | TIME | Jam jadwal ambil (diisi admin) |
| status | VARCHAR(30) DEFAULT 'menunggu' | Status: menunggu / diambil / dibatalkan |

Jika antrian dihapus, `antrian_id` di sini di-set NULL (bukan ikut terhapus), sehingga riwayat pesanan tetap ada.

### Diagram Relasi

```
pasien ─────────────────────── riwayat_antrian
(username)              (username, FK tidak eksplisit)
                               │
                               │ antrian_id (SET NULL on DELETE)
                               ▼
                         pesanan_obat

dokter ──────────────── jadwal_dokter
(id)           (dokter_id, CASCADE DELETE)
```

---

## 11. JavaScript (app.js)

Satu file JS yang dimuat di semua halaman. Berisi 4 fungsionalitas:

### 1. Toggle Field BPJS
Mendeteksi perubahan pada `[data-payment-select]`. Jika nilai "BPJS", tampilkan `[data-bpjs-section]` dan set field input sebagai required. Jika bukan BPJS, sembunyikan dan hapus required.

### 2. Accordion Fasilitas
Klik `[data-fasilitas-card]` → toggle class `active` pada kartu tersebut dan hapus `active` dari kartu lain. Detail fasilitas ditampilkan via CSS transition pada `max-height`.

### 3. Sidebar Profil
- `[data-profile-open]` (tombol di navbar) → tambah class `open` ke `[data-profile-sidebar]`
- `[data-profile-close]` (tombol × di sidebar) → hapus class `open`
- Sidebar geser masuk/keluar via CSS transition.

### 4. Pencarian Real-time
Jika ada `[data-search]` di halaman (input pencarian), setiap keystroke memfilter `[data-search-item]` berdasarkan konten teks. Item yang tidak cocok di-hide via `style.display = 'none'`. Dipakai di halaman jadwal dokter.

---

## 12. CSS & Tampilan

### Bootstrap 5.3
Diload dari CDN. Dipakai untuk: grid system, kartu, tabel, form, badge, modal, alert, tombol.

### Font Awesome 6
Diload dari CDN. Dipakai untuk ikon di navbar, tombol, kartu fitur, dan badge status.

### gaya.css (CSS Kustom)

File CSS tambahan di `public/css/gaya.css`. Berisi:
- Styling halaman login/register (`.auth-shell`, `.auth-entry`, `.auth-brand-panel`)
- Navbar kustom (`.navbar`, `.navbar-nav`, `.navbar-profile-btn`)
- Sidebar profil user (`.profile-sidebar`, transisi geser)
- Kartu halaman user (`.role-card`, `.hospital-card`, `.fasilitas-card`)
- Layout admin (`.admin-layout`, `.admin-sidebar`)
- Step progress bar pendaftaran (`.step`, `.circle`)
- Padding halaman dengan navbar (`.page-shell` → `padding-top: 104px`)

### Layout Functions (`layout.php`)

Semua HTML global dirender via fungsi PHP:

| Fungsi | Output |
|--------|--------|
| `renderHeader($title)` | DOCTYPE, `<head>`, link CSS, flash message |
| `renderNav()` | Navbar dengan logo, menu, tombol profil (user only) |
| `renderFooter()` | `<script src="app.js">`, `</body>`, `</html>` |
| `stepProgress($step)` | Progress bar 5 langkah pendaftaran |
| `summaryCard($title, $items)` | Kartu ringkasan data (dipakai di konfirmasi) |
| `adminSidebarOpen()` | Buka div layout admin + render sidebar kiri |
| `adminSidebarClose()` | Tutup div layout admin |

---

## 13. Alur Data End-to-End

Berikut alur lengkap dari user mendaftar sampai mengambil obat, beserta tabel database yang terlibat:

```
[USER MENDAFTAR BEROBAT]
user/hospital.php  → simpan ke SESSION['registration']['hospital']
user/pasien.php    → simpan ke SESSION['registration']['patient']
user/pembayaran.php → simpan ke SESSION['registration']['payment']
user/tanggal.php   → simpan ke SESSION['registration']['date']
user/dokter.php    → simpan ke SESSION['registration']['doctor']
user/konfirmasi.php → INSERT INTO riwayat_antrian (status='On Progress')
                      DELETE SESSION['registration']

[ADMIN MEMPROSES ANTRIAN]
admin/antrian.php  → UPDATE riwayat_antrian SET status='Dipanggil'
                  → UPDATE riwayat_antrian SET status='Selesai'
                  → UPDATE riwayat_antrian SET resep_obat_json='[...]'
                     (jika status=Dibatalkan: UPDATE pesanan_obat SET status='dibatalkan')

[USER MEMESAN OBAT]
user/pesanan.php   → baca riwayat_antrian WHERE resep_obat_json IS NOT NULL
                  → INSERT INTO pesanan_obat (status='menunggu')

[ADMIN MENJADWALKAN PENGAMBILAN]
admin/pesanan.php  → UPDATE pesanan_obat SET tanggal_pengambilan, jam_pengambilan
                  → UPDATE pesanan_obat SET status='diambil'

[USER MELIHAT JADWAL]
user/home.php      → SELECT FROM pesanan_obat WHERE tanggal_pengambilan IS NOT NULL
user/obat.php      → SELECT FROM pesanan_obat WHERE username=?
```

---

## 14. Mekanisme Keamanan

### Yang Sudah Diterapkan

| Mekanisme | Lokasi | Keterangan |
|-----------|--------|------------|
| Password hashing | `index.php` → `register()` | `password_hash($pw, PASSWORD_DEFAULT)` → bcrypt |
| Session regeneration | `index.php` → `login()` | `session_regenerate_id(true)` setelah login |
| HTML escaping | Seluruh output | Fungsi `e()` → `htmlspecialchars()` |
| Prepared statements | Semua query DB | PDO prepared statements, tidak ada SQL injection |
| Auth guard | Semua halaman | `requireLogin()` / `requireAdmin()` di baris pertama |
| Terminal status lock | `admin/antrian.php` | Antrian Selesai/Dibatalkan tidak bisa diubah lagi |
| Duplicate queue check | `user/hospital.php`, `user/konfirmasi.php` | Cegah user punya 2 antrian aktif |
| Cascade cancel | `admin/antrian.php` | Batalkan antrian → batalkan pesanan obat terkait |
| Foreign key integrity | `database/rumahsakit.sql` | FK dengan CASCADE DELETE dan SET NULL |
| Database index | `database/rumahsakit.sql` | Index pada kolom yang sering di-WHERE |

### Yang Belum Diterapkan

- **CSRF token** — form POST tidak dilindungi token. Rentan Cross-Site Request Forgery.
- **Rate limiting login** — tidak ada batas percobaan login. Rentan brute force.
- **Validasi format input** — no HP, tanggal, format shift tidak divalidasi secara ketat.
- **Session storage di git** — folder `storage/sessions/` ter-track git. File session aktif bisa terbaca.

---

## 15. Keterbatasan yang Diketahui

Berikut keterbatasan teknis yang disadari dan dicatat di `KEKURANGAN.md`:

### Fungsional
- **Field form tidak tersimpan**: Agama, Pekerjaan, Alamat di form pasien ada di UI tapi tidak disimpan ke database.
- **Kode antrian lemah**: Hanya 260 kombinasi (26 huruf × 10 angka). Tidak ada pengecekan keunikan.
- **Rumah sakit & poli hard-coded**: Admin tidak bisa menambah RS atau poli dari dashboard.
- **Tidak ada notifikasi**: User harus refresh manual untuk melihat perubahan status.
- **Tidak ada pagination**: Semua data ditampilkan sekaligus tanpa batasan halaman.
- **User tidak bisa batalkan antrian sendiri**: Hanya admin yang bisa mengubah status.

### UX
- **Flash message tidak auto-dismiss**: Alert tetap muncul sampai user navigasi ke halaman lain.
- **Tidak ada loading indicator**: Tidak ada spinner saat form disubmit.
- **Tidak ada validasi client-side**: Semua validasi hanya di server.
- **Sidebar admin tanpa active state**: Admin tidak bisa melihat secara visual sedang berada di halaman mana.

### Arsitektur
- **Resep obat disimpan sebagai JSON**: Tidak ternormalisasi dalam tabel relasional terpisah.
- **Hanya satu akun admin**: Tidak ada manajemen multi-admin.
- **Tidak ada Composer/autoloader**: Dependency management manual.
- **Tidak ada unit test**: Tidak ada cara otomatis memverifikasi kebenaran kode.

---

*Dokumen ini ditulis berdasarkan kode aktual project per Mei 2025. Untuk perubahan terbaru, lihat commit history git.*
