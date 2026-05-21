<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

$stmt = $pdo->prepare("
    SELECT *
    FROM pesanan_obat
    WHERE username = ?
      AND tanggal_pengambilan IS NOT NULL
      AND tanggal_pengambilan != ''
      AND jam_pengambilan IS NOT NULL
      AND jam_pengambilan != ''
      AND status != 'diambil'
    ORDER BY tanggal_pengambilan ASC, jam_pengambilan ASC
");
$stmt->execute([currentUsername()]);
$pickupOrders = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM riwayat_antrian WHERE username = ?');
$stmt->execute([currentUsername()]);
$queueCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM riwayat_antrian WHERE username = ? AND status IN ('On Progress', 'Dipanggil')");
$stmt->execute([currentUsername()]);
$activeQueueCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM riwayat_antrian
    WHERE username = ?
      AND resep_obat_json IS NOT NULL
      AND resep_obat_json != ''
      AND id NOT IN (
          SELECT antrian_id FROM pesanan_obat WHERE username = ? AND antrian_id IS NOT NULL
      )
");
$stmt->execute([currentUsername(), currentUsername()]);
$availablePrescriptionCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM pesanan_obat WHERE username = ?');
$stmt->execute([currentUsername()]);
$medicineOrderCount = (int) $stmt->fetchColumn();

renderHeader('Home');
renderNav();
?>
<div data-profile-sidebar class="profile-sidebar">
    <div class="d-flex justify-content-between align-items-center">
        <h5>Profil</h5>
        <button data-profile-close class="close-btn">&times;</button>
    </div>
    <hr>
    <p><span id="username-display"><?= e(currentUsername()) ?></span></p>
    <a href="<?= e(routeUrl('obat')) ?>" class="sidebar-item d-block mb-1">Obat</a>
    <a href="<?= e(routeUrl('riwayat')) ?>" class="sidebar-item d-block">Riwayat</a>
</div>

<main class="page-shell">
    <div class="container pb-5">
        <div class="text-center mb-4">
            <h1 class="fw-bold">Dashboard User Ayosehat</h1>
            <p class="text-muted mb-0">Ikuti alur layanan dari pendaftaran, pemeriksaan, resep, sampai pengambilan obat.</p>
        </div>

        <?php if ($pickupOrders): ?>
            <div class="alert alert-info text-start shadow-sm">
                <h5 class="alert-heading mb-2">Jadwal pengambilan obat tersedia</h5>
                <?php foreach ($pickupOrders as $order): ?>
                    <?php $items = json_decode($order['obat_json'], true) ?: []; ?>
                    <div class="mb-2">
                        <strong><?= e($order['kode_antrian'] ?: 'Pesanan #' . $order['id']) ?></strong>
                        ambil pada <strong><?= e($order['tanggal_pengambilan']) ?></strong>
                        jam <strong><?= e(formatTimeAmPm($order['jam_pengambilan'])) ?></strong>.
                        <span class="text-muted">Obat: <?= e(implode(', ', array_map(fn($i) => is_array($i) ? $i['nama'] . ' ×' . ($i['qty'] ?? 1) : $i, $items))) ?></span>
                    </div>
                <?php endforeach; ?>
                <a href="<?= e(routeUrl('obat')) ?>" class="btn btn-sm btn-primary mt-2">Lihat Riwayat Obat</a>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Antrian</div>
                        <div class="fs-3 fw-bold"><?= $queueCount ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Antrian Aktif</div>
                        <div class="fs-3 fw-bold"><?= $activeQueueCount ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Resep Siap Dipesan</div>
                        <div class="fs-3 fw-bold"><?= $availablePrescriptionCount ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Pesanan Obat</div>
                        <div class="fs-3 fw-bold"><?= $medicineOrderCount ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6 col-xl-3">
                <div class="card role-card shadow-sm h-100">
                <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="fa-solid fa-calendar-plus fa-2x text-primary"></i>
                            <h5 class="card-title mb-0">1. Daftar Berobat</h5>
                        </div>
                        <p class="card-text text-muted">Mulai pendaftaran dengan memilih rumah sakit, data pasien, tanggal, dan dokter.</p>
                        <a href="<?= e(routeUrl('hospital')) ?>" class="btn btn-primary w-100">Buat Pendaftaran</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card role-card shadow-sm h-100">
                <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="fa-solid fa-list-check fa-2x text-success"></i>
                            <h5 class="card-title mb-0">2. Pantau Antrian</h5>
                        </div>
                        <p class="card-text text-muted">Cek status pendaftaran: On Progress, Dipanggil, Selesai, atau Dibatalkan.</p>
                        <a href="<?= e(routeUrl('riwayat')) ?>" class="btn btn-success w-100">Lihat Antrian</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card role-card shadow-sm h-100">
                <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="fa-solid fa-prescription-bottle-medical fa-2x text-danger"></i>
                            <h5 class="card-title mb-0">3. Pesan Resep</h5>
                        </div>
                        <p class="card-text text-muted">Pesan obat hanya dari resep yang sudah diatur admin untuk antrianmu.</p>
                        <a href="<?= e(routeUrl('pesanan')) ?>" class="btn btn-danger w-100">Pesan Obat</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card role-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="fa-solid fa-box-open fa-2x text-dark"></i>
                            <h5 class="card-title mb-0">4. Ambil Obat</h5>
                        </div>
                        <p class="card-text text-muted">Lihat jadwal pengambilan obat yang sudah ditentukan admin.</p>
                        <a href="<?= e(routeUrl('obat')) ?>" class="btn btn-dark w-100">Riwayat Obat</a>
                    </div>
                </div>
            </div>
        </div>

        <section>
            <h2 class="text-center fw-bold mb-3">Informasi Layanan</h2>
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <?php foreach ([
                    ['jadwal', 'Jadwal Dokter', 'fa-solid fa-user-doctor'],
                    ['fasilitas', 'Fasilitas', 'fa-regular fa-hospital'],
                    ['panduan', 'Panduan', 'fa-solid fa-book'],
                    ['about', 'Tentang', 'fa-solid fa-circle-exclamation'],
                    ['contact', 'Kontak', 'fa-solid fa-phone'],
                    ['reward', 'Reward', 'fa-regular fa-star'],
                ] as $item): ?>
                    <div class="col">
                        <a href="<?= e(routeUrl($item[0])) ?>" class="text-decoration-none text-reset">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <i class="<?= e($item[2]) ?> fa-2x text-primary"></i>
                                    <h6 class="mb-0"><?= e($item[1]) ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
<footer><p>Alawi &copy; 2025 | Terus berkembang, terus berinovasi.</p></footer>
<?php
renderFooter();
