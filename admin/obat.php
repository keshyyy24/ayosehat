<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireAdmin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'admin_add_medicine':
                $nama  = trim($_POST['nama'] ?? '');
                $harga = (int) ($_POST['harga'] ?? 0);
                $stok  = (int) ($_POST['stok'] ?? 0);
                if ($nama === '' || $harga <= 0) {
                    flash('Nama dan harga obat tidak boleh kosong.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'obat']);
                }
                $pdo->prepare('INSERT INTO obat (nama, harga, stok) VALUES (?, ?, ?)')->execute([$nama, $harga, $stok]);
                flash('Obat berhasil ditambahkan.');
                redirect('admin-dashboard', ['tab' => 'obat']);
                break;

            case 'admin_update_medicine':
                $id    = (int) ($_POST['id'] ?? 0);
                $nama  = trim($_POST['nama'] ?? '');
                $harga = (int) ($_POST['harga'] ?? 0);
                $stok  = (int) ($_POST['stok'] ?? 0);
                if ($id === 0 || $nama === '' || $harga <= 0) {
                    flash('Data obat tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'obat']);
                }
                $pdo->prepare('UPDATE obat SET nama = ?, harga = ?, stok = ? WHERE id = ?')->execute([$nama, $harga, $stok, $id]);
                flash('Data obat berhasil diperbarui.');
                redirect('admin-dashboard', ['tab' => 'obat']);
                break;

            case 'admin_delete_medicine':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === 0) {
                    flash('Obat tidak valid.', 'danger');
                    redirect('admin-dashboard', ['tab' => 'obat']);
                }
                $pdo->prepare('DELETE FROM obat WHERE id = ?')->execute([$id]);
                flash('Obat berhasil dihapus.');
                redirect('admin-dashboard', ['tab' => 'obat']);
                break;
        }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('admin-dashboard', ['tab' => 'obat']);
    }
}

$medicines = $pdo->query('SELECT * FROM obat ORDER BY nama')->fetchAll();

renderHeader('Daftar Obat');
adminSidebarOpen();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Daftar Obat</h3>
        <p class="text-muted mb-0">Atur daftar obat yang bisa diresepkan admin untuk user.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
        <i class="fa-solid fa-plus me-1"></i>Tambah Obat
    </button>
</div>

<?php if (!$medicines): ?>
    <div class="card"><div class="card-body text-muted">Belum ada data obat.</div></div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nama Obat</th><th>Harga/item</th><th>Stok</th><th style="width:200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                            <tr>
                                <td><?= (int) $med['id'] ?></td>
                                <td><strong><?= e($med['nama']) ?></strong></td>
                                <td>Rp <?= number_format((int) $med['harga'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ((int) $med['stok'] <= 0): ?>
                                        <span class="badge bg-danger">Habis</span>
                                    <?php elseif ((int) $med['stok'] <= 10): ?>
                                        <span class="badge bg-warning text-dark"><?= (int) $med['stok'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= (int) $med['stok'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editMedicineModal"
                                        data-id="<?= (int) $med['id'] ?>"
                                        data-nama="<?= e($med['nama']) ?>"
                                        data-harga="<?= (int) $med['harga'] ?>"
                                        data-stok="<?= (int) $med['stok'] ?>">
                                        Edit
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus obat ini?')">
                                        <input type="hidden" name="action" value="admin_delete_medicine">
                                        <input type="hidden" name="id" value="<?= (int) $med['id'] ?>">
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

<!-- Modal Tambah Obat -->
<div class="modal fade" id="addMedicineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="admin_add_medicine">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Obat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Obat</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama obat..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga per item (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="1" placeholder="5000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control" min="0" value="0">
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

<!-- Modal Edit Obat -->
<div class="modal fade" id="editMedicineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="admin_update_medicine">
                <input type="hidden" name="id" id="editMedId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Obat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Obat</label>
                        <input type="text" name="nama" id="editMedNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga per item (Rp)</label>
                        <input type="number" name="harga" id="editMedHarga" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" id="editMedStok" class="form-control" min="0">
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
document.getElementById('editMedicineModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('editMedId').value    = btn.dataset.id;
    document.getElementById('editMedNama').value  = btn.dataset.nama;
    document.getElementById('editMedHarga').value = btn.dataset.harga;
    document.getElementById('editMedStok').value  = btn.dataset.stok;
});
</script>

<?php
adminSidebarClose();
renderFooter();
