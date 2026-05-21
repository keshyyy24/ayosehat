# Alur Aplikasi Ayosehat

Dokumen ini menjelaskan alur penggunaan aplikasi Ayosehat dari sisi user dan admin sesuai struktur project saat ini.

## Gambaran Umum

Ayosehat adalah aplikasi web berbasis PHP native dan MySQL untuk membantu proses pendaftaran berobat, pemantauan antrian, pengelolaan resep obat, pemesanan obat resep, dan penjadwalan pengambilan obat.

Halaman operasional aplikasi sudah dipisah menjadi:

- `index.php`: halaman awal, login, register, dan halaman publik.
- `user/`: halaman dashboard dan alur layanan user.
- `admin/`: halaman dashboard dan pengelolaan data admin.
- `app/`: bootstrap, helper, koneksi database, dan layout.
- `database/`: skema MySQL aplikasi.
- `public/`: CSS, JavaScript, dan aset gambar.

## Role Pengguna

### User

User adalah pasien yang menggunakan aplikasi untuk:

- Register dan login.
- Membuat pendaftaran berobat.
- Memilih rumah sakit, pembayaran, tanggal, poli, dan dokter.
- Melihat status antrian.
- Melihat resep yang diinput admin.
- Memesan obat berdasarkan resep.
- Melihat jadwal pengambilan obat.
- Membuka invoice pengambilan obat.

### Admin

Admin adalah petugas yang menggunakan aplikasi untuk:

- Login ke dashboard admin.
- Melihat ringkasan operasional.
- Mengelola antrian pasien.
- Mengubah status antrian.
- Menginput resep obat berdasarkan instruksi dokter.
- Mengelola pesanan dan jadwal pengambilan obat.
- Mengelola data dokter.
- Mengelola jadwal dokter.
- Mengelola data obat, stok, dan harga.

## User Flow

### 1. Register atau Login

User membuka halaman awal aplikasi melalui `index.php`.

Jika belum memiliki akun, user membuka halaman register dan membuat akun pasien menggunakan username dan password.

Jika sudah memiliki akun, user login melalui halaman login.

Setelah login berhasil, user diarahkan ke `user/home.php`.

### 2. Dashboard User

Dashboard user menampilkan ringkasan:

- Total antrian.
- Antrian aktif.
- Resep siap dipesan.
- Total pesanan obat.
- Jadwal pengambilan obat jika sudah ditentukan admin.

Dashboard user menyediakan akses ke empat proses utama:

1. Daftar Berobat.
2. Pantau Antrian.
3. Pesan Resep.
4. Ambil Obat.

Dashboard juga menyediakan akses ke halaman informasi:

- Jadwal dokter.
- Fasilitas.
- Panduan.
- Tentang.
- Kontak.
- Reward.

### 3. Daftar Berobat

User membuat pendaftaran berobat melalui alur berikut:

1. Memilih rumah sakit di `user/hospital.php`.
2. Mengisi data pasien di `user/pasien.php`.
3. Memilih metode pembayaran di `user/pembayaran.php`.
4. Memilih tanggal kunjungan di `user/tanggal.php`.
5. Memilih poli dan dokter di `user/dokter.php`.
6. Mengonfirmasi pendaftaran di `user/konfirmasi.php`.

Data pendaftaran sementara disimpan di session sampai user menekan tombol simpan pada halaman konfirmasi.

Jika user masih memiliki antrian aktif dengan status `On Progress` atau `Dipanggil`, user tidak dapat membuat pendaftaran baru.

### 4. Konfirmasi Pendaftaran

Pada halaman konfirmasi, sistem menampilkan ringkasan:

- Data pasien.
- Tanggal kunjungan.
- Jam dokter.
- Nama dokter.
- Poli.
- Metode pembayaran.
- Nomor kartu jika memakai BPJS.

Saat user menyimpan pendaftaran:

- Sistem membuat kode antrian.
- Data masuk ke tabel `riwayat_antrian`.
- Status awal antrian menjadi `On Progress`.
- User diarahkan ke halaman riwayat antrian.

### 5. Pantau Antrian

User membuka `user/riwayat.php` untuk melihat daftar antrian miliknya.

Status antrian yang digunakan:

- `On Progress`: pendaftaran sudah masuk dan menunggu diproses admin.
- `Dipanggil`: pasien sedang dipanggil atau sedang dilayani.
- `Selesai`: pemeriksaan selesai.
- `Dibatalkan`: pendaftaran dibatalkan oleh admin.

Perubahan status antrian dilakukan oleh admin.

### 6. Resep Obat

Resep tidak dibuat otomatis oleh sistem.

Alur resep yang digunakan:

1. Dokter memeriksa pasien.
2. Dokter memberikan instruksi resep kepada admin secara manual.
3. Admin membuka menu `Kelola Antrian`.
4. Admin memilih obat dari master obat.
5. Admin mengisi jumlah obat atau qty.
6. Sistem menyimpan resep ke kolom `resep_obat_json`.

Resep yang tersimpan berisi:

- ID obat.
- Nama obat.
- Qty.
- Harga satuan.

### 7. Pesan Obat Resep

User membuka `user/pesanan.php` untuk melihat resep yang sudah diinput admin.

User tidak memilih obat bebas.

User hanya dapat memesan obat yang sudah diresepkan admin untuk antrian miliknya.

Saat user menekan tombol `Pesan Obat Resep Ini`:

- Sistem mengambil resep dari `riwayat_antrian`.
- Sistem membuat data pesanan di tabel `pesanan_obat`.
- Pesanan menyimpan kode antrian, daftar obat, qty, harga, dan waktu pemesanan.
- User diarahkan ke riwayat obat.

User hanya dapat membuat satu pesanan obat untuk satu antrian yang sama.

### 8. Riwayat Obat dan Invoice

User membuka `user/obat.php` untuk melihat riwayat pesanan obat.

Halaman ini menampilkan:

- Kode antrian.
- Data obat.
- Qty obat.
- Jadwal pengambilan.
- Status pesanan.
- Link invoice.

User dapat membuka `user/invoice.php` untuk melihat invoice.

Invoice menampilkan:

- Data pasien.
- Data kunjungan.
- Kode antrian.
- Daftar obat.
- Qty.
- Harga satuan.
- Subtotal.
- Total pembayaran.
- Status pengambilan.

### 9. Ambil Obat

Setelah user membuat pesanan obat, data masuk ke menu admin `Pengambilan Obat`.

Admin menentukan:

- Tanggal pengambilan.
- Jam pengambilan.

Setelah jadwal disimpan, user dapat melihat jadwal tersebut di:

- Dashboard user.
- Riwayat obat.
- Invoice.

Status pesanan obat yang digunakan:

- `menunggu`: pesanan sudah dibuat dan menunggu jadwal dari admin.
- `Siap Diambil`: kondisi tampilan ketika tanggal dan jam pengambilan sudah diisi.
- `diambil`: obat sudah ditandai selesai diambil oleh admin.
- `dibatalkan`: pesanan dibatalkan karena antrian terkait dibatalkan atau dihapus admin.

## Admin Flow

### 1. Login Admin

Admin login melalui `index.php`.

Data admin berasal dari tabel `admin` di database.

Setelah login berhasil, sistem memanggil `session_regenerate_id(true)` dan admin diarahkan ke `admin/index.php`.

### 2. Dashboard Operasional

Dashboard admin menampilkan ringkasan:

- Total antrian.
- Antrian aktif.
- Antrian yang belum diberi resep.
- Total pesanan obat.
- Pesanan obat yang belum dijadwalkan.
- Total dokter.
- Total obat.

Dashboard admin menyediakan akses ke:

- Kelola Antrian.
- Pengambilan Obat.
- Jadwal Dokter.
- Data Dokter.
- Daftar Obat.

### 3. Kelola Antrian

Admin membuka `admin/antrian.php`.

Admin dapat melihat:

- Kode antrian.
- Username.
- Data pasien.
- Tanggal kunjungan.
- Jam praktik.
- Dokter.
- Poli.
- Metode pembayaran.
- Resep obat.
- Status antrian.

Admin dapat melakukan:

- Mengubah status antrian.
- Menyimpan resep obat.
- Melihat pesanan berdasarkan kode antrian.
- Menghapus antrian.

Jika status antrian sudah `Selesai` atau `Dibatalkan`, status dikunci dan tidak dapat diubah kembali.

Jika antrian dibatalkan, pesanan obat terkait yang belum diambil ikut diberi status `dibatalkan`.

### 4. Atur Resep Obat

Resep obat diatur dari halaman `admin/antrian.php`.

Admin memilih obat berdasarkan daftar master obat.

Setiap obat memiliki:

- Nama.
- Stok.
- Harga.

Admin mengisi qty untuk setiap obat yang diresepkan.

Data resep disimpan dalam format JSON pada tabel `riwayat_antrian`.

Resep yang sudah disimpan akan muncul di halaman `Pesan Obat` milik user.

### 5. Pengambilan Obat

Admin membuka `admin/pesanan.php`.

Admin dapat melihat:

- ID pesanan.
- Username.
- Kode antrian.
- Daftar obat.
- Qty obat.
- Waktu pesan.
- Jadwal pengambilan.
- Status pesanan.

Admin dapat melakukan:

- Memfilter pesanan berdasarkan kode antrian.
- Menentukan tanggal pengambilan.
- Menentukan jam pengambilan.
- Menandai pesanan sudah diambil.
- Menghapus pesanan.

Pesanan dengan status `diambil` atau `dibatalkan` dikunci dari perubahan jadwal.

### 6. Jadwal Dokter

Admin membuka `admin/jadwal.php`.

Admin dapat:

- Menambahkan jadwal dokter.
- Mengatur hari praktik.
- Mengatur shift praktik.
- Menghapus jadwal dokter.

Jadwal ini digunakan user saat memilih dokter berdasarkan tanggal kunjungan dan hari praktik.

### 7. Data Dokter

Admin membuka `admin/dokter.php`.

Admin dapat:

- Menambah dokter.
- Mengubah nama dokter.
- Mengubah poli.
- Menghapus dokter.

Jika dokter dihapus, jadwal dokter terkait ikut terhapus karena relasi database menggunakan `ON DELETE CASCADE`.

### 8. Daftar Obat

Admin membuka `admin/obat.php`.

Admin dapat:

- Menambah obat.
- Mengubah nama obat.
- Mengubah stok.
- Mengubah harga.
- Menghapus obat.

Data obat digunakan saat admin membuat resep.

Harga obat yang tersimpan digunakan untuk perhitungan invoice user.

## Alur Lengkap Sistem

```text
User register/login
-> User masuk dashboard
-> User memilih rumah sakit
-> User mengisi data pasien
-> User memilih metode pembayaran
-> User memilih tanggal kunjungan
-> User memilih dokter
-> User mengonfirmasi pendaftaran
-> Sistem menyimpan antrian
-> Admin melihat antrian
-> Admin memproses status antrian
-> Dokter memberikan resep ke admin
-> Admin menginput resep dan qty obat
-> User melihat resep
-> User memesan obat resep
-> Admin melihat pesanan obat
-> Admin menentukan jadwal pengambilan
-> User melihat jadwal pengambilan
-> User membuka invoice
-> Admin menandai obat sudah diambil
```

## Struktur Halaman

### Halaman Awal

- `index.php`

### Halaman User

- `user/home.php`
- `user/hospital.php`
- `user/pasien.php`
- `user/pembayaran.php`
- `user/tanggal.php`
- `user/dokter.php`
- `user/konfirmasi.php`
- `user/riwayat.php`
- `user/pesanan.php`
- `user/obat.php`
- `user/invoice.php`
- `user/jadwal.php`

### Halaman Admin

- `admin/index.php`
- `admin/antrian.php`
- `admin/pesanan.php`
- `admin/jadwal.php`
- `admin/dokter.php`
- `admin/obat.php`

## Struktur Database Utama

Tabel utama yang digunakan:

- `admin`
- `pasien`
- `dokter`
- `jadwal_dokter`
- `obat`
- `riwayat_antrian`
- `pesanan_obat`

## Prinsip Alur

- User dan admin memiliki hak akses berbeda.
- User harus login untuk menggunakan layanan.
- Admin harus login dan memiliki role admin untuk membuka dashboard admin.
- User hanya dapat membuat pendaftaran jika tidak memiliki antrian aktif.
- Status antrian dikendalikan admin.
- Resep berasal dari dokter, lalu diinput admin.
- User tidak dapat memilih obat bebas.
- User memesan seluruh resep yang sudah ditentukan admin.
- Jadwal pengambilan obat dikendalikan admin.
- Invoice dibuat dari data pesanan obat, qty, dan harga master obat.
- Master data dokter, jadwal dokter, dan obat dikelola oleh admin.

