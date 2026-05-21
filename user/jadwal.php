<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

$rows = $pdo->query('SELECT d.nama, d.poli, jd.hari, jd.shift FROM dokter d JOIN jadwal_dokter jd ON d.id = jd.dokter_id ORDER BY d.nama, jd.hari')->fetchAll();
$grouped = [];
foreach ($rows as $row) {
    $key = $row['nama'] . '|' . $row['poli'];
    $grouped[$key]['nama'] = $row['nama'];
    $grouped[$key]['poli'] = $row['poli'];
    $grouped[$key]['jadwal'][] = ['hari' => $row['hari'], 'shift' => $row['shift']];
}

renderHeader('Jadwal Dokter');
renderNav();
?>
<div class="container py-5 page-shell">
    <h2 class="mb-4 text-center">Cari Jadwal Dokter</h2>
    <input data-search class="form-control mb-4" placeholder="Cari nama dokter atau poli...">
    <div class="row g-4">
        <?php foreach ($grouped as $doctor): ?>
            <div class="col-md-6 col-lg-4" data-search-item>
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= e($doctor['nama']) ?></h5>
                        <span class="badge bg-info mb-2"><?= e($doctor['poli']) ?></span>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($doctor['jadwal'] as $schedule): ?>
                                <li class="list-group-item d-flex justify-content-between"><?= e($schedule['hari']) ?><span class="badge bg-secondary"><?= e(formatTimeRangeAmPm($schedule['shift'])) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
renderFooter();
