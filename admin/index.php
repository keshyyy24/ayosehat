<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireAdmin();

renderHeader('Dashboard Admin');
adminSidebarOpen();

$queueTotal            = (int) $pdo->query('SELECT COUNT(*) FROM riwayat_antrian')->fetchColumn();
$queueActive           = (int) $pdo->query("SELECT COUNT(*) FROM riwayat_antrian WHERE status IN ('On Progress', 'Dipanggil')")->fetchColumn();
$queueNeedPrescription = (int) $pdo->query("SELECT COUNT(*) FROM riwayat_antrian WHERE status != 'Dibatalkan' AND (resep_obat_json IS NULL OR resep_obat_json = '')")->fetchColumn();
$medicineOrders        = (int) $pdo->query('SELECT COUNT(*) FROM pesanan_obat')->fetchColumn();
$pickupNeedSchedule    = (int) $pdo->query("SELECT COUNT(*) FROM pesanan_obat WHERE status NOT IN ('diambil', 'dibatalkan') AND (tanggal_pengambilan IS NULL OR tanggal_pengambilan = '' OR jam_pengambilan IS NULL OR jam_pengambilan = '')")->fetchColumn();
$doctorTotal           = (int) $pdo->query('SELECT COUNT(*) FROM dokter')->fetchColumn();
$medicineTotal         = (int) $pdo->query('SELECT COUNT(*) FROM obat')->fetchColumn();
?>

<div class="mb-4">
    <h2 class="mb-1">Dashboard Operasional</h2>
    <p class="text-muted mb-0">Urutan kerja admin: proses antrian, atur resep, jadwalkan pengambilan obat, lalu rawat master data.</p>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Antrian',     $queueTotal,            'bg-primary'],
        ['Antrian Aktif',     $queueActive,           'bg-warning text-dark'],
        ['Belum Ada Resep',   $queueNeedPrescription, 'bg-danger'],
        ['Pesanan Obat',      $medicineOrders,        'bg-success'],
        ['Belum Dijadwalkan', $pickupNeedSchedule,    'bg-info text-dark'],
        ['Dokter',            $doctorTotal,           'bg-secondary'],
        ['Obat',              $medicineTotal,         'bg-dark'],
    ] as $stat): ?>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= e($stat[0]) ?></div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="fs-3 fw-bold"><?= (int) $stat[1] ?></span>
                        <span class="badge <?= e($stat[2]) ?>">&nbsp;</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <?php foreach ([
        ['Kelola Antrian',   'Lihat pendaftaran user, ubah status pemeriksaan, dan atur resep obat.',   'antrian', 'fa-solid fa-clipboard-list',             'btn-primary'],
        ['Pengambilan Obat', 'Jadwalkan tanggal dan jam pengambilan obat yang sudah dipesan user.',     'pesanan', 'fa-solid fa-prescription-bottle-medical', 'btn-success'],
        ['Jadwal Dokter',    'Atur hari dan shift dokter agar user bisa memilih jadwal yang tersedia.', 'jadwal',  'fa-solid fa-calendar-days',               'btn-info'],
        ['Data Dokter',      'Tambah, ubah, atau hapus data dokter dan poli.',                          'dokter',  'fa-solid fa-user-doctor',                 'btn-secondary'],
        ['Daftar Obat',      'Atur daftar obat yang bisa diresepkan admin untuk user.',                 'obat',    'fa-solid fa-pills',                       'btn-dark'],
    ] as $item): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="<?= e($item[3]) ?> fa-2x text-primary"></i>
                        <h5 class="mb-0"><?= e($item[0]) ?></h5>
                    </div>
                    <p class="text-muted"><?= e($item[1]) ?></p>
                    <a href="<?= e(routeUrl('admin-dashboard', ['tab' => $item[2]])) ?>" class="btn <?= e($item[4]) ?> w-100">Buka</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
adminSidebarClose();
renderFooter();
