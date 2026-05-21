<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_order') {
            saveMedicineOrder($pdo);
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('pesanan');
    }
}

$stmt = $pdo->prepare("
    SELECT id, kode, tanggal, dokter, poli, resep_obat_json
    FROM riwayat_antrian
    WHERE username = ?
      AND resep_obat_json IS NOT NULL
      AND resep_obat_json != ''
      AND status != 'Dibatalkan'
    ORDER BY id DESC
");
$stmt->execute([currentUsername()]);
$prescriptions = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT antrian_id FROM pesanan_obat WHERE username = ? AND antrian_id IS NOT NULL');
$stmt->execute([currentUsername()]);
$orderedQueueIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

renderHeader('Pesan Obat');
renderNav();
?>
<section class="container mt-5 page-shell">
    <div class="card shadow p-4">
        <h2 class="mb-4 text-center">Obat dari Resep Admin</h2>
        <?php if (!$prescriptions): ?>
            <p class="text-center text-muted mb-0">Belum ada resep obat dari admin. Selesaikan pendaftaran dan tunggu admin mengatur resep terlebih dahulu.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($prescriptions as $prescription): ?>
                    <?php
                    $items = json_decode($prescription['resep_obat_json'], true) ?: [];
                    $alreadyOrdered = in_array((int) $prescription['id'], $orderedQueueIds, true);
                    ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">Kode Antrian: <?= e($prescription['kode']) ?></h5>
                                    <?php if ($alreadyOrdered): ?><span class="badge bg-success">Sudah Dipesan</span><?php endif; ?>
                                </div>
                                <p class="text-muted mb-2">
                                    <?= e($prescription['tanggal']) ?>,
                                    <?= e($prescription['dokter'] ?: '-') ?>
                                    (<?= e($prescription['poli'] ?: '-') ?>)
                                </p>
                                <strong>Resep obat:</strong>
                                <table class="table table-sm mb-3 mt-1">
                                    <thead><tr><th>Nama Obat</th><th class="text-center">Qty</th><th class="text-end">Harga/item</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($items as $item):
                                        $nama  = is_array($item) ? $item['nama']  : $item;
                                        $qty   = is_array($item) ? ($item['qty']   ?? 1) : 1;
                                        $harga = is_array($item) ? ($item['harga'] ?? 0) : 0;
                                    ?>
                                        <tr>
                                            <td><?= e($nama) ?></td>
                                            <td class="text-center"><?= $qty ?>x</td>
                                            <td class="text-end">Rp <?= number_format($harga, 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if ($alreadyOrdered): ?>
                                    <a href="<?= e(routeUrl('obat')) ?>" class="btn btn-outline-primary w-100">Lihat Riwayat Obat</a>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="save_order">
                                        <input type="hidden" name="antrian_id" value="<?= (int) $prescription['id'] ?>">
                                        <button class="btn btn-primary w-100">Pesan Obat Resep Ini</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
renderFooter();

function saveMedicineOrder(PDO $pdo): void
{
    $queueId = (int) ($_POST['antrian_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT id, kode, resep_obat_json FROM riwayat_antrian WHERE id = ? AND username = ?');
    $stmt->execute([$queueId, currentUsername()]);
    $queue = $stmt->fetch();

    if (!$queue) {
        flash('Resep tidak ditemukan untuk akun ini.', 'danger');
        redirect('pesanan');
    }

    $prescribedItems = json_decode($queue['resep_obat_json'] ?? '[]', true);
    if (!is_array($prescribedItems) || count($prescribedItems) === 0) {
        flash('Admin belum mengatur resep obat untuk antrian ini.', 'warning');
        redirect('pesanan');
    }

    $stmt = $pdo->prepare('INSERT INTO pesanan_obat (username, antrian_id, kode_antrian, obat_json, waktu) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([currentUsername(), $queueId, $queue['kode'], $queue['resep_obat_json'], date('Y-m-d H:i:s')]);
    flash('Pesanan obat berhasil dibuat.');
    redirect('obat');
}
