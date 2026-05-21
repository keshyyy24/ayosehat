<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'choose_hospital') {
            $_SESSION['registration']['hospital'] = trim($_POST['hospital'] ?? '');
            redirect('pasien');
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('hospital');
    }
}

$stmt = $pdo->prepare("SELECT kode, status FROM riwayat_antrian WHERE username = ? AND status IN ('On Progress', 'Dipanggil') LIMIT 1");
$stmt->execute([currentUsername()]);
$activeQueue = $stmt->fetch();

renderHeader('Pilih Rumah Sakit');
renderNav();

if ($activeQueue) {
    ?>
    <div class="container py-5 page-shell">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="alert alert-warning text-center shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 d-block"></i>
                    <h5 class="fw-bold">Kamu masih memiliki antrian aktif</h5>
                    <p class="mb-3">Antrian <strong><?= e($activeQueue['kode']) ?></strong> dengan status <strong><?= e($activeQueue['status']) ?></strong> belum selesai. Selesaikan atau tunggu antrian tersebut sebelum mendaftar kembali.</p>
                    <a href="<?= e(routeUrl('riwayat')) ?>" class="btn btn-primary">Lihat Riwayat Antrian</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    renderFooter();
    exit;
}

$hospitals = [
    'RS Sehat Selalu' => 'Jl. Kesehatan No. 1',
    'RS Gas Medika'   => 'Jl. Gas Alam No. 2',
    'RS Bahagia'      => 'Jl. Bahagia No. 3',
    'RS Citra Sehat'  => 'Jl. Citra Harmoni No. 4',
    'RS Anggrek'      => 'Jl. Bunga Anggrek No. 5',
    'RS Kasih Ibu'    => 'Jl. Kasih Ibu No. 6',
    'RS Mandiri'      => 'Jl. Mandiri No. 7',
    'RS Pelita Hati'  => 'Jl. Hati Mulia No. 8',
];
?>
<div class="hospital-container py-5 page-shell">
    <h2 class="text-center mb-4 fw-bold hospital-title">Pilih Rumah Sakit</h2>
    <p class="text-center text-muted mb-5 hospital-subtitle">Klik salah satu rumah sakit untuk melanjutkan</p>
    <div class="hospital-wrapper bg-white p-4 p-md-5 hospital-rounded hospital-shadow">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($hospitals as $name => $address): ?>
                <div class="col">
                    <form method="post">
                        <input type="hidden" name="action" value="choose_hospital">
                        <input type="hidden" name="hospital" value="<?= e($name) ?>">
                        <button class="card hospital-card h-100 border-0 hospital-rounded p-2 text-start w-100" style="cursor: pointer;">
                            <span class="card-body">
                                <span class="card-title hospital-card-title d-block"><?= e($name) ?></span>
                                <span class="card-text hospital-card-text d-block"><i class="fa-solid fa-location-dot text-danger me-2"></i><?= e($address) ?></span>
                            </span>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
renderFooter();
