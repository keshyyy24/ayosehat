<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_payment') {
            $_SESSION['registration']['payment'] = [
                'metode'      => trim($_POST['metode'] ?? ''),
                'nomor_kartu' => trim($_POST['nomor_kartu'] ?? ''),
            ];
            redirect('tanggal');
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('pembayaran');
    }
}

if (empty($_SESSION['registration']['patient'])) {
    flash('Silakan isi data pasien terlebih dahulu.', 'warning');
    redirect('pasien');
}

renderHeader('Pembayaran');
renderNav();
?>
<div class="container py-5 page-shell">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card pembayaran-card shadow-lg">
                <div class="card-body p-4">
                    <?php stepProgress(2); ?>
                    <h3 class="text-center mb-4">Pembayaran</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="save_payment">
                        <label class="form-label">Pembayaran</label>
                        <select class="form-select" name="metode" data-payment-select required>
                            <option value="">-- Pilih Pembayaran --</option>
                            <option value="BPJS">BPJS</option>
                            <option value="Umum">Umum</option>
                        </select>
                        <div data-bpjs-section style="display: none;" class="mt-3">
                            <input type="text" placeholder="Nomor Kartu" class="form-control" name="nomor_kartu" data-bpjs-input>
                        </div>
                        <div class="text-center mt-4"><button class="btn btn-primary">Simpan data</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
renderFooter();
