# Alur Aplikasi Ayosehat

Dokumen ini menjelaskan alur penggunaan aplikasi dari sisi user dan admin.

## Role

Ada dua role utama:

- `user`: pasien yang melakukan pendaftaran berobat dan pemesanan obat berdasarkan resep.
- `admin`: petugas yang mengelola antrian, resep, jadwal pengambilan obat, dokter, jadwal dokter, dan master obat.

## User Flow

### 1. Register atau Login

User masuk ke aplikasi melalui halaman login.

Jika belum punya akun, user melakukan register terlebih dahulu. Setelah login berhasil, user masuk ke dashboard user.

### 2. Dashboard User

Dashboard user menampilkan ringkasan:

- Total antrian
- Antrian aktif
- Resep siap dipesan
- Total pesanan obat
- Notifikasi jadwal pengambilan obat jika admin sudah menentukan jadwal

Dashboard user juga menampilkan empat langkah utama:

1. Daftar Berobat
2. Pantau Antrian
3. Pesan Resep
4. Ambil Obat

### 3. Daftar Berobat

User membuat pendaftaran berobat dengan alur:

1. Pilih rumah sakit
2. Isi data pasien
3. Pilih metode pembayaran
4. Pilih tanggal kunjungan
5. Pilih dokter berdasarkan poli dan jadwal
6. Konfirmasi pendaftaran

Setelah disimpan, data masuk ke menu admin `Kelola Antrian`.

### 4. Pantau Antrian

User dapat melihat status antrian di halaman riwayat antrian.

Status yang mungkin muncul:

- `On Progress`: pendaftaran sudah masuk dan menunggu diproses admin.
- `Dipanggil`: pasien sedang dipanggil atau sedang dilayani.
- `Selesai`: pemeriksaan selesai.
- `Dibatalkan`: pendaftaran dibatalkan oleh admin.

User hanya memantau status. Perubahan status dilakukan oleh admin.

### 5. Pesan Resep

User tidak bisa memilih obat secara bebas.

Obat hanya bisa dipesan jika admin sudah mengatur resep pada antrian user.

**Konteks resep:** Setelah dokter memeriksa pasien, dokter menyampaikan daftar obat yang dibutuhkan kepada petugas (admin) secara langsung atau melalui saluran komunikasi lain (misalnya WhatsApp). Admin kemudian menginput daftar obat tersebut ke dalam sistem — bukan sistem yang otomatis membaca dari dokter.

Alurnya:

1. Dokter memberikan resep obat kepada admin secara manual (tatap muka / WA / dll.).
2. Admin membuka `Kelola Antrian`, menemukan antrian pasien yang bersangkutan.
3. Admin mencentang obat-obat sesuai resep dokter dan mengisi jumlah (qty) masing-masing obat.
4. Admin klik `Simpan Resep`.
5. User membuka menu `Pesan Obat`.
6. User melihat daftar obat beserta jumlah dan harga per item yang sudah ditentukan admin.
7. User klik `Pesan Obat Resep Ini` untuk mengkonfirmasi seluruh resep.

User tidak memilih subset obat — seluruh resep dipesan sekaligus sesuai instruksi dokter. Ini disengaja agar tidak ada obat yang terlewat dari resep.

### 6. Ambil Obat

Setelah user memesan obat resep, data masuk ke menu admin `Pengambilan Obat`.

Admin menentukan:

- Tanggal pengambilan
- Jam pengambilan

Setelah admin menyimpan jadwal, user dapat melihat jadwal tersebut di:

- Dashboard user
- Riwayat pemesanan obat

Status pesanan obat:

- `Menunggu Jadwal`: admin belum menentukan jadwal pengambilan.
- `Siap Diambil`: admin sudah menentukan tanggal dan jam pengambilan.
- `Sudah Diambil`: admin sudah menandai obat selesai diambil.

## Admin Flow

### 1. Login Admin

Admin login dengan akun khusus.

```text
username: ayosehat
password: admin123
```

Setelah login berhasil, admin masuk ke dashboard operasional.

### 2. Dashboard Operasional

Dashboard admin menampilkan ringkasan:

- Total antrian
- Antrian aktif
- Antrian yang belum diberi resep
- Total pesanan obat
- Pesanan obat yang belum dijadwalkan
- Total dokter
- Total obat

Dashboard ini berfungsi sebagai pusat kontrol operasional.

### 3. Kelola Antrian

Menu ini digunakan untuk memproses pendaftaran user.

Admin dapat melihat:

- Kode antrian
- Username
- Data pasien
- Jadwal kunjungan
- Dokter dan poli
- Metode pembayaran
- Status antrian
- Resep obat

Admin dapat melakukan:

- Mengubah status antrian
- Menentukan resep obat untuk user
- Menghapus antrian jika diperlukan

Status antrian:

- `On Progress`
- `Dipanggil`
- `Selesai`
- `Dibatalkan`

### 4. Atur Resep Obat

Resep obat diatur dari menu `Kelola Antrian`.

**Sumber resep:** Admin menerima instruksi resep dari dokter secara manual — bisa tatap muka langsung setelah pemeriksaan, atau melalui saluran komunikasi seperti WhatsApp. Admin kemudian menginput daftar obat tersebut ke sistem berdasarkan instruksi dokter.

Admin mencentang obat dari daftar master obat dan mengisi jumlah (qty) untuk masing-masing obat. Harga per item sudah tersimpan di master obat.

Obat yang dipilih admin (beserta qty dan harga) akan muncul di halaman `Pesan Obat` milik user.

Jika admin belum memilih resep, user tidak bisa memesan obat untuk antrian tersebut.

### 5. Pengambilan Obat

Menu ini digunakan untuk memproses pesanan obat yang sudah dibuat user.

Admin dapat melihat:

- Username
- Waktu pemesanan
- Kode antrian
- Daftar obat
- Jadwal pengambilan
- Status pengambilan

Admin dapat melakukan:

- Menentukan tanggal pengambilan
- Menentukan jam pengambilan
- Menandai obat sudah diambil

### 6. Jadwal Dokter

Menu ini digunakan untuk mengatur jadwal praktik dokter.

Admin dapat:

- Menambahkan jadwal dokter per hari
- Mengubah shift dokter pada hari tertentu
- Menghapus jadwal dokter

Jadwal ini dipakai user saat memilih dokter ketika mendaftar berobat.

### 7. Master Dokter

Menu ini digunakan untuk mengelola data dokter.

Admin dapat:

- Menambah dokter
- Mengubah nama dokter
- Mengubah poli
- Menghapus dokter

Jika dokter dihapus, jadwal dokter terkait ikut dihapus.

### 8. Daftar Obat

Menu ini digunakan untuk mengatur master obat.

Admin dapat:

- Menambah obat beserta stok awal dan harga satuan
- Mengubah nama, stok, dan harga obat
- Menghapus obat

Setiap obat menyimpan:

- **Nama**: nama generik atau merek obat
- **Stok**: jumlah unit yang tersedia di apotek
- **Harga**: harga satuan per item (Rp)

Daftar obat ini dipakai admin saat membuat resep untuk user. Harga yang tersimpan di master obat otomatis muncul di invoice user.

## Ringkasan Alur Lengkap

```text
User register/login
-> User daftar berobat
-> Admin melihat pendaftaran di Kelola Antrian
-> Admin memproses status antrian
-> Admin menentukan resep obat
-> User memesan obat dari resep admin
-> Admin menentukan jadwal pengambilan obat
-> User melihat jadwal pengambilan
-> Admin menandai obat sudah diambil
```

## Prinsip Alur

- User tidak bisa memilih obat bebas — seluruh resep berasal dari dokter via admin.
- User hanya bisa memesan obat yang sudah diresepkan admin, dan harus memesan semua sekaligus.
- User tidak bisa membatalkan pesanan obat sendiri — jika ingin membatalkan, harus menghubungi admin secara langsung.
- Status antrian dikendalikan admin.
- Jadwal pengambilan obat dikendalikan admin.
- Master data dokter, jadwal dokter, dan obat dikendalikan admin.
