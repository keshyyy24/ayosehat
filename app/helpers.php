<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function routeUrl(string $page, array $params = []): string
{
    $base = defined('APP_BASE') ? APP_BASE : '';

    // Admin tabs → separate files
    if ($page === 'admin-dashboard') {
        $tab = $params['tab'] ?? 'overview';
        unset($params['tab']);
        $tabMap = [
            'overview' => $base . '/admin/index.php',
            'home'     => $base . '/admin/index.php',
            'antrian'  => $base . '/admin/antrian.php',
            'pesanan'  => $base . '/admin/pesanan.php',
            'jadwal'   => $base . '/admin/jadwal.php',
            'dokter'   => $base . '/admin/dokter.php',
            'obat'     => $base . '/admin/obat.php',
        ];
        $url = $tabMap[$tab] ?? ($base . '/admin/index.php');
        if ($params) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        }
        return $url;
    }

    $map = [
        'login'      => $base . '/index.php',
        'register'   => $base . '/index.php?page=register',
        'logout'     => $base . '/logout.php',
        'home'       => $base . '/user/home.php',
        'hospital'   => $base . '/user/hospital.php',
        'pasien'     => $base . '/user/pasien.php',
        'pembayaran' => $base . '/user/pembayaran.php',
        'tanggal'    => $base . '/user/tanggal.php',
        'dokter'     => $base . '/user/dokter.php',
        'konfirmasi' => $base . '/user/konfirmasi.php',
        'riwayat'    => $base . '/user/riwayat.php',
        'jadwal'     => $base . '/user/jadwal.php',
        'pesanan'    => $base . '/user/pesanan.php',
        'obat'       => $base . '/user/obat.php',
        'invoice'    => $base . '/user/invoice.php',
        'about'      => $base . '/index.php?page=about',
        'contact'    => $base . '/index.php?page=contact',
        'panduan'    => $base . '/index.php?page=panduan',
        'reward'     => $base . '/index.php?page=reward',
        'fasilitas'  => $base . '/index.php?page=fasilitas',
    ];
    $url = $map[$page] ?? ($base . '/index.php');
    if ($params) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    }
    return $url;
}

function redirect(string $page, array $params = []): never
{
    header('Location: ' . routeUrl($page, $params));
    exit;
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function currentUsername(): string
{
    return $_SESSION['user']['username'] ?? 'guest';
}

function currentRole(): string
{
    return $_SESSION['user']['role'] ?? 'guest';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('Silakan login terlebih dahulu.', 'warning');
        redirect('login');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (currentRole() !== 'admin') {
        flash('Halaman admin hanya bisa diakses oleh admin.', 'danger');
        redirect('home');
    }
}

function indonesianDayName(string $date): string
{
    $days = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    return $days[(int) date('N', $timestamp)] ?? '';
}

function verifyPassword(string $input, string $stored): bool
{
    if (password_get_info($stored)['algo'] !== 0) {
        return password_verify($input, $stored);
    }

    return hash_equals($stored, $input);
}

function formatTimeAmPm(?string $time): string
{
    $time = trim((string) $time);
    if ($time === '') {
        return '-';
    }

    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return $time;
    }

    return date('h:i A', $timestamp);
}

function formatTimeRangeAmPm(?string $range): string
{
    $range = trim((string) $range);
    if ($range === '') {
        return '-';
    }

    $parts = preg_split('/\s*-\s*/', $range);
    if (!$parts || count($parts) !== 2) {
        return formatTimeAmPm($range);
    }

    return formatTimeAmPm($parts[0]) . ' - ' . formatTimeAmPm($parts[1]);
}

function queueStatusClass(string $status): string
{
    return match ($status) {
        'Selesai'    => 'bg-success',
        'Dipanggil'  => 'bg-info text-dark',
        'Dibatalkan' => 'bg-danger',
        default      => 'bg-warning text-dark',
    };
}
