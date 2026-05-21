<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$page = $_GET['page'] ?? 'login';

// Handle POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'login') { login($pdo); }
        elseif ($action === 'register') { register($pdo); }
    } catch (Throwable $e) {
        flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        redirect('login');
    }
}

// Redirect already-logged-in users away from login/register
if (in_array($page, ['login', 'register'], true) && isLoggedIn()) {
    redirect(currentRole() === 'admin' ? 'admin-dashboard' : 'home');
}

$titles = [
    'login'     => 'Login',
    'register'  => 'Register',
    'about'     => 'Tentang',
    'contact'   => 'Kontak',
    'panduan'   => 'Panduan',
    'reward'    => 'Reward',
    'fasilitas' => 'Fasilitas',
];

renderHeader($titles[$page] ?? 'Ayosehat');

match ($page) {
    'register'  => pageRegister(),
    'about'     => pageAbout(),
    'contact'   => pageContact(),
    'panduan'   => pagePanduan(),
    'reward'    => pageReward(),
    'fasilitas' => pageFasilitas(),
    default     => pageLogin(),
};

renderFooter();

// ── Action handlers ──────────────────────────────────────────────────────────

function login(PDO $pdo): void
{
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && verifyPassword($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['username' => $admin['username'], 'role' => 'admin'];
        redirect('admin-dashboard');
    }

    $stmt = $pdo->prepare('SELECT * FROM pasien WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !verifyPassword($password, $user['password'])) {
        flash('Username atau password salah.', 'danger');
        redirect('login');
    }

    session_regenerate_id(true);
    $_SESSION['user'] = ['username' => $username, 'role' => 'user'];
    $_SESSION['registration'] = [];
    redirect('home');
}

function register(PDO $pdo): void
{
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash('Username dan password wajib diisi.', 'danger');
        redirect('register');
    }

    $stmt = $pdo->prepare('SELECT id FROM pasien WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        flash('Username sudah terdaftar.', 'danger');
        redirect('register');
    }

    $stmt = $pdo->prepare('INSERT INTO pasien (username, password) VALUES (?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    flash('Registrasi berhasil. Silakan login.');
    redirect('login');
}

// ── Page renderers ────────────────────────────────────────────────────────────

function pageLogin(): void
{
    ?>
    <div class="auth-shell auth-entry-shell">
        <div class="auth-entry">
            <section class="auth-brand-panel">
                <div class="auth-brand-mark">
                    <img src="<?= APP_BASE ?>/public/assets/ayosehat.png" alt="Logo Ayosehat">
                    <span>Ayo<strong>Sehat!</strong></span>
                </div>
                <h1>Layanan pendaftaran berobat dalam satu alur.</h1>
                <p>Masuk untuk membuat pendaftaran, memantau antrian, memesan resep, dan melihat jadwal pengambilan obat.</p>
                <div class="auth-feature-grid">
                    <div><i class="fa-solid fa-hospital-user"></i><span>Pendaftaran pasien</span></div>
                    <div><i class="fa-solid fa-list-check"></i><span>Status antrian</span></div>
                    <div><i class="fa-solid fa-prescription-bottle-medical"></i><span>Resep admin</span></div>
                    <div><i class="fa-solid fa-box-open"></i><span>Pengambilan obat</span></div>
                </div>
            </section>

            <section class="auth-card card p-4 shadow">
                <div class="mb-4">
                    <p class="auth-kicker mb-2">Selamat datang</p>
                    <h2 class="mb-1">Login Ayosehat</h2>
                    <p class="text-muted mb-0">Gunakan akun pasien atau admin untuk melanjutkan.</p>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <label class="form-label" for="login-username">Username</label>
                    <input id="login-username" class="form-control mb-3" type="text" name="username" placeholder="Masukkan username" autocomplete="username" required>
                    <label class="form-label" for="login-password">Password</label>
                    <input id="login-password" class="form-control mb-3" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button class="btn btn-primary w-100">Masuk</button>
                </form>
                <p class="text-center mt-3 mb-0">Belum punya akun? <a href="<?= e(routeUrl('register')) ?>">Daftar di sini</a></p>
            </section>
        </div>
    </div>
    <?php
}

function pageRegister(): void
{
    ?>
    <div class="auth-shell auth-entry-shell">
        <div class="auth-entry auth-entry-register">
            <section class="auth-brand-panel">
                <div class="auth-brand-mark">
                    <img src="<?= APP_BASE ?>/public/assets/ayosehat.png" alt="Logo Ayosehat">
                    <span>Ayo<strong>Sehat!</strong></span>
                </div>
                <h1>Buat akun pasien untuk mulai memakai layanan.</h1>
                <p>Setelah registrasi, kamu bisa memilih rumah sakit, mengisi data kunjungan, dan memantau proses layanan.</p>
                <div class="auth-step-list">
                    <div><strong>1</strong><span>Daftar akun</span></div>
                    <div><strong>2</strong><span>Lengkapi pendaftaran</span></div>
                    <div><strong>3</strong><span>Pantau layanan</span></div>
                </div>
            </section>

            <section class="auth-card card p-4 shadow">
                <div class="mb-4">
                    <p class="auth-kicker mb-2">Akun pasien</p>
                    <h2 class="mb-1">Register</h2>
                    <p class="text-muted mb-0">Buat username dan password untuk masuk ke dashboard.</p>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="register">
                    <label class="form-label" for="register-username">Username</label>
                    <input id="register-username" class="form-control mb-3" type="text" name="username" placeholder="Buat username" autocomplete="username" required>
                    <label class="form-label" for="register-password">Password</label>
                    <input id="register-password" class="form-control mb-3" type="password" name="password" placeholder="Buat password" autocomplete="new-password" required>
                    <button class="btn btn-success w-100">Daftar</button>
                </form>
                <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="<?= e(routeUrl('login')) ?>">Login di sini</a></p>
            </section>
        </div>
    </div>
    <?php
}

function pageAbout(): void
{
    renderNav();
    ?>
    <section class="about-section">
        <div class="about-container">
            <div class="about-text">
                <h2>Ayosehat (PT SEHAT SELALU INDONESIA)</h2>
                <p>Ayosehat mulai beroperasi sejak 20 Mei 2025, dengan tonggak awal berupa bergabungnya RSIA Sumber Sehat yang kini dikenal sebagai RS Sumber Sehat Jakarta sebagai rumah sakit pertama yang menjadi bagian dari sistem ini.</p>
                <p>Ayosehat hadir dengan visi untuk meningkatkan taraf kesehatan masyarakat Indonesia, khususnya pada kelompok masyarakat menengah. Kami terus berupaya menghadirkan peningkatan mutu layanan, infrastruktur, dan fasilitas penunjang demi memberikan pengalaman terbaik bagi pasien dan keluarganya.</p>
                <p>Kami memiliki komitmen kuat untuk terus memperluas jaringan Ayosehat demi menjangkau lebih banyak masyarakat melalui penambahan rumah sakit di berbagai wilayah di Indonesia.</p>
            </div>
            <div class="about-image"><img src="<?= APP_BASE ?>/public/assets/ayosehat.png" alt="Ayosehat Logo"></div>
        </div>
    </section>
    <?php
}

function pageContact(): void
{
    renderNav();
    ?>
    <main class="contact-section">
        <div class="contact-map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126917.32201097774!2d106.68942934565148!3d-6.229728048222735!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e6f6b0515d%3A0x5c933dff1be29815!2sJakarta!5e0!3m2!1sid!2sid!4v1717949234560!5m2!1sid!2sid" allowfullscreen loading="lazy" title="Peta lokasi Jakarta"></iframe>
        </div>
        <div class="contact-details">
            <h1 class="contact-title">PT SEHAT SELALU INDONESIA</h1>
            <p>Jl. Fiktif Raya No.123, RT 004/RW 005,<br>Kel. Fiktif Jaya, Kec. Fiktif Makmur, Kota Jakarta Pusat</p>
            <h2 class="contact-subtitle">Kontak</h2>
            <p>Telepon: 021-12345678<br>Fax: 021-87654321<br>Email: info@sehatindonesia.co.id</p>
        </div>
    </main>
    <footer><p>Alawi &copy; 2025 | Terus berkembang, terus berinovasi.</p></footer>
    <?php
}

function pagePanduan(): void
{
    renderNav();
    ?>
    <div class="panduan-content">
        <div class="panduan-page fade-in">
            <div class="card shadow-sm title-card border-0 mb-4"><div class="card-body text-center"><h1 class="fw-bold mb-0">Panduan Penggunaan</h1></div></div>
            <div class="card shadow guide-card mb-4 border-0"><div class="card-body"><h5 class="mb-3 text-primary">Untuk Pasien</h5><ol><li>Login atau registrasi jika belum memiliki akun.</li><li>Pilih layanan pasien.</li><li>Lengkapi data, pembayaran, tanggal, dokter, lalu konfirmasi.</li><li>Cek status janji di riwayat.</li></ol></div></div>
            <div class="card shadow guide-card mb-4 border-0"><div class="card-body"><h5 class="mb-3 text-success">Untuk Laboratorium</h5><ol><li>Pilih menu pesan obat.</li><li>Pilih obat sesuai kebutuhan.</li><li>Cek jadwal pengambilan di riwayat obat.</li></ol></div></div>
        </div>
    </div>
    <?php
}

function pageReward(): void
{
    renderNav();
    $rewards = ['Top Hospital Award 2024', 'Green Hospital Certification', 'Pelayanan Terbaik BPJS 2022', 'Inovasi Layanan Digital Medis', 'Zero Infection Achievement', 'Transparansi Layanan Publik'];
    ?>
    <main class="reward-section page-shell">
        <h1 class="reward-title text-center">Penghargaan & Prestasi</h1>
        <div class="reward-list">
            <?php foreach ($rewards as $reward): ?>
                <div class="reward-card"><h2><?= e($reward) ?></h2><p>Pengakuan atas dedikasi dalam memberikan layanan kesehatan terbaik.</p></div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php
}

function pageFasilitas(): void
{
    renderNav();
    $items = [
        ['Rawat Jalan', 'Layanan konsultasi dokter umum dan spesialis untuk pasien rawat jalan.'],
        ['Rawat Inap', 'Kamar perawatan untuk pasien dengan pengawasan medis 24 jam.'],
        ['Ambulans', 'Layanan ambulans cepat dan tanggap darurat ke lokasi pasien.'],
        ['Laboratorium', 'Pemeriksaan darah, urin, dan tes laboratorium lainnya.'],
        ['Radiologi', 'Fasilitas untuk X-Ray, CT Scan, dan USG modern.'],
        ['Farmasi', 'Layanan pembelian obat resep dan konsultasi farmasi.'],
    ];
    ?>
    <main class="fasilitas-wrapper">
        <div class="fasilitas-container">
            <h2 class="text-center mb-5">Fasilitas Rumah Sakit</h2>
            <?php foreach ($items as $item): ?>
                <div class="card mb-3 shadow-sm fasilitas-card" data-fasilitas-card>
                    <div class="card-body">
                        <h5 class="card-title"><?= e($item[0]) ?></h5>
                        <p class="card-text"><?= e($item[1]) ?></p>
                        <div class="fasilitas-detail"><?= e($item[1]) ?> Layanan ini disiapkan untuk mendukung kebutuhan pasien secara aman dan nyaman.</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php
}
