# Analisis Kekurangan Proyek Ayosehat

Dokumen ini mencatat kekurangan teknis dan fungsional yang ditemukan dalam proyek Ayosehat (PHP native + MySQL).

> **Legenda:** ✅ DIPERBAIKI — item sudah ditangani | ⚠️ BELUM — item masih terbuka

---

## 1. Keamanan (Security)

### ✅ 1.1 Kredensial Admin Hard-coded
~~Password admin tersimpan langsung di source code sebagai plain text.~~

**Diperbaiki:** Tabel `admin` ditambahkan ke `database/rumahsakit.sql`. Login admin kini query ke tabel tersebut menggunakan `verifyPassword()` (bcrypt). Password default `admin123` disimpan sebagai hash `$2y$10$...` di seed data — bisa diubah langsung dari database tanpa menyentuh kode.

### 1.2 Tidak Ada CSRF Protection
Semua form POST (login, register, tambah dokter, hapus antrian, dll.) tidak memiliki CSRF token. Penyerang bisa membuat halaman berbahaya yang mengirim permintaan POST atas nama user yang sedang login.

### 1.3 Tidak Ada Rate Limiting pada Login
Tidak ada pembatasan percobaan login. Akun bisa diserang dengan brute force tanpa hambatan.

### ✅ 1.4 Session Tidak Di-regenerate Setelah Login
~~Setelah login berhasil, `session_regenerate_id(true)` tidak dipanggil. Rentan session fixation.~~

**Diperbaiki:** `session_regenerate_id(true)` sekarang dipanggil di kedua jalur login (admin dan user) sebelum data session di-set.

### 1.5 File Session Masuk ke Repository
Folder `storage/sessions/` berisi file session aktif yang ter-commit ke git. Data session pengguna bisa terbaca oleh siapa saja yang punya akses ke repository.

### 1.6 Kode Antrian Sangat Lemah
**File:** `index.php:214`
```php
$kode = chr(random_int(65, 90)) . random_int(0, 9);
```
Hanya 260 kombinasi yang mungkin (26 huruf × 10 angka). Tidak ada pengecekan keunikan. Kode mudah ditebak dan bisa terjadi duplikasi, yang bisa dieksploitasi untuk melihat data antrian orang lain.

### ✅ 1.7 SQL Injection pada `ensureColumn`
~~**File:** `app/db.php:74`~~
~~Nama tabel, kolom, dan definisi digabung langsung ke query tanpa escaping.~~

**Diperbaiki:** Fungsi `ensureColumn()` dan `initializeDatabase()` dihapus seluruhnya saat migrasi ke MySQL. Skema kini dikelola via `database/rumahsakit.sql` yang diimport ke phpMyAdmin.

### 1.8 Tidak Ada Validasi Format Input
Tidak ada validasi format pada nomor HP (bisa diisi sembarang teks), tanggal lahir, nomor kartu BPJS, atau shift dokter. Data tidak bersih bisa masuk ke database.

---

## 2. Fungsionalitas yang Hilang atau Cacat

### 2.1 Field Form Tidak Disimpan
**File:** `index.php:53-58` (action `save_pasien`) dan `index.php:660-665` (form register)

- Form **data pasien** memiliki field `agama`, `pekerjaan`, dan `alamat`, tapi ketiga field ini tidak disimpan ke `$_SESSION` maupun database.
- Form **register** memiliki field `name` tapi tidak disimpan ke database (hanya `username` dan `password` yang disimpan).

### 2.2 User Tidak Bisa Membatalkan Pendaftaran Sendiri
User tidak memiliki tombol untuk membatalkan antrian yang sudah dibuat. Pembatalan hanya bisa dilakukan oleh admin melalui perubahan status. Ini sangat tidak nyaman bagi user.

### 2.3 Tidak Ada Nomor Antrian Berurutan
Sistem tidak menggunakan nomor antrian yang nyata dan berurutan (A1, A2, B1, dst.). Kode antrian hanya 2 karakter acak yang tidak merepresentasikan urutan sebenarnya.

### 2.4 Tidak Ada Notifikasi
Tidak ada notifikasi (email, SMS, atau push notification) kepada user ketika:
- Status antrian berubah
- Resep sudah diatur admin
- Jadwal pengambilan obat sudah ditentukan

User harus terus-menerus refresh halaman secara manual.

### 2.5 User Tidak Bisa Edit Profil
Tidak ada halaman edit profil. User tidak bisa mengubah password atau menyimpan data pribadi yang dapat digunakan ulang untuk pendaftaran berikutnya.

### 2.6 Data Pasien Harus Diisi Ulang Setiap Pendaftaran
Data pasien seperti nama, tanggal lahir, nomor HP tidak disimpan ke profil user. Setiap kali mendaftar, user harus mengisi ulang semua data yang sama.

### 2.7 Resep Tidak Menyimpan Dosis/Instruksi
**File:** `app/db.php:39` (kolom `resep_obat_json`)
Resep hanya menyimpan nama obat saja tanpa dosis, jumlah, atau instruksi pemakaian. Ini tidak cukup untuk kebutuhan medis yang sesungguhnya.

### 2.8 Tidak Ada Manajemen Stok Obat
Tabel `obat` hanya menyimpan nama obat. Tidak ada stok, tidak ada pengecekan ketersediaan. Obat yang kehabisan stok tetap bisa diresepkan.

### 2.9 Tidak Ada Pencarian/Filter di Antrian Admin
Tabel antrian admin menampilkan semua data sekaligus tanpa filter berdasarkan tanggal, status, dokter, atau kata kunci. Ketika data banyak, halaman akan sangat sulit digunakan.

### 2.10 Tidak Ada Pagination
Tidak ada pagination di tabel admin (antrian, dokter, obat, pesanan). Semua data di-load sekaligus, yang akan memperlambat halaman ketika data tumbuh besar.

### 2.11 Hanya Satu Akun Admin
Hanya ada satu akun admin yang hard-coded. Tidak ada manajemen multi-admin, tidak ada role lebih granular (misalnya petugas farmasi vs. petugas antrian).

### 2.12 Halaman Statis Tidak Berguna
Halaman **Reward**, **Fasilitas**, **Panduan**, dan **About** sepenuhnya hard-coded sebagai konten statis. Tidak ada cara mengelola konten ini melalui admin tanpa mengubah kode.

### ✅ 2.13 Sidebar Profil Tidak Bisa Dibuka
~~HTML hanya merender `[data-profile-close]` tapi tidak ada `[data-profile-open]` di navbar.~~

**Diperbaiki:** Ditambahkan tombol `[data-profile-open]` di `renderNav()` — tampil hanya untuk role `user`, berupa ikon dan username. Tombol terhubung ke JS yang sudah ada di `app.js` yang menggeser sidebar masuk/keluar.

### 2.14 Langkah Pendaftaran Tidak Bisa Kembali
Dalam alur multi-step (Pasien → Pembayaran → Tanggal → Dokter → Konfirmasi), tidak ada tombol "Kembali" yang aman. Jika user ingin mengubah data di langkah sebelumnya, satu-satunya cara adalah "Daftar Ulang" yang menghapus semua data yang sudah diisi.

---

## 3. Arsitektur & Kualitas Kode

### 3.1 Satu File Monolitik 1864 Baris
Semua logika bisnis, routing, koneksi database, validasi, dan HTML view berada dalam satu file `index.php`. Sangat sulit dipelihara, di-debug, dan di-test seiring pertumbuhan fitur.

### 3.2 Tidak Ada Pemisahan View dan Logic
HTML dan PHP business logic bercampur di setiap fungsi `pageXxx()`. Tidak ada template engine atau pemisahan layer presentasi.

### 3.3 URL Tidak RESTful
Semua halaman diakses via `?page=xxx`. Tidak ada routing yang bersih, tidak bisa di-bookmark secara natural, dan tidak ramah SEO.

### ✅ 3.4 SQLite Tidak Cocok untuk Multi-User Production
~~SQLite menggunakan file lock. Jika banyak pengguna mengakses bersamaan, akan terjadi bottleneck.~~

**Diperbaiki:** `app/db.php` dimigrasi ke MySQL (`mysql:host=127.0.0.1;dbname=ayosehat;charset=utf8mb4`). Konfigurasi host/user/password tersedia sebagai konstanta di baris atas file.

### 3.5 State Pendaftaran Hanya di Session
Alur pendaftaran multi-step menyimpan semua data di `$_SESSION['registration']`. Jika session expire atau browser ditutup di tengah proses, semua data hilang dan user harus mulai dari awal.

### 3.6 Tidak Ada Autoloader / Composer
Tidak menggunakan Composer. Semua dependency di-load manual dengan `require`. Sulit menambahkan library pihak ketiga dengan benar.

### 3.7 Tidak Ada Unit Test
Tidak ada test sama sekali. Tidak ada cara untuk memverifikasi bahwa perubahan kode tidak merusak fitur yang sudah ada.

### 3.8 Rumah Sakit Hard-coded
**File:** `index.php:868-877`
Daftar rumah sakit sepenuhnya hard-coded dalam array PHP. Admin tidak bisa menambah atau menghapus rumah sakit melalui dashboard.

### 3.9 Daftar Poli Hard-coded
**File:** `index.php:1021`
Daftar poli juga hard-coded. Tidak konsisten dengan data poli yang seharusnya mengikuti data dokter yang ada.

---

## 4. Database

### 4.1 Resep Obat Disimpan sebagai JSON di Kolom Teks
**File:** `app/db.php:39` (kolom `resep_obat_json`)
Relasi antrian-obat disimpan sebagai JSON string, bukan tabel relasional terpisah. Ini membuat query analitik sangat sulit dan melanggar normalisasi database.

### ✅ 4.2 Tidak Ada Foreign Key yang Aktif di Skema
~~Tabel `pesanan_obat.antrian_id` tidak memiliki deklarasi `FOREIGN KEY` eksplisit. Integritas referensial tidak terjaga.~~

**Diperbaiki:** `database/rumahsakit.sql` kini memiliki FK eksplisit:
```sql
CONSTRAINT `fk_pesanan_antrian`
  FOREIGN KEY (`antrian_id`) REFERENCES `riwayat_antrian` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE
```
Jika antrian dihapus, `antrian_id` di `pesanan_obat` otomatis di-set `NULL` — tidak lagi orphaned.

### ✅ 4.3 Tidak Ada Index Database
~~Tidak ada index pada kolom yang sering di-query. Performa query akan turun drastis seiring bertambahnya data.~~

**Diperbaiki:** `database/rumahsakit.sql` kini memiliki index pada semua kolom yang sering di-query:

| Tabel | Index |
|-------|-------|
| `riwayat_antrian` | `idx_antrian_username`, `idx_antrian_status`, `idx_antrian_tanggal` |
| `pesanan_obat` | `idx_pesanan_username`, `idx_pesanan_antrian_id`, `idx_pesanan_status` |

### ✅ 4.4 File SQL MySQL Tidak Sinkron
~~`database/rumahsakit.sql` tersedia untuk MySQL tetapi tidak sinkron dengan SQLite yang aktif dipakai.~~

**Diperbaiki:** `database/rumahsakit.sql` diperbarui penuh — skema sinkron dengan kode PHP, index dan FK sudah ditambahkan, seed data jadwal_dokter yang punya trailing space dan shift typo (`12:00 - 02:00`) juga diperbaiki. File ini adalah satu-satunya sumber kebenaran skema.

---

## 5. UX / Antarmuka

### 5.1 Flash Message Tidak Auto-Dismiss
Alert notifikasi (flash message) tidak hilang secara otomatis dan tidak memiliki tombol close. Harus melakukan navigasi halaman lain agar pesan hilang.

### 5.2 Navbar Tidak Responsif untuk Mobile
Navbar tidak memiliki hamburger menu. Di layar kecil, semua item navigasi akan menumpuk atau tersembunyi tanpa kontrol yang baik.

### 5.3 Dialog Hapus Menggunakan `confirm()` Native
Semua konfirmasi delete menggunakan `confirm()` bawaan browser yang tampilannya tidak konsisten antar-browser dan tidak bisa di-style.

### 5.4 Tidak Ada Indikator Loading
Tidak ada feedback visual (spinner/loading) ketika form sedang disubmit. User bisa mengklik tombol berulang kali dan menyebabkan data duplikat.

### 5.5 Tidak Ada Validasi Client-Side
Validasi hanya terjadi di server. User tidak mendapat feedback instan saat mengisi form salah (misalnya nomor HP berformat salah) sebelum submit.

---

## Ringkasan Prioritas Perbaikan

> Diperbarui berdasarkan analisis terkini. Urutan dalam tiap kelompok = urutan eksekusi yang disarankan.

---

### 🔴 TINGGI — Eksekusi Duluan

Dampak langsung ke fungsionalitas atau integritas data.

| Item | Judul | Status |
|------|-------|--------|
| **7.5** | `adminSavePickup` tidak validasi ID — silent fail, pesan sukses padahal tidak ada yang berubah | ⚠️ Belum |
| **7.6** | `adminMarkTaken` tidak validasi ID — sama seperti 7.5 | ⚠️ Belum |
| **6.4** | Validasi `saveAppointment` kurang ketat — bisa simpan pendaftaran tanpa RS / metode bayar | ✅ Selesai |
| **6.1** | Langkah pendaftaran bisa di-skip — user bisa akses konfirmasi langsung tanpa isi data | ✅ Selesai |
| **6.6** | User bisa buat banyak antrian aktif sekaligus — satu pasien punya 2+ antrian aktif | ✅ Selesai |
| **7.4** | Batalkan antrian tidak batalkan pesanan obat — pesanan dari antrian dibatalkan tetap aktif | ✅ Selesai |
| **2.13** | Sidebar profil tidak bisa dibuka — tombol buka tidak ada, fitur mati total | ✅ Selesai |
| **7.1** | Sidebar admin tidak ada active state — admin tidak tahu sedang di tab mana | ⚠️ Belum |

---

### 🟡 MENENGAH — Setelah Tinggi Selesai

| Item | Judul | Status |
|------|-------|--------|
| **5.1** | Flash message tidak auto-dismiss — mengganggu UX, mudah diperbaiki | ⚠️ Belum |
| **5.4** | Tidak ada loading indicator — mencegah double-submit | ⚠️ Belum |
| **5.5** | Tidak ada validasi client-side — feedback instan ke user saat isi form | ⚠️ Belum |
| **2.14** | Tidak ada tombol kembali di multi-step pendaftaran | ⚠️ Belum |
| **7.3** | Hapus obat tidak cek referensi aktif — tidak ada warning sama sekali | ⚠️ Belum |
| **7.9** | Hapus dokter tidak cek antrian aktif — tidak ada warning | ⚠️ Belum |
| **6.2** | Dead code `complete_history` / `delete_history` — kode mati, perlu dibersihkan | ⚠️ Belum |
| **2.9** | Tidak ada filter/pencarian di antrian admin — susah cari data kalau banyak | ⚠️ Belum |
| **3.8** | Rumah sakit hard-coded — admin tidak bisa tambah RS sendiri | ⚠️ Belum |

---

### 🟢 RENDAH — Kalau Ada Waktu

| Item | Judul | Status |
|------|-------|--------|
| **1.2** | CSRF Protection — perlu token di semua form, cukup besar | ⚠️ Belum |
| **1.6** | Kode antrian lemah — hanya 260 kombinasi, perlu redesign | ⚠️ Belum |
| **1.8** | Validasi format input — nomor HP, tanggal lahir, nomor kartu | ⚠️ Belum |
| **2.2** | User tidak bisa batalkan pendaftaran sendiri | ⚠️ Belum |
| **2.4** | Tidak ada notifikasi — butuh email / WA gateway | ⚠️ Belum |
| **2.10** | Tidak ada pagination — perlu refactor query | ⚠️ Belum |
| **2.11** | Hanya satu akun admin — perlu tabel role baru | ⚠️ Belum |
| **3.9** | Poli hard-coded — perlu tabel poli di DB | ⚠️ Belum |
| **5.2** | Navbar tidak responsif untuk mobile | ⚠️ Belum |
| **7.10** | Tidak ada audit trail / log perubahan | ⚠️ Belum |

---

## 6. Verifikasi User Flow vs Implementasi Aktual

Perbandingan antara alur yang didokumentasikan di `FLOW.md` dengan kode yang benar-benar berjalan.

### ✅ 6.1 Langkah Pendaftaran Bisa Di-skip Tanpa Urutan
~~Setiap halaman multi-step (`pasien`, `pembayaran`, `tanggal`, `dokter`, `konfirmasi`) bisa diakses langsung via URL tanpa menyelesaikan langkah sebelumnya.~~

**Diperbaiki:** Setiap fungsi halaman pendaftaran sekarang mengecek session data langkah sebelumnya di awal. Jika data belum ada, user di-redirect ke langkah yang belum selesai dengan pesan peringatan:
- `pagePasien()` → cek `hospital` → redirect ke `hospital`
- `pagePembayaran()` → cek `patient` → redirect ke `pasien`
- `pageTanggal()` → cek `payment` → redirect ke `pembayaran`
- `pageDokter()` → cek `date` → redirect ke `tanggal`
- `pageKonfirmasi()` → cek `doctor` → redirect ke `dokter`

### 6.2 Action `complete_history` dan `delete_history` Ada di Backend tapi Tidak Ada di UI

**File:** `index.php:84-89` (backend handler), `index.php:1114-1148` (halaman riwayat)

Di `handlePost()` terdapat dua action:
- `complete_history` → mengubah status antrian user ke `Selesai`
- `delete_history` → menghapus baris antrian milik user

Namun di `pageRiwayat()` tidak ada satu pun form atau tombol yang mengirim action tersebut. Kedua action ini adalah **dead code** — tidak bisa diakses dari antarmuka manapun.

### 6.3 Halaman Pilih Rumah Sakit Tidak Masuk `publicPages` tapi Tidak Konsisten

**File:** `index.php:21`

`$publicPages` hanya berisi `['login', 'register', 'about', 'contact', 'panduan', 'reward', 'fasilitas']`. Halaman `hospital` memerlukan login, yang benar. Namun jika user belum login lalu diarahkan ke `hospital`, akan di-redirect ke login, lalu setelah login langsung ke `home`, bukan kembali ke `hospital`. Tidak ada intended redirect setelah login.

### ✅ 6.4 Validasi `saveAppointment` Tidak Cukup Ketat
~~Hanya mengecek nama pasien, tanggal, dan nama dokter. Rumah sakit, metode pembayaran, dan poli tidak divalidasi.~~

**Diperbaiki:** Validasi sekarang mengecek semua field wajib secara terpisah dan meredirect ke langkah yang tepat jika ada yang kosong: rumah sakit → `hospital`, data pasien (nama, jenis_kelamin, tanggal_lahir, no_hp) → `pasien`, metode pembayaran (termasuk nomor kartu BPJS) → `pembayaran`, tanggal → `tanggal`, dokter & poli → `dokter`.

### 6.5 Admin Tidak Bisa Melihat Kode Antrian di Tab Pengambilan Obat

**File:** `index.php:1725-1766` (`adminTabPesanan`)

Di halaman **Pengambilan Obat**, kartu pesanan hanya menampilkan username dan waktu pesan. Kolom `kode_antrian` yang tersimpan di tabel `pesanan_obat` tidak ditampilkan sama sekali, sehingga admin tidak tahu pesanan ini berasal dari antrian mana.

### ✅ 6.6 User Bisa Membuat Banyak Antrian Aktif Sekaligus
~~Tidak ada pembatasan yang mencegah user membuat pendaftaran baru saat masih ada antrian aktif.~~

**Diperbaiki:** Pengecekan dilakukan di dua lapisan:
- `pageHospital()`: jika user masih punya antrian berstatus `On Progress` atau `Dipanggil`, halaman pilih RS diganti dengan pesan peringatan dan tombol ke riwayat antrian — user tidak bisa memulai alur pendaftaran baru sama sekali.
- `saveAppointment()`: cek ulang sebelum INSERT — jika ada antrian aktif, simpan dibatalkan dengan flash error dan redirect ke riwayat.

### ✅ 6.7 Admin Bisa Mengubah Status Antrian ke Arah Manapun Tanpa Batasan

~~Admin dapat mengubah status dari `Selesai` kembali ke `On Progress`, atau dari `Dibatalkan` ke `Dipanggil`.~~

**Diperbaiki:** `adminUpdateQueueStatus()` kini mengecek status saat ini sebelum melakukan UPDATE. Jika status sudah `Selesai` atau `Dibatalkan`, request ditolak dengan flash error dan redirect. Di sisi UI, antrian final menampilkan badge terkunci dan ikon gembok — dropdown dan tombol Simpan tidak ditampilkan sama sekali. Form resep juga dikunci: hanya menampilkan daftar resep yang sudah ada (read-only) tanpa bisa diedit.

### ✅ 6.8 Pesanan Obat Mengirim Semua Obat — By Design

~~Semua obat yang diresepkan dikirim sebagai hidden input. User tidak bisa memilih subset obat dari resep.~~

**Klarifikasi (bukan bug):** Alur bisnis yang dimaksud adalah admin menerima instruksi resep dari dokter secara manual (misal via WhatsApp), lalu menginput daftar obat beserta jumlah ke sistem. User hanya mengkonfirmasi pesanan sesuai resep yang sudah ditetapkan dokter — tidak perlu dan tidak seharusnya bisa memilih subset. "Pesan Semua atau Tidak Sama Sekali" adalah perilaku yang benar untuk alur ini.

### ✅ 6.9 Pesanan Obat Tidak Bisa Dibatalkan oleh User — By Design

~~Setelah user menekan **Pesan Obat Resep Ini**, tidak ada cara untuk membatalkan pesanan tersebut.~~

**Klarifikasi (bukan bug):** Ini merupakan konsekuensi langsung dari alur 6.8. Karena resep berasal dari dokter (diinput admin secara manual), dan user hanya mengkonfirmasi seluruh resep tanpa memilih, maka pembatalan pesanan juga seharusnya tidak bisa dilakukan sendiri oleh user — keputusan medis (resep maupun pembatalannya) ada di tangan dokter/admin. Jika user ingin membatalkan, harus menghubungi admin secara langsung. Ini konsisten dengan prinsip bahwa user hanya mengikuti instruksi medis, bukan mengendalikannya.

---

## Ringkasan Verifikasi Alur

| Langkah Alur (FLOW.md) | Status | Catatan |
|------------------------|--------|---------|
| User: Register/Login | Berjalan | Field `name` di register tidak disimpan |
| User: Dashboard | Berjalan | Tombol buka sidebar profil tidak ada |
| User: Pilih RS | Berjalan | — |
| User: Isi data pasien | Cacat | Field agama/pekerjaan/alamat tidak disimpan |
| User: Pilih pembayaran | Berjalan | — |
| User: Pilih tanggal | Berjalan | — |
| User: Pilih dokter | Berjalan | — |
| User: Konfirmasi & simpan | Cacat | Bisa disubmit dengan data tidak lengkap jika akses langsung |
| User: Pantau antrian | Berjalan | Tidak bisa batalkan sendiri |
| User: Pesan resep | Cacat | Semua obat dipesan sekaligus, tidak bisa pilih sebagian |
| User: Lihat jadwal ambil obat | Berjalan | Tidak ada notifikasi |
| Admin: Login | Berjalan | Kredensial hard-coded |
| Admin: Dashboard | Berjalan | — |
| Admin: Kelola antrian | Berjalan | Tidak ada filter/pagination |
| Admin: Atur resep | Berjalan | — |
| Admin: Pengambilan obat | Cacat | Kode antrian tidak ditampilkan |
| Admin: Jadwal dokter | Berjalan | Shift bebas teks, tidak ada validasi format |
| Admin: Master dokter | Berjalan | — |
| Admin: Daftar obat | Berjalan | — |

---

## 7. Verifikasi Admin Flow — Detail

### 7.1 Sidebar Tidak Menandai Tab yang Sedang Aktif

**File:** `index.php:1322-1327`

Semua item sidebar selalu menggunakan `class="nav-link text-white"` tanpa logika active state. Tidak ada perbedaan visual antara tab yang sedang dibuka dan yang tidak. Admin tidak tahu sedang berada di tab mana.

```php
// semua item identik — tidak ada pengecekan $tab
<a href="..." class="nav-link text-white">Kelola Antrian</a>
```

### ⚠️ 7.2 Hapus Antrian Tidak Cascade ke Pesanan Obat

**File:** `index.php:496-497`

```php
$stmt = $pdo->prepare('DELETE FROM riwayat_antrian WHERE id = ?');
```

Ketika admin menghapus antrian, baris di `pesanan_obat` yang memiliki `antrian_id` sama **tidak ikut dihapus**. Data pesanan user tersebut tetap muncul di tab Pengambilan Obat tanpa konteks antrian yang jelas.

> **Sebagian dimitigasi:** FK `ON DELETE SET NULL` di MySQL sudah ditambahkan, sehingga `antrian_id` tidak lagi menunjuk ke baris yang sudah tidak ada — nilainya menjadi `NULL`. Namun record pesanan tetap ada dan tetap muncul di admin. Solusi penuh membutuhkan logika di PHP untuk menangani atau menginformasikan kondisi ini.

### 7.3 Hapus Obat Tidak Cek Referensi di Resep/Pesanan Aktif

**File:** `index.php:436-439`

```php
$stmt = $pdo->prepare('DELETE FROM obat WHERE id = ?');
```

Admin bisa menghapus obat dari master daftar meski obat tersebut sudah ada di `resep_obat_json` antrian aktif atau di `obat_json` pesanan yang sedang berjalan. Data nama obat di record lama tidak terhapus (karena disimpan sebagai string JSON), tapi obat itu tidak akan muncul lagi di checklist resep untuk antrian baru. Tidak ada peringatan sama sekali.

### ✅ 7.4 Membatalkan Antrian Tidak Membatalkan Pesanan Obat Terkait
~~Jika admin mengubah status antrian ke `Dibatalkan`, record di `pesanan_obat` tetap aktif.~~

**Diperbaiki:** Saat `adminUpdateQueueStatus()` mengubah status ke `Dibatalkan`, langsung di-cascade: semua `pesanan_obat` dengan `antrian_id` yang sama dan status bukan `'diambil'` diubah statusnya ke `'dibatalkan'`. Efek di UI:
- **Admin (tab Pengambilan Obat):** order berstatus `'dibatalkan'` tidak muncul sama sekali (difilter dari query).
- **User (Riwayat Obat):** order ditampilkan dengan badge merah "Dibatalkan" dan alert penjelasan.
- **Dashboard admin:** counter "Pesanan belum dijadwalkan" tidak menghitung order yang dibatalkan.

### 7.5 `adminSavePickup` Tidak Memvalidasi ID dan Tidak Membedakan Kosong vs Diisi

**File:** `index.php:502-511`

Berbeda dengan fungsi admin lain yang mengecek `if ($id === 0)`, `adminSavePickup()` tidak melakukan pengecekan tersebut. Jika `id` tidak valid, query UPDATE tidak akan mengubah apapun tapi pesan "Jadwal pengambilan obat berhasil disimpan." tetap muncul.

Selain itu, jika admin menyimpan dengan tanggal dan jam kosong (menghapus jadwal), pesan sukses yang muncul tetap sama — tidak ada perbedaan antara "berhasil disimpan" dan "berhasil dikosongkan".

### 7.6 `adminMarkTaken` Tidak Memvalidasi ID

**File:** `index.php:514-521`

Sama dengan poin 7.5 — tidak ada pengecekan `$id === 0`. Jika request dikirim dengan id=0, query berjalan tanpa error, tidak mengubah apapun, dan tidak ada feedback ke admin.

### ✅ 7.7 Dua Tombol Dashboard yang Identik di Sidebar

**File:** `index.php:1322` dan `index.php:1330`

Sidebar admin menampilkan link "Dashboard" di dalam daftar navigasi (`nav-item`) sekaligus tombol "Dashboard" terpisah di bawah daftar navigasi. Keduanya menuju URL yang sama. Salah satu redundant.

### ✅ 7.8 Tidak Ada Link Antrian → Pesanan di Antarmuka Admin

~~Dari tab **Kelola Antrian**, admin tidak bisa langsung melihat pesanan obat yang dibuat dari antrian tersebut.~~

**Diperbaiki:** Kolom Aksi di tab Kelola Antrian kini memiliki tombol **Lihat Pesanan** per baris antrian. Tombol ini membuka tab Pengambilan Obat dengan filter otomatis ke kode antrian yang bersangkutan. Card pesanan yang cocok ditandai dengan border biru dan di-scroll ke tengah layar secara otomatis. Badge kode antrian juga kini tampil di header setiap card pesanan.

### 7.9 Hapus Dokter Tidak Cek Antrian Aktif yang Menggunakan Dokter Tersebut

**File:** `index.php:329-348`

Admin bisa menghapus dokter meski ada antrian aktif (`On Progress`/`Dipanggil`) yang mencatat nama dokter tersebut. Karena nama dokter disimpan sebagai string di `riwayat_antrian.dokter` (bukan foreign key), data lama tidak rusak — tapi tidak ada peringatan bahwa dokter ini masih digunakan, sehingga penghapusan bisa terjadi tanpa disadari dampaknya.

### 7.10 Tidak Ada Audit Trail / Log Perubahan

Tidak ada tabel log atau kolom `updated_at`/`updated_by` di `riwayat_antrian` maupun `pesanan_obat`. Admin tidak bisa mengetahui kapan status antrian terakhir diubah, siapa yang mengubah (jika ada multi-admin), atau riwayat perubahan resep obat. Setiap perubahan bersifat overwrite tanpa jejak.
