<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'complete_history') {
            updateHistoryStatus($pdo, 'Selesai');
        } elseif ($action === 'delete_history') {
            deleteHistory($pdo);
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('riwayat');
    }
}

$stmt = $pdo->prepare('SELECT * FROM riwayat_antrian WHERE username = ? ORDER BY id DESC');
$stmt->execute([currentUsername()]);
$histories = $stmt->fetchAll();

renderHeader('Riwayat');
renderNav();
?>
<div class="container page-shell">
    <h2 class="mb-4 text-center color">Riwayat Antrian</h2>
    <?php if (!$histories): ?>
        <p class="text-center text-muted">Belum ada riwayat antrian.</p>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($histories as $item): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title">Kode: <?= e($item['kode']) ?></h5>
                                <span class="badge <?= queueStatusClass($item['status']) ?>"><?= e($item['status']) ?></span>
                            </div>
                            <p class="card-text mb-1"><strong>Nama:</strong> <?= e($item['nama']) ?></p>
                            <p class="card-text mb-1"><strong>Dokter:</strong> <?= e($item['dokter']) ?> (<?= e($item['poli']) ?>)</p>
                            <p class="card-text mb-1"><strong>Tanggal:</strong> <?= e($item['tanggal']) ?></p>
                            <p class="card-text mb-1"><strong>Jam:</strong> <?= e(formatTimeRangeAmPm($item['jam'])) ?></p>
                            <p class="card-text"><strong>Metode:</strong> <?= e($item['metode']) ?></p>
                            <p class="text-muted small mb-0">Status antrian diperbarui oleh admin.</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
renderFooter();

function updateHistoryStatus(PDO $pdo, string $status): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE riwayat_antrian SET status = ? WHERE id = ? AND username = ?');
    $stmt->execute([$status, $id, currentUsername()]);
    redirect('riwayat');
}

function deleteHistory(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM riwayat_antrian WHERE id = ? AND username = ?');
    $stmt->execute([$id, currentUsername()]);
    redirect('riwayat');
}
