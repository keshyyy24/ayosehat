<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireAdmin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'admin_add_schedule':
                $dokterId = (int) ($_POST['dokter_id'] ?? 0);
                $hari     = trim($_POST['hari'] ?? '');
                $shift    = trim($_POST['shift'] ?? '');
                if ($dokterId === 0 || $hari === '' || $shift === '') {
                    flash('Data jadwal tidak lengkap.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'jadwal']);
                }
                $pdo->prepare('INSERT INTO jadwal_dokter (dokter_id, hari, shift) VALUES (?, ?, ?)')
                    ->execute([$dokterId, $hari, $shift]);
                flash('Jadwal berhasil ditambahkan.');
                redirect('admin-dashboard', ['tab' => 'jadwal']);
                break;

            case 'admin_delete_schedule':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === 0) {
                    flash('Jadwal tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'jadwal']);
                }
                $pdo->prepare('DELETE FROM jadwal_dokter WHERE id = ?')->execute([$id]);
                flash('Jadwal berhasil dihapus.');
                redirect('admin-dashboard', ['tab' => 'jadwal']);
                break;
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('admin-dashboard', ['tab' => 'jadwal']);
    }
}

$doctors   = $pdo->query('SELECT id, nama, poli FROM dokter ORDER BY nama')->fetchAll();
$schedules = $pdo->query('
    SELECT jd.id, jd.hari, jd.shift, d.nama AS dokter_nama, d.poli
    FROM jadwal_dokter jd
    JOIN dokter d ON d.id = jd.dokter_id
    ORDER BY d.nama, jd.hari
')->fetchAll();

$hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

renderHeader('Jadwal Dokter');
adminSidebarOpen();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Jadwal Dokter</h3>
        <p class="text-muted mb-0">Atur hari dan shift dokter agar user bisa memilih jadwal yang tersedia.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
        <i class="fa-solid fa-plus me-1"></i>Tambah Jadwal
    </button>
</div>

<?php if (!$schedules): ?>
    <div class="card"><div class="card-body text-muted">Belum ada jadwal dokter.</div></div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Dokter</th><th>Poli</th><th>Hari</th><th>Shift</th><th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?= e($schedule['dokter_nama']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= e($schedule['poli']) ?></span></td>
                                <td><?= e($schedule['hari']) ?></td>
                                <td><?= e(formatTimeRangeAmPm($schedule['shift'])) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Hapus jadwal ini?')">
                                        <input type="hidden" name="action" value="admin_delete_schedule">
                                        <input type="hidden" name="id" value="<?= (int) $schedule['id'] ?>">
                                        <button class="btn btn-sm btn-danger">Hapus</button>
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

<!-- Modal Tambah Jadwal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="admin_add_schedule">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Dokter</label>
                        <select name="dokter_id" class="form-select" required>
                            <option value="">-- Pilih Dokter --</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= e($d['nama']) ?> (<?= e($d['poli']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hari</label>
                        <select name="hari" class="form-select" required>
                            <option value="">-- Pilih Hari --</option>
                            <?php foreach ($hariOptions as $hari): ?>
                                <option value="<?= e($hari) ?>"><?= e($hari) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift (misal: 08:00 - 12:00)</label>
                        <input type="text" name="shift" class="form-control" placeholder="08:00 - 12:00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php
adminSidebarClose();
renderFooter();
