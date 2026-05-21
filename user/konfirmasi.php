<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_appointment') {
            saveAppointment($pdo);
        } elseif ($action === 'reset_registration') {
            unset($_SESSION['registration']);
            redirect('pasien');
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('konfirmasi');
    }
}

if (empty($_SESSION['registration']['doctor'])) {
    flash('Silakan pilih dokter terlebih dahulu.', 'warning');
    redirect('dokter');
}

$registration = $_SESSION['registration'] ?? [];
$patient  = $registration['patient']  ?? [];
$payment  = $registration['payment']  ?? [];
$doctor   = $registration['doctor']   ?? [];

renderHeader('Konfirmasi');
renderNav();
?>
<div class="container py-5 page-shell">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <?php stepProgress(5); ?>
                    <h5 class="text-center mb-3">Pendaftaran sudah siap</h5>
                    <p class="text-center text-muted mb-4">Klik Simpan untuk menyimpan data pendaftaran kamu.</p>
                    <?php summaryCard('Pasien', ['Nama' => $patient['nama'] ?? '-', 'Tanggal Lahir' => $patient['tanggal_lahir'] ?? '-', 'Jenis Kelamin' => $patient['jenis_kelamin'] ?? '-', 'Nomor Telepon' => $patient['no_hp'] ?? '-']); ?>
                    <?php summaryCard('Tanggal', ['Tanggal Berobat' => $registration['date'] ?? '-', 'Jam' => formatTimeRangeAmPm($doctor['shift'] ?? '')]); ?>
                    <?php summaryCard('Dokter', ['Nama' => $doctor['nama'] ?? '-', 'Poli' => $doctor['poli'] ?? '-']); ?>
                    <?php summaryCard('Pembayaran', ['Metode' => $payment['metode'] ?? '-', 'Nomor Kartu' => $payment['nomor_kartu'] ?? '-']); ?>
                    <div class="text-center mt-3 d-flex gap-2 justify-content-center">
                        <form method="post"><input type="hidden" name="action" value="save_appointment"><button class="btn btn-success px-4">Simpan</button></form>
                        <form method="post"><input type="hidden" name="action" value="reset_registration"><button class="btn btn-danger px-4">Daftar Ulang</button></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
renderFooter();

function saveAppointment(PDO $pdo): void
{
    $stmt = $pdo->prepare("SELECT id FROM riwayat_antrian WHERE username = ? AND status IN ('On Progress', 'Dipanggil') LIMIT 1");
    $stmt->execute([currentUsername()]);
    if ($stmt->fetch()) {
        flash('Kamu masih memiliki antrian aktif. Selesaikan antrian tersebut sebelum mendaftar kembali.', 'danger');
        redirect('riwayat');
    }

    $registration = $_SESSION['registration'] ?? [];
    $patient = $registration['patient'] ?? [];
    $payment = $registration['payment'] ?? [];
    $doctor  = $registration['doctor']  ?? [];

    if (empty($registration['hospital'])) {
        flash('Rumah sakit belum dipilih.', 'danger');
        redirect('hospital');
    }
    if (empty($patient['nama']) || empty($patient['jenis_kelamin']) || empty($patient['tanggal_lahir']) || empty($patient['no_hp'])) {
        flash('Data pasien belum lengkap.', 'danger');
        redirect('pasien');
    }
    if (empty($payment['metode'])) {
        flash('Metode pembayaran belum dipilih.', 'danger');
        redirect('pembayaran');
    }
    if ($payment['metode'] === 'BPJS' && empty($payment['nomor_kartu'])) {
        flash('Nomor kartu BPJS wajib diisi.', 'danger');
        redirect('pembayaran');
    }
    if (empty($registration['date'])) {
        flash('Tanggal kunjungan belum dipilih.', 'danger');
        redirect('tanggal');
    }
    if (empty($doctor['nama']) || empty($doctor['poli'])) {
        flash('Dokter belum dipilih.', 'danger');
        redirect('dokter');
    }

    $kode = chr(random_int(65, 90)) . random_int(0, 9);
    $stmt = $pdo->prepare('
        INSERT INTO riwayat_antrian
        (username, kode, nama, tanggal_lahir, jenis_kelamin, no_hp, rumah_sakit, tanggal, jam, dokter, poli, metode, nomor_kartu)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        currentUsername(),
        $kode,
        $patient['nama'],
        $patient['tanggal_lahir'] ?? '',
        $patient['jenis_kelamin'] ?? '',
        $patient['no_hp'] ?? '',
        $registration['hospital'] ?? '',
        $registration['date'],
        $doctor['shift'] ?? '',
        $doctor['nama'] ?? '',
        $doctor['poli'] ?? '',
        $payment['metode'] ?? '',
        $payment['nomor_kartu'] ?? '',
    ]);

    unset($_SESSION['registration']);
    flash('Pendaftaran berhasil disimpan dengan kode antrian ' . $kode . '.');
    redirect('riwayat');
}
