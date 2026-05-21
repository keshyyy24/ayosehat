<?php

function renderHeader(string $title): void
{
    $flash = flash();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> - Ayosehat</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
        <link rel="stylesheet" href="<?= APP_BASE ?>/public/css/gaya.css">
        <style>
            .page-shell { padding-top: 104px !important; min-height: 100vh; }
            .auth-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
            .content-card { background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
            .admin-sidebar { width: 280px; background: #0f2742; color: #fff; min-height: 100vh; position: sticky; top: 0; }
            .fasilitas-card.active .fasilitas-detail { max-height: 500px; padding-top: 10px; padding-bottom: 10px; }
        </style>
    </head>
    <body>
    <?php if ($flash): ?>
        <div class="position-fixed top-0 start-50 translate-middle-x alert alert-<?= e($flash['type']) ?> shadow mt-3" style="z-index: 2000;">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
    <?php
}

function renderNav(): void
{
    $homeUrl = isLoggedIn() ? routeUrl('home') : routeUrl('login');
    ?>
    <nav class="navbar">
        <a href="<?= e($homeUrl) ?>" class="navbar-logo d-flex align-items-center gap-2">
            <img src="<?= APP_BASE ?>/public/assets/ayosehat.png" alt="Logo Ayosehat" style="height: 35px;">
            <span>Ayo<span style="font-weight: bold;">Sehat!</span></span>
        </a>
        <div class="navbar-nav">
            <a href="<?= e($homeUrl) ?>">Home</a>
            <a href="<?= e(routeUrl('about')) ?>">About us</a>
            <a href="<?= e(routeUrl('contact')) ?>">Kontak</a>
            <?php if (isLoggedIn()): ?>
                <?php if (currentRole() === 'user'): ?>
                    <button data-profile-open class="btn btn-link navbar-profile-btn p-0 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-circle-user fa-lg"></i>
                        <span><?= e(currentUsername()) ?></span>
                    </button>
                <?php endif; ?>
                <a href="<?= e(routeUrl('logout')) ?>" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Keluar</a>
                <form id="logout-form" method="post" action="<?= APP_BASE ?>/logout.php" class="d-none"></form>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

function renderFooter(): void
{
    ?>
    <script src="<?= APP_BASE ?>/public/app.js"></script>
    </body>
    </html>
    <?php
}

function stepProgress(int $active): void
{
    $steps = ['Pasien', 'Pembayaran', 'Tanggal', 'Dokter', 'Konfirmasi'];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <?php foreach ($steps as $index => $label): $number = $index + 1; ?>
            <div class="step text-center <?= $number < $active ? 'completed' : '' ?> <?= $number === $active ? 'active' : '' ?>">
                <div class="circle"><?= $number ?></div>
                <small><?= e($label) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function adminSidebarOpen(): void
{
    ?>
    <div class="admin-layout d-flex">
        <aside class="admin-sidebar d-flex flex-column p-3">
            <h4 class="text-white">Admin Ayosehat</h4>
            <ul class="nav flex-column mt-4 mb-auto">
                <li class="nav-item"><a href="<?= e(routeUrl('admin-dashboard')) ?>" class="nav-link text-white">Dashboard</a></li>
                <li class="nav-item"><a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'antrian'])) ?>" class="nav-link text-white">Kelola Antrian</a></li>
                <li class="nav-item"><a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'pesanan'])) ?>" class="nav-link text-white">Pengambilan Obat</a></li>
                <li class="nav-item"><a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'jadwal'])) ?>" class="nav-link text-white">Jadwal Dokter</a></li>
                <li class="nav-item"><a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'dokter'])) ?>" class="nav-link text-white">Data Dokter</a></li>
                <li class="nav-item"><a href="<?= e(routeUrl('admin-dashboard', ['tab' => 'obat'])) ?>" class="nav-link text-white">Daftar Obat</a></li>
            </ul>
            <div class="d-grid gap-2">
                <form method="post" action="<?= APP_BASE ?>/logout.php">
                    <button class="btn btn-danger w-100">Logout</button>
                </form>
            </div>
        </aside>
        <main class="flex-grow-1 p-4 bg-light">
    <?php
}

function adminSidebarClose(): void
{
    ?>
        </main>
    </div>
    <?php
}

function summaryCard(string $title, array $items): void
{
    ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body p-3">
            <h6 class="fw-bold mb-3"><?= e($title) ?></h6>
            <?php foreach ($items as $label => $value): ?>
                <div class="row mb-2"><div class="col-5"><?= e($label) ?></div><div class="col-7 text-end text-muted"><?= e($value) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
