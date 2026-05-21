<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_date') {
            $date = trim($_POST['tanggal'] ?? '');
            $_SESSION['registration']['date'] = $date;
            $_SESSION['registration']['day']  = indonesianDayName($date);
            redirect('dokter');
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('tanggal');
    }
}

if (empty($_SESSION['registration']['payment'])) {
    flash('Silakan pilih metode pembayaran terlebih dahulu.', 'warning');
    redirect('pembayaran');
}

renderHeader('Tanggal');
renderNav();
?>
<div class="container py-5 page-shell">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <?php stepProgress(3); ?>
                    <h3 class="text-center mb-4">Form Pilih Tanggal</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="save_date">
                        <div class="mb-3"><label class="form-label">Tanggal</label><input type="date" class="form-control" name="tanggal" required></div>
                        <div class="text-center mt-3 mb-4"><button class="btn btn-primary">Simpan data</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
renderFooter();
