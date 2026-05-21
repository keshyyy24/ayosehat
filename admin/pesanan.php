<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireAdmin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'admin_save_pickup':
                $id      = (int) ($_POST['id'] ?? 0);
                $tanggal = trim($_POST['tanggal_pengambilan'] ?? '');
                $jam     = trim($_POST['jam_pengambilan'] ?? '');
                if ($id === 0 || $tanggal === '' || $jam === '') {
                    flash('Data jadwal tidak lengkap.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'pesanan']);
                }
                $pdo->prepare('UPDATE pesanan_obat SET tanggal_pengambilan = ?, jam_pengambilan = ? WHERE id = ?')
                    ->execute([$tanggal, $jam, $id]);
                flash('Jadwal pengambilan berhasil disimpan.');
                redirect('admin-dashboard', ['tab' => 'pesanan']);
                break;

            case 'admin_mark_taken':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === 0) {
                    flash('Pesanan tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'pesanan']);
                }
                $pdo->prepare("UPDATE pesanan_obat SET status = 'diambil' WHERE id = ?")
                    ->execute([$id]);
                flash('Pesanan ditandai sudah diambil.');
                redirect('admin-dashboard', ['tab' => 'pesanan']);
                break;

            case 'admin_delete_order':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === 0) {
                    flash('Pesanan tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'pesanan']);
                }
                $pdo->prepare('DELETE FROM pesanan_obat WHERE id = ?')->execute([$id]);
                flash('Pesanan berhasil dihapus.');
                redirect('admin-dashboard', ['tab' => 'pesanan']);
                break;
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('admin-dashboard', ['tab' => 'pesanan']);
    }
}

$filterKode = trim($_GET['kode'] ?? '');

if ($filterKode !== '') {
    $stmt = $pdo->prepare('SELECT * FROM pesanan_obat WHERE kode_antrian = ? ORDER BY id DESC');
    $stmt->execute([$filterKode]);
} else {
    $stmt = $pdo->query('SELECT * FROM pesanan_obat ORDER BY id DESC');
}
$orders = $stmt->fetchAll();

renderHeader('Pengambilan Obat');
adminSidebarOpen();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Pengambilan Obat</h3>
        <p class="text-muted mb-0">Atur jadwal pengambilan dan tandai pesanan yang sudah diambil.</p>
    </div>
    <span class="badge bg-success"><?= count($orders) ?> pesanan</span>
</div>

<?php if ($filterKode): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Menampilkan pesanan untuk kode antrian: <strong><?= e($filterKode) ?></strong></span>
        <a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'pesanan'])) ?>" class="btn btn-sm btn-outline-secondary">Tampilkan Semua</a>
    </div>
<?php endif; ?>

<?php if (!$orders): ?>
    <div class="card"><div class="card-body text-muted">Belum ada pesanan obat.</div></div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th><th>User</th><th>Kode Antrian</th><th>Obat Dipesan</th>
                            <th>Waktu Pesan</th><th>Jadwal Ambil</th><th>Status</th>
                            <th style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $items = json_decode($order['obat_json'], true) ?: [];
                            $isDone = $order['status'] === 'diambil';
                            $isCancelled = $order['status'] === 'dibatalkan';
                            $isLocked = $isDone || $isCancelled;
                        ?>
                            <tr>
                                <td><strong>#<?= (int) $order['id'] ?></strong></td>
                                <td><?= e($order['username']) ?></td>
                                <td>
                                    <?php if ($order['kode_antrian']): ?>
                                        <a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'antrian'])) ?>" class="text-decoration-none">
                                            <?= e($order['kode_antrian']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <ul class="mb-0 ps-3 small">
                                        <?php foreach ($items as $item): ?>
                                            <li>
                                                <?= e(is_array($item) ? $item['nama'] : $item) ?>
                                                <?= is_array($item) ? ' &times;' . ($item['qty'] ?? 1) : '' ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td><small><?= e($order['waktu'] ?: '-') ?></small></td>
                                <td>
                                    <?php if ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                                        <strong><?= e($order['tanggal_pengambilan']) ?></strong><br>
                                        <small class="text-muted"><?= e(formatTimeAmPm($order['jam_pengambilan'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum dijadwalkan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isDone): ?>
                                        <span class="badge bg-success">Diambil</span>
                                    <?php elseif ($isCancelled): ?>
                                        <span class="badge bg-danger">Dibatalkan</span>
                                    <?php elseif ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                                        <span class="badge bg-info text-dark">Siap Diambil</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Menunggu Jadwal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isLocked): ?>
                                        <span class="text-muted small"><i class="fa-solid fa-lock me-1"></i>Terkunci</span>
                                    <?php else: ?>
                                        <form method="post" class="mb-2">
                                            <input type="hidden" name="action" value="admin_save_pickup">
                                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                            <div class="d-flex gap-1 mb-1">
                                                <input type="date" name="tanggal_pengambilan" class="form-control form-control-sm"
                                                    value="<?= e($order['tanggal_pengambilan'] ?? '') ?>" required>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <input type="time" name="jam_pengambilan" class="form-control form-control-sm"
                                                    value="<?= e($order['jam_pengambilan'] ?? '') ?>" required>
                                                <button class="btn btn-sm btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                        <?php if ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                                            <form method="post" class="mb-1">
                                                <input type="hidden" name="action" value="admin_mark_taken">
                                                <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                                <button class="btn btn-sm btn-success w-100">Tandai Diambil</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <form method="post" onsubmit="return confirm('Hapus pesanan ini?')">
                                        <input type="hidden" name="action" value="admin_delete_order">
                                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                        <button class="btn btn-sm btn-danger w-100 mt-1">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
adminSidebarClose();
renderFooter();
