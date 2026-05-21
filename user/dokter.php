<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_doctor') {
            saveDoctorChoice($pdo);
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('dokter');
    }
}

if (empty($_SESSION['registration']['date'])) {
    flash('Silakan pilih tanggal kunjungan terlebih dahulu.', 'warning');
    redirect('tanggal');
}

$selectedPoli = $_GET['poli'] ?? '';
$hari = $_SESSION['registration']['day'] ?? '';
$doctors = [];
if ($selectedPoli !== '' && $hari !== '') {
    $stmt = $pdo->prepare('SELECT d.id, d.nama, d.poli, jd.shift FROM dokter d JOIN jadwal_dokter jd ON jd.dokter_id = d.id WHERE d.poli = ? AND jd.hari = ? ORDER BY d.nama');
    $stmt->execute([$selectedPoli, $hari]);
    $doctors = $stmt->fetchAll();
}
$polis = ['Poli Gigi', 'Umum', 'Poli KIA', 'Poli KB', 'Pelayanan Imunisasi', 'Pelayanan Khusus'];

renderHeader('Pilih Dokter');
renderNav();
?>
<div class="container py-5 page-shell">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <?php stepProgress(4); ?>
                    <h3 class="text-center mb-4">Form Pilih Dokter</h3>
                    <form method="get" class="mb-3">
                        <input type="hidden" name="page" value="dokter">
                        <label class="form-label">Poli untuk hari <?= e($hari ?: '-') ?></label>
                        <select class="form-select" name="poli" onchange="this.form.submit()" required>
                            <option value="">-- Pilih Poli --</option>
                            <?php foreach ($polis as $poli): ?>
                                <option value="<?= e($poli) ?>" <?= $selectedPoli === $poli ? 'selected' : '' ?>><?= e($poli) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($selectedPoli !== ''): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="save_doctor">
                            <?php if (!$doctors): ?>
                                <div class="text-danger">Tidak ada dokter tersedia untuk poli ini.</div>
                            <?php else: ?>
                                <label class="form-label">Pilih Dokter</label>
                                <div class="dokter-cards mb-3">
                                    <?php foreach ($doctors as $doctor): ?>
                                        <label class="dokter-card">
                                            <input type="radio" name="dokter_id" value="<?= (int) $doctor['id'] ?>" required>
                                            <strong><?= e($doctor['nama']) ?></strong><br>
                                            <small><?= e(formatTimeRangeAmPm($doctor['shift'])) ?></small>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center"><button class="btn btn-primary">Simpan data</button></div>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
renderFooter();

function saveDoctorChoice(PDO $pdo): void
{
    $doctorId = (int) ($_POST['dokter_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT d.id, d.nama, d.poli, jd.shift FROM dokter d JOIN jadwal_dokter jd ON jd.dokter_id = d.id WHERE d.id = ? AND jd.hari = ?');
    $stmt->execute([$doctorId, $_SESSION['registration']['day'] ?? '']);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        flash('Dokter tidak ditemukan untuk jadwal yang dipilih.', 'danger');
        redirect('dokter');
    }

    $_SESSION['registration']['doctor'] = $doctor;
    redirect('konfirmasi');
}
