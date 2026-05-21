<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireAdmin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'admin_update_queue_status':
                $id               = (int) ($_POST['id']   ?? 0);
                $status           = trim($_POST['status'] ?? '');
                $allowedStatuses  = ['On Progress', 'Dipanggil', 'Selesai', 'Dibatalkan'];
                $terminalStatuses = ['Selesai', 'Dibatalkan'];
                if ($id === 0 || !in_array($status, $allowedStatuses, true)) {
                    flash('Status antrian tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'antrian']);
                }
                $stmt = $pdo->prepare('SELECT status FROM riwayat_antrian WHERE id = ?');
                $stmt->execute([$id]);
                $current = $stmt->fetchColumn();
                if (in_array($current, $terminalStatuses, true)) {
                    flash('Antrian yang sudah ' . $current . ' tidak dapat diubah statusnya.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'antrian']);
                }
                $pdo->prepare('UPDATE riwayat_antrian SET status = ? WHERE id = ?')->execute([$status, $id]);
                if ($status === 'Dibatalkan') {
                    $pdo->prepare("UPDATE pesanan_obat SET status = 'dibatalkan' WHERE antrian_id = ? AND status != 'diambil'")->execute([$id]);
                }
                flash('Status antrian berhasil diperbarui.');
                redirect('admin-dashboard', ['tab' => 'antrian']);
                break;

            case 'admin_save_queue_prescription':
                $id         = (int) ($_POST['id'] ?? 0);
                $checkedIds = array_keys($_POST['resep_obat'] ?? []);
                $qtyMap     = $_POST['qty_obat'] ?? [];
                if ($id === 0) {
                    flash('Antrian tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'antrian']);
                }
                $items = [];
                if ($checkedIds) {
                    $placeholders = implode(',', array_fill(0, count($checkedIds), '?'));
                    $stmt = $pdo->prepare("SELECT id, nama, harga FROM obat WHERE id IN ($placeholders)");
                    $stmt->execute($checkedIds);
                    foreach ($stmt->fetchAll() as $med) {
                        $qty     = max(1, (int) ($qtyMap[$med['id']] ?? 1));
                        $items[] = ['id' => (int) $med['id'], 'nama' => $med['nama'], 'qty' => $qty, 'harga' => (int) $med['harga']];
                    }
                }
                $json = $items ? json_encode($items) : null;
                $pdo->prepare('UPDATE riwayat_antrian SET resep_obat_json = ? WHERE id = ?')->execute([$json, $id]);
                flash($items ? 'Resep obat berhasil disimpan.' : 'Resep obat dikosongkan.');
                redirect('admin-dashboard', ['tab' => 'antrian']);
                break;

            case 'admin_delete_queue':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === 0) {
                    flash('Antrian tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'antrian']);
                }
                $pdo->prepare('DELETE FROM riwayat_antrian WHERE id = ?')->execute([$id]);
                flash('Antrian berhasil dihapus.');
                redirect('admin-dashboard', ['tab' => 'antrian']);
                break;
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('admin-dashboard', ['tab' => 'antrian']);
    }
}

$queues    = $pdo->query('SELECT * FROM riwayat_antrian ORDER BY id DESC')->fetchAll();
$medicines = $pdo->query('SELECT id, nama, harga, stok FROM obat ORDER BY nama')->fetchAll();
$statuses  = ['On Progress', 'Dipanggil', 'Selesai', 'Dibatalkan'];

renderHeader('Kelola Antrian');
adminSidebarOpen();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Kelola Antrian</h3>
        <p class="text-muted mb-0">Data pendaftaran user yang sudah dikonfirmasi.</p>
    </div>
    <span class="badge bg-primary"><?= count($queues) ?> antrian</span>
</div>

<?php if (!$queues): ?>
    <div class="card"><div class="card-body text-muted">Belum ada pendaftaran user.</div></div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th><th>User</th><th>Pasien</th><th>Jadwal</th>
                            <th>Dokter</th><th>Pembayaran</th>
                            <th style="min-width:240px;">Resep Obat</th>
                            <th>Status</th><th style="width:230px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queues as $queue): ?>
                            <tr>
                                <td><strong><?= e($queue['kode']) ?></strong></td>
                                <td><?= e($queue['username']) ?></td>
                                <td>
                                    <strong><?= e($queue['nama']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= e($queue['jenis_kelamin'] ?: '-') ?>,
                                        <?= e($queue['tanggal_lahir'] ?: '-') ?><br>
                                        HP: <?= e($queue['no_hp'] ?: '-') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= e($queue['tanggal']) ?><br>
                                    <small class="text-muted"><?= e(formatTimeRangeAmPm($queue['jam'])) ?></small>
                                </td>
                                <td>
                                    <?= e($queue['dokter'] ?: '-') ?><br>
                                    <small class="text-muted"><?= e($queue['poli'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <?= e($queue['metode'] ?: '-') ?><br>
                                    <?php if ($queue['nomor_kartu']): ?>
                                        <small class="text-muted"><?= e($queue['nomor_kartu']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $queueFinal    = in_array($queue['status'], ['Selesai', 'Dibatalkan'], true);
                                    $selectedResep = json_decode($queue['resep_obat_json'] ?? '[]', true) ?: [];
                                    $selectedMap   = [];
                                    foreach ($selectedResep as $sr) {
                                        if (is_array($sr) && isset($sr['id'])) {
                                            $selectedMap[(int) $sr['id']] = (int) ($sr['qty'] ?? 1);
                                        }
                                    }
                                    if ($queueFinal && $selectedResep): ?>
                                        <ul class="mb-0 ps-3 small">
                                            <?php foreach ($selectedResep as $sr): ?>
                                                <li><?= e(is_array($sr) ? $sr['nama'] : $sr) ?><?= is_array($sr) ? ' &times;' . $sr['qty'] : '' ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif ($queueFinal): ?>
                                        <span class="text-muted small">—</span>
                                    <?php else: ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="admin_save_queue_prescription">
                                            <input type="hidden" name="id" value="<?= (int) $queue['id'] ?>">
                                            <div class="border rounded p-2 bg-white" style="max-height:220px;overflow-y:auto;min-width:280px;">
                                                <?php foreach ($medicines as $index => $med): ?>
                                                    <?php
                                                    $inputId   = 'resep-' . (int) $queue['id'] . '-' . $index;
                                                    $isChecked = isset($selectedMap[(int) $med['id']]);
                                                    $savedQty  = $isChecked ? $selectedMap[(int) $med['id']] : 0;
                                                    ?>
                                                    <div class="d-flex align-items-center gap-2 mb-1 resep-row">
                                                        <input class="form-check-input mt-0 flex-shrink-0" type="checkbox"
                                                            name="resep_obat[<?= (int) $med['id'] ?>]" value="1"
                                                            id="<?= e($inputId) ?>"
                                                            <?= $isChecked ? 'checked' : '' ?>
                                                            onchange="toggleResepQty(this)">
                                                        <label class="form-check-label flex-grow-1 small" for="<?= e($inputId) ?>" style="cursor:pointer;">
                                                            <?= e($med['nama']) ?><br>
                                                            <span class="text-muted" style="font-size:.75rem;">Rp <?= number_format((int) $med['harga'], 0, ',', '.') ?>/item</span>
                                                        </label>
                                                        <div class="d-flex align-items-center gap-1 qty-ctrl">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="line-height:1.4;" onclick="changeResepQty(this,-1)" <?= !$isChecked ? 'disabled' : '' ?>>−</button>
                                                            <span class="qty-display fw-semibold text-center" style="min-width:22px;"><?= $savedQty ?></span>
                                                            <input type="hidden" name="qty_obat[<?= (int) $med['id'] ?>]" value="<?= $savedQty ?>" class="qty-val">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="line-height:1.4;" onclick="changeResepQty(this,1)" <?= !$isChecked ? 'disabled' : '' ?>>+</button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="btn btn-sm btn-success w-100 mt-2">Simpan Resep</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= queueStatusClass($queue['status']) ?>"><?= e($queue['status']) ?></span>
                                </td>
                                <td>
                                    <?php $isFinal = in_array($queue['status'], ['Selesai', 'Dibatalkan'], true); ?>
                                    <?php if ($isFinal): ?>
                                        <span class="badge <?= queueStatusClass($queue['status']) ?> me-1"><?= e($queue['status']) ?></span>
                                        <span class="text-muted small"><i class="fa-solid fa-lock me-1"></i>Terkunci</span>
                                    <?php else: ?>
                                        <form method="post" class="d-flex gap-2 mb-2">
                                            <input type="hidden" name="action" value="admin_update_queue_status">
                                            <input type="hidden" name="id" value="<?= (int) $queue['id'] ?>">
                                            <select name="status" class="form-select form-select-sm">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= e($status) ?>" <?= $queue['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-primary">Simpan</button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'pesanan', 'kode' => $queue['kode']])) ?>"
                                       class="btn btn-sm btn-outline-info w-100 mb-2">
                                        <i class="fa-solid fa-box-open me-1"></i>Lihat Pesanan
                                    </a>
                                    <form method="post" onsubmit="return confirm('Hapus antrian ini?')">
                                        <input type="hidden" name="action" value="admin_delete_queue">
                                        <input type="hidden" name="id" value="<?= (int) $queue['id'] ?>">
                                        <button class="btn btn-sm btn-danger w-100">Hapus</button>
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

<script>
function toggleResepQty(checkbox) {
    var row = checkbox.closest('.resep-row');
    var ctrl = row.querySelector('.qty-ctrl');
    var display = ctrl.querySelector('.qty-display');
    var input   = ctrl.querySelector('.qty-val');
    var btns    = ctrl.querySelectorAll('button');
    if (checkbox.checked) {
        btns.forEach(function(b) { b.disabled = false; });
        if (parseInt(input.value) === 0) { input.value = 1; display.textContent = 1; }
    } else {
        btns.forEach(function(b) { b.disabled = true; });
        input.value = 0; display.textContent = 0;
    }
}
function changeResepQty(btn, delta) {
    var ctrl    = btn.closest('.qty-ctrl');
    var display = ctrl.querySelector('.qty-display');
    var input   = ctrl.querySelector('.qty-val');
    var val     = Math.max(1, parseInt(input.value || 1) + delta);
    input.value = val; display.textContent = val;
}
</script>

<?php
adminSidebarClose();
renderFooter();
