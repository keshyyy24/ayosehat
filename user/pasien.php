<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_pasien') {
            $_SESSION['registration']['patient'] = [
                'nama'          => trim($_POST['nama'] ?? ''),
                'jenis_kelamin' => trim($_POST['jenis_kelamin'] ?? ''),
                'tanggal_lahir' => trim($_POST['tanggal_lahir'] ?? ''),
                'no_hp'         => trim($_POST['no_hp'] ?? ''),
            ];
            redirect('pembayaran');
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('pasien');
    }
}

if (empty($_SESSION['registration']['hospital'])) {
    flash('Silakan pilih rumah sakit terlebih dahulu.', 'warning');
    redirect('hospital');
}

renderHeader('Data Pasien');
renderNav();
?>
<div class="container py-5 page-shell">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card pasien-card shadow-lg">
                <div class="card-body p-4">
                    <h5 class="text-center text-muted mb-4">Rumah Sakit: <?= e($_SESSION['registration']['hospital'] ?? 'Belum dipilih') ?></h5>
                    <?php stepProgress(1); ?>
                    <h3 class="text-center mb-4">Form Input Data Pasien</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="save_pasien">
                        <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama" required></div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="jenis_kelamin" value="Laki-laki" required><label class="form-check-label">Laki-laki</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="jenis_kelamin" value="Perempuan" required><label class="form-check-label">Perempuan</label></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="tanggal_lahir" required></div>
                        <div class="mb-3"><label class="form-label">No HP</label><input type="tel" class="form-control" name="no_hp" required></div>
                        <div class="mb-3"><label class="form-label">Agama</label><select class="form-select" name="agama"><option>Islam</option><option>Kristen</option><option>Katolik</option><option>Hindu</option><option>Buddha</option><option>Konghucu</option></select></div>
                        <div class="mb-3"><label class="form-label">Pekerjaan</label><input type="text" class="form-control" name="pekerjaan"></div>
                        <div class="mb-3"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="3"></textarea></div>
                        <div class="text-center mt-3 mb-4"><button class="btn btn-primary">Simpan data</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
renderFooter();
