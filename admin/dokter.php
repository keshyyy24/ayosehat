<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireAdmin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'admin_add_doctor':
                $nama = trim($_POST['nama'] ?? '');
                $poli = trim($_POST['poli'] ?? '');
                if ($nama === '' || $poli === '') {
                    flash('Nama dan poli tidak boleh kosong.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'dokter']);
                }
                $pdo->prepare('INSERT INTO dokter (nama, poli) VALUES (?, ?)')->execute([$nama, $poli]);
                flash('Dokter berhasil ditambahkan.');
                redirect('admin-dashboard', ['tab' => 'dokter']);
                break;

            case 'admin_update_doctor':
                $id   = (int) ($_POST['id'] ?? 0);
                $nama = trim($_POST['nama'] ?? '');
                $poli = trim($_POST['poli'] ?? '');
                if ($id === 0 || $nama === '' || $poli === '') {
                    flash('Data dokter tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'dokter']);
                }
                $pdo->prepare('UPDATE dokter SET nama = ?, poli = ? WHERE id = ?')->execute([$nama, $poli, $id]);
                flash('Data dokter berhasil diperbarui.');
                redirect('admin-dashboard', ['tab' => 'dokter']);
                break;

            case 'admin_delete_doctor':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === 0) {
                    flash('Dokter tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'dokter']);
                }
                $pdo->prepare('DELETE FROM dokter WHERE id = ?')->execute([$id]);
                flash('Dokter berhasil dihapus.');
                redirect('admin-dashboard', ['tab' => 'dokter']);
                break;
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('admin-dashboard', ['tab' => 'dokter']);
    }
}

$doctors = $pdo->query('SELECT * FROM dokter ORDER BY nama')->fetchAll();

renderHeader('Data Dokter');
adminSidebarOpen();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Data Dokter</h3>
        <p class="text-muted mb-0">Tambah, ubah, atau hapus data dokter dan poli.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
        <i class="fa-solid fa-plus me-1"></i>Tambah Dokter
    </button>
</div>

<?php if (!$doctors): ?>
    <div class="card"><div class="card-body text-muted">Belum ada data dokter.</div></div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nama Dokter</th><th>Poli</th><th style="width:200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td><?= (int) $doctor['id'] ?></td>
                                <td><strong><?= e($doctor['nama']) ?></strong></td>
                                <td><span class="badge bg-info text-dark"><?= e($doctor['poli']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDoctorModal"
                                        data-id="<?= (int) $doctor['id'] ?>"
                                        data-nama="<?= e($doctor['nama']) ?>"
                                        data-poli="<?= e($doctor['poli']) ?>">
                                        Edit
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus dokter ini? Jadwal terkait juga akan terhapus.')">
                                        <input type="hidden" name="action" value="admin_delete_doctor">
                                        <input type="hidden" name="id" value="<?= (int) $doctor['id'] ?>">
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

<!-- Modal Tambah Dokter -->
<div class="modal fade" id="addDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="admin_add_doctor">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Dokter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Dokter</label>
                        <input type="text" name="nama" class="form-control" placeholder="dr. Nama Lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poli</label>
                        <input type="text" name="poli" class="form-control" placeholder="Poli Umum / Poli Gigi / ..." required>
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

<!-- Modal Edit Dokter -->
<div class="modal fade" id="editDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="admin_update_doctor">
                <input type="hidden" name="id" id="editDoctorId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Dokter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Dokter</label>
                        <input type="text" name="nama" id="editDoctorNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poli</label>
                        <input type="text" name="poli" id="editDoctorPoli" class="form-control" required>
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
<script>
document.getElementById('editDoctorModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('editDoctorId').value   = btn.dataset.id;
    document.getElementById('editDoctorNama').value = btn.dataset.nama;
    document.getElementById('editDoctorPoli').value = btn.dataset.poli;
});
</script>

<?php
adminSidebarClose();
renderFooter();
