<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

$stmt = $pdo->prepare('
    SELECT po.*,
           ra.nama       AS nama_pasien,
           ra.tanggal    AS tanggal_berobat,
           ra.dokter     AS dokter_berobat,
           ra.poli       AS poli_berobat,
           ra.rumah_sakit
    FROM pesanan_obat po
    LEFT JOIN riwayat_antrian ra ON ra.id = po.antrian_id
    WHERE po.username = ?
    ORDER BY po.id DESC
');
$stmt->execute([currentUsername()]);
$orders = $stmt->fetchAll();

renderHeader('Riwayat Obat');
renderNav();
?>
<div class="container page-shell">
    <h2 class="mb-4 text-center">Riwayat Pemesanan Obat</h2>
    <?php if (!$orders): ?>
        <p class="text-center text-muted">Belum ada pemesanan obat.</p>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($orders as $index => $order): $items = json_decode($order['obat_json'], true) ?: []; ?>
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">Pemesanan ke-<?= $index + 1 ?></h5>
                                <?php if ($order['status'] === 'diambil'): ?>
                                    <span class="badge bg-success">Sudah Diambil</span>
                                <?php elseif ($order['status'] === 'dibatalkan'): ?>
                                    <span class="badge bg-danger">Dibatalkan</span>
                                <?php elseif ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                                    <span class="badge bg-info text-dark">Siap Diambil</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Menunggu Jadwal</span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text mb-1"><strong>Waktu:</strong> <?= e($order['waktu']) ?></p>
                            <?php if ($order['kode_antrian']): ?>
                                <p class="card-text"><strong>Kode Antrian:</strong> <?= e($order['kode_antrian']) ?></p>
                            <?php endif; ?>
                            <strong>Daftar Obat:</strong>
                            <ul><?php foreach ($items as $item):
                                $nama = is_array($item) ? $item['nama'] : $item;
                                $qty  = is_array($item) ? ($item['qty'] ?? 1) : 1;
                            ?><li><?= e($nama) ?> &times;<?= $qty ?></li><?php endforeach; ?></ul>
                            <?php if ($order['status'] === 'diambil'): ?>
                                <div class="alert alert-success mb-2">
                                    Obat sudah diambil.
                                </div>
                            <?php elseif ($order['status'] === 'dibatalkan'): ?>
                                <div class="alert alert-danger mb-2">
                                    Pesanan ini dibatalkan karena antrian terkait telah dibatalkan.
                                </div>
                            <?php elseif ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                                <div class="alert alert-info mb-2">
                                    <strong>Jadwal pengambilan:</strong><br>
                                    <?= e($order['tanggal_pengambilan']) ?>, jam <?= e(formatTimeAmPm($order['jam_pengambilan'])) ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-2">
                                    Jadwal pengambilan belum ditentukan admin.
                                </div>
                            <?php endif; ?>
                            <a href="<?= e(routeUrl('invoice', ['id' => (int) $order['id']])) ?>" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fa-solid fa-file-invoice me-1"></i> Lihat Invoice
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
renderFooter();
