<?php
require_once 'config/database.php';
require_once 'config/auth.php';

harusLogin();

$query = mysqli_query($conn,
    "SELECT dosen.nip, dosen.nama_dosen, dosen.email,
            prodi.nama_prodi, prodi.jenjang
     FROM dosen
     LEFT JOIN prodi ON dosen.prodi_id = prodi.id
     ORDER BY dosen.nama_dosen ASC"
);

if (!$query) {
    die("Gagal mengambil data dosen.");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Dosen - CampusHub</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dosen.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">

        <a href="index.php" class="logo">
            <img src="assets/images/logo.png" alt="CampusHub">
        </a>

        <div class="nav-links">

            <a href="index.php">Home</a>
            <a href="absensi.php">Absensi</a>
            <a href="prodi.php">Prodi</a>
            <a href="dosen.php">Dosen</a>

            <button type="button"
                    class="theme-btn"
                    id="themeToggle">
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

                <a href="auth/login.php" class="btn">Login</a>

            <?php endif; ?>

        </div>

    </nav>


    <!-- DAFTAR DOSEN -->
    <main class="dosen-container">

        <h1>Data Dosen</h1>

        <p class="dosen-description">
            Informasi dosen dan program studi di CampusHub.
        </p>

        <div class="dosen-grid">

            <?php if (mysqli_num_rows($query) > 0): ?>

                <?php while ($d = mysqli_fetch_assoc($query)): ?>

                    <div class="dosen-card">

                        <div class="dosen-avatar">
                            <?= strtoupper(substr($d['nama_dosen'], 0, 1)) ?>
                        </div>

                        <h3>
                            <?= htmlspecialchars($d['nama_dosen']) ?>
                        </h3>

                        <p class="dosen-nip">
                            NIP: <?= htmlspecialchars($d['nip']) ?>
                        </p>

                        <p>
                            Email:
                            <?= htmlspecialchars($d['email'] ?? '-') ?>
                        </p>

                        <div class="dosen-prodi">
                            <?= htmlspecialchars($d['nama_prodi'] ?? 'Prodi tidak tersedia') ?>
                        </div>

                        <span class="dosen-level">
                            Jenjang <?= htmlspecialchars($d['jenjang'] ?? '-') ?>
                        </span>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="dosen-empty">
                    <h3>Belum Ada Data Dosen</h3>
                    <p>Data dosen belum tersedia saat ini.</p>
                </div>

            <?php endif; ?>

        </div>

    </main>


    <!-- FOOTER -->
    <footer>
        <p>© 2026 CampusHub. All Rights Reserved.</p>
        <p>Sistem Informasi Akademik dan Absensi Mahasiswa</p>
    </footer>


    <script src="assets/js/main.js"></script>

</body>
</html>
 