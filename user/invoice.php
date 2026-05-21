<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);

if ($id === 0) {
    flash('Invoice tidak valid.', 'danger');
    redirect('obat');
}

$stmt = $pdo->prepare('
    SELECT po.*,
           ra.nama            AS nama_pasien,
           ra.tanggal_lahir,
           ra.jenis_kelamin,
           ra.no_hp,
           ra.rumah_sakit,
           ra.tanggal         AS tanggal_berobat,
           ra.dokter          AS dokter_berobat,
           ra.poli            AS poli_berobat,
           ra.metode,
           ra.nomor_kartu
    FROM pesanan_obat po
    LEFT JOIN riwayat_antrian ra ON ra.id = po.antrian_id
    WHERE po.id = ? AND po.username = ?
');
$stmt->execute([$id, currentUsername()]);
$order = $stmt->fetch();

if (!$order) {
    flash('Invoice tidak ditemukan.', 'danger');
    redirect('obat');
}

$items = json_decode($order['obat_json'], true) ?: [];
$invoiceNumber = 'INV-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);

$grandTotal = 0;
foreach ($items as $it) {
    $grandTotal += (is_array($it) ? (int)($it['qty'] ?? 1) : 1) * (is_array($it) ? (int)($it['harga'] ?? 0) : 0);
}

renderHeader('Invoice Pengambilan Obat');
renderNav();
?>
<style>
    /* ── Screen wrapper ── */
    .inv-wrap { max-width: 760px; margin: 0 auto; padding: 28px 16px 60px; }

    /* ── Paper ── */
    .inv-paper {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 28px rgba(0,0,0,.13);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Header band ── */
    .inv-hdr {
        background: linear-gradient(120deg, #1648c8 0%, #0891b2 100%);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color: #fff;
        padding: 22px 28px 18px;
    }
    .inv-hdr-inner {
        display: table;
        width: 100%;
        border-collapse: collapse;
    }
    .inv-hdr-left  { display: table-cell; vertical-align: middle; width: 55%; }
    .inv-hdr-right { display: table-cell; vertical-align: middle; text-align: right; }
    .inv-logo-box {
        display: inline-block;
        background: #fff;
        border-radius: 7px;
        padding: 5px 7px;
        vertical-align: middle;
        margin-right: 10px;
    }
    .inv-logo-box img { height: 34px; width: auto; display: block; }
    .inv-brand { display: inline-block; vertical-align: middle; }
    .inv-brand-name { font-size: 1.15rem; font-weight: 700; line-height: 1.2; }
    .inv-brand-sub  { font-size: .72rem; opacity: .8; }
    .inv-hdr-title  { font-size: .72rem; opacity: .8; letter-spacing: .03em; }
    .inv-hdr-no     { font-size: 1.1rem; font-weight: 700; letter-spacing: .02em; }
    .inv-hdr-date   { font-size: .72rem; opacity: .82; margin-top: 1px; }
    .inv-pill {
        display: inline-block;
        margin-top: 6px;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 600;
        border: 1px solid rgba(255,255,255,.4);
        background: rgba(255,255,255,.2);
        color: #fff;
    }

    /* ── Body ── */
    .inv-body { padding: 22px 28px 18px; }

    /* ── Section label ── */
    .inv-label {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #6b7280;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }

    /* ── Two-column info block (table-based so print works) ── */
    .inv-cols { display: table; width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .inv-col  { display: table-cell; vertical-align: top; width: 50%; }
    .inv-col:first-child { padding-right: 20px; }
    .inv-col:last-child  { padding-left: 20px; border-left: 1px solid #e5e7eb; }

    /* ── KV rows ── */
    .inv-kv { width: 100%; border-collapse: collapse; }
    .inv-kv td { padding: 3px 0; font-size: .82rem; vertical-align: top; color: #111827; }
    .inv-kv td.k { color: #6b7280; width: 110px; padding-right: 6px; white-space: nowrap; }

    /* ── Divider ── */
    .inv-divider { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0; }

    /* ── Medicine table ── */
    .inv-tbl { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .inv-tbl thead th {
        background: #1648c8;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color: #fff !important;
        font-weight: 600;
        padding: 8px 10px;
        text-align: left;
        border: none;
    }
    .inv-tbl tbody tr:nth-child(odd)  td { background: #f8faff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-tbl tbody tr:nth-child(even) td { background: #fff; }
    .inv-tbl tbody td {
        padding: 7px 10px;
        color: #1e293b !important;
        border-bottom: 1px solid #e8edf5;
        font-size: .82rem;
    }
    .inv-tbl tfoot td {
        padding: 8px 10px;
        font-weight: 700;
        color: #1e293b !important;
        background: #eef2ff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        border-top: 2px solid #1648c8;
        font-size: .85rem;
    }

    /* ── Pickup box ── */
    .inv-pickup {
        border-radius: 7px;
        padding: 11px 16px;
        font-size: .83rem;
        margin-top: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .inv-pickup.diambil { background: #f0fdf4; border: 1px solid #86efac; color: #166534 !important; }
    .inv-pickup.siap    { background: #eff6ff; border: 1px solid #93c5fd; color: #1e40af !important; }
    .inv-pickup.tunggu  { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e !important; }
    .inv-pickup strong  { color: inherit !important; }

    /* ── Footer ── */
    .inv-foot {
        background: #f1f5f9;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        border-top: 1px solid #e2e8f0;
        padding: 9px 28px;
        font-size: .7rem;
        color: #94a3b8;
        display: table;
        width: 100%;
        box-sizing: border-box;
    }
    .inv-foot-l { display: table-cell; vertical-align: middle; }
    .inv-foot-r { display: table-cell; vertical-align: middle; text-align: right; }

    /* ── Print overrides ── */
    @media print {
        nav, .inv-no-print, footer { display: none !important; }
        body, html { background: #fff !important; margin: 0; padding: 0; }
        .page-shell { padding-top: 0 !important; min-height: 0 !important; }
        .container { padding: 0 !important; max-width: 100% !important; }
        .inv-wrap { padding: 0 !important; max-width: 100% !important; }
        .inv-paper { box-shadow: none !important; border-radius: 0 !important; page-break-inside: avoid; }
        .inv-body { padding: 16px 22px 12px !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="container page-shell">
    <div class="inv-wrap">

        <!-- Action bar — hidden on print -->
        <div class="d-flex justify-content-between align-items-center mb-3 inv-no-print">
            <a href="<?= e(routeUrl('obat')) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak / Unduh PDF
            </button>
        </div>

        <!-- Paper -->
        <div class="inv-paper">

            <!-- Header -->
            <div class="inv-hdr">
                <div class="inv-hdr-inner">
                    <div class="inv-hdr-left">
                        <div class="inv-logo-box">
                            <img src="<?= APP_BASE ?>/public/assets/ayosehat.png" alt="Ayosehat">
                        </div>
                        <div class="inv-brand">
                            <div class="inv-brand-name">Ayosehat</div>
                            <div class="inv-brand-sub">PT Sehat Selalu Indonesia</div>
                        </div>
                    </div>
                    <div class="inv-hdr-right">
                        <div class="inv-hdr-title">Invoice Pengambilan Obat</div>
                        <div class="inv-hdr-no"><?= e($invoiceNumber) ?></div>
                        <div class="inv-hdr-date">Dipesan: <?= e($order['waktu']) ?></div>
                        <?php if ($order['status'] === 'diambil'): ?>
                            <div><span class="inv-pill">&#10003; Sudah Diambil</span></div>
                        <?php elseif ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                            <div><span class="inv-pill">&#128197; Siap Diambil</span></div>
                        <?php else: ?>
                            <div><span class="inv-pill">&#9679; Menunggu Jadwal</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="inv-body">

                <!-- Data Pasien | Data Kunjungan (table-based 2-col) -->
                <div class="inv-cols">
                    <div class="inv-col">
                        <div class="inv-label">Data Pasien</div>
                        <table class="inv-kv">
                            <tr><td class="k">Nama</td><td><?= e($order['nama_pasien'] ?: '-') ?></td></tr>
                            <tr><td class="k">Tanggal Lahir</td><td><?= e($order['tanggal_lahir'] ?: '-') ?></td></tr>
                            <tr><td class="k">Jenis Kelamin</td><td><?= e($order['jenis_kelamin'] ?: '-') ?></td></tr>
                            <tr><td class="k">No. HP</td><td><?= e($order['no_hp'] ?: '-') ?></td></tr>
                            <tr><td class="k">Metode Bayar</td><td><?= e($order['metode'] ?: '-') ?></td></tr>
                            <?php if ($order['nomor_kartu']): ?>
                                <tr><td class="k">Nomor Kartu</td><td><?= e($order['nomor_kartu']) ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="inv-col">
                        <div class="inv-label">Data Kunjungan</div>
                        <table class="inv-kv">
                            <tr><td class="k">Rumah Sakit</td><td><?= e($order['rumah_sakit'] ?: '-') ?></td></tr>
                            <tr><td class="k">Tanggal</td><td><?= e($order['tanggal_berobat'] ?: '-') ?></td></tr>
                            <tr><td class="k">Dokter</td><td><?= e($order['dokter_berobat'] ?: '-') ?></td></tr>
                            <tr><td class="k">Poli</td><td><?= e($order['poli_berobat'] ?: '-') ?></td></tr>
                            <tr><td class="k">Kode Antrian</td><td><?= e($order['kode_antrian'] ?: '-') ?></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Daftar Obat -->
                <div class="inv-label">Daftar Obat</div>
                <table class="inv-tbl" style="margin-bottom:14px;">
                    <thead>
                        <tr>
                            <th style="width:36px;">No.</th>
                            <th>Nama Obat</th>
                            <th style="width:52px;text-align:center;">Qty</th>
                            <th style="width:110px;text-align:right;">Harga/item</th>
                            <th style="width:110px;text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item):
                            $nama     = is_array($item) ? $item['nama']  : $item;
                            $qty      = is_array($item) ? (int)($item['qty']   ?? 1) : 1;
                            $harga    = is_array($item) ? (int)($item['harga'] ?? 0) : 0;
                            $subtotal = $qty * $harga;
                        ?>
                            <tr>
                                <td style="text-align:center;"><?= $i + 1 ?></td>
                                <td><?= e($nama) ?></td>
                                <td style="text-align:center;"><?= $qty ?>x</td>
                                <td style="text-align:right;">Rp <?= number_format($harga, 0, ',', '.') ?></td>
                                <td style="text-align:right;">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;">Total &nbsp;(<?= count($items) ?> item)</td>
                            <td style="text-align:right;">Rp <?= number_format($grandTotal, 0, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Jadwal Pengambilan -->
                <div class="inv-label">Jadwal Pengambilan</div>
                <?php if ($order['status'] === 'diambil'): ?>
                    <div class="inv-pickup diambil">
                        Obat sudah diambil pada <strong><?= e($order['tanggal_pengambilan']) ?></strong>, jam <strong><?= e(formatTimeAmPm($order['jam_pengambilan'])) ?></strong>.
                    </div>
                <?php elseif ($order['tanggal_pengambilan'] && $order['jam_pengambilan']): ?>
                    <div class="inv-pickup siap">
                        Silakan ambil pada <strong><?= e($order['tanggal_pengambilan']) ?></strong>, jam <strong><?= e(formatTimeAmPm($order['jam_pengambilan'])) ?></strong>.
                    </div>
                <?php else: ?>
                    <div class="inv-pickup tunggu">
                        Jadwal pengambilan belum ditentukan. Pantau halaman riwayat obat.
                    </div>
                <?php endif; ?>

            </div><!-- /inv-body -->

            <!-- Footer -->
            <div class="inv-foot" style="margin-top:14px;">
                <div class="inv-foot-l">Dicetak: <?= date('d M Y, H:i') ?></div>
                <div class="inv-foot-r">Ayosehat &mdash; PT Sehat Selalu Indonesia</div>
            </div>

        </div><!-- /inv-paper -->

    </div>
</div>

<?php
renderFooter();
