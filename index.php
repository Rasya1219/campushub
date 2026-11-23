<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Statistik mahasiswa
$qMahasiswa = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM users WHERE role='mahasiswa'"
);
$total_mahasiswa = mysqli_fetch_assoc($qMahasiswa)['total'];

// Statistik program studi
$qProdi = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM prodi"
);
$total_prodi = mysqli_fetch_assoc($qProdi)['total'];

// Statistik dosen
$qDosen = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM dosen"
);
$total_dosen = mysqli_fetch_assoc($qDosen)['total'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusHub - Sistem Informasi Akademik</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">

        <a href="index.php" class="logo">
            <img src="assets/images/logo.png" alt="CampusHub">
        </a>

        <div class="nav-links">

            <a href="index.php">Home</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen'): ?>
                <a href="absensi.php">Absensi</a>
            <?php else: ?>
                <a href="rekap_absensi.php">Absensi</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin/prodi.php">Prodi</a>
            <?php else: ?>
                <a href="prodi.php">Prodi</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin/dosen.php">Dosen</a>
            <?php else: ?>
                <a href="dosen.php">Dosen</a>
            <?php endif; ?>

            <button
                type="button"
                class="theme-btn"
                id="themeToggle"
                aria-label="Ganti tema">
                🌙
            </button>

            <?php if (isset($_SESSION['user_id'])): ?>

                <span class="user-name">
                    <?= htmlspecialchars($_SESSION['nama']) ?>
                </span>

                <a href="auth/logout.php" class="btn logout-btn">
                    Logout
                </a>

            <?php else: ?>

                <a href="auth/login.php" class="btn">
                    Login
                </a>

            <?php endif; ?>

        </div>

    </nav>


    <!-- HERO SECTION -->
    <section class="hero">

        <h1>
            Explore Your Campus, Build Your Future.
        </h1>

        <p>
            Temukan informasi akademik, lakukan absensi harian
            menggunakan QR Code, dan akses informasi program
            studi serta dosen melalui CampusHub.
        </p>

        <?php if (isset($_SESSION['role'])): ?>

    <?php if ($_SESSION['role'] === 'dosen'): ?>

        <a href="absensi.php" class="btn">
            Mulai Absensi →
        </a>

    <?php else: ?>

        <a href="rekap_absensi.php" class="btn">
            Hasil Rekap Absensi →
        </a>

    <?php endif; ?>

<?php else: ?>

    <a href="auth/login.php" class="btn">
        Login untuk Absensi →
    </a>

<?php endif; ?>

    </section>


    <!-- STATISTIK -->
    <section class="container">

        <h2>Statistik CampusHub</h2>

        <div class="stats">

            <div class="stat-card">
                <h3><?= $total_mahasiswa ?></h3>
                <p>Mahasiswa Terdaftar</p>
            </div>

            <div class="stat-card">
                <h3><?= $total_prodi ?></h3>
                <p>Program Studi</p>
            </div>

            <div class="stat-card">
                <h3><?= $total_dosen ?></h3>
                <p>Dosen Terdaftar</p>
            </div>

        </div>

    </section>


    <!-- INFORMASI AKADEMIK -->
    <section class="container">

        <h2>Informasi Akademik</h2>

        <div class="event-list">

            <div class="event-card">
                <h3>Absensi Harian</h3>
                <p>
                    Lakukan pencatatan kehadiran mahasiswa
                    menggunakan QR Code dinamis.
                </p>
                <?php if (isset($_SESSION['role'])): ?>

    <?php if ($_SESSION['role'] === 'dosen'): ?>
        <a href="absensi.php">Buka Absensi →</a>
    <?php else: ?>
        <a href="rekap_absensi.php">Lihat Rekap →</a>
    <?php endif; ?>

<?php else: ?>
    <a href="auth/login.php">Login untuk Absensi →</a>
<?php endif; ?>
            </div>

            <div class="event-card">
                <h3>Program Studi</h3>
                <p>
                    Lihat informasi dan data program studi
                    yang tersedia di kampus.
                </p>
                <a href="prodi.php">Lihat Prodi →</a>
            </div>

            <div class="event-card">
                <h3>Data Dosen</h3>
                <p>
                    Lihat informasi dosen dan program studi
                    yang menjadi tanggung jawabnya.
                </p>
                <a href="dosen.php">Lihat Dosen →</a>
            </div>

        </div>

    </section>


    <!-- FOOTER -->
    <footer>

        <p>© 2026 CampusHub. All Rights Reserved.</p>

        <p>Sistem Informasi Akademik dan Absensi Mahasiswa</p>

    </footer>


    <!-- JAVASCRIPT -->
    <script src="assets/js/main.js"></script>

</body>
</html>
