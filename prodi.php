<?php
require_once 'config/database.php';
require_once 'config/auth.php';

harusLogin();

$query = mysqli_query($conn,
    "SELECT * FROM prodi ORDER BY nama_prodi ASC"
);

if (!$query) {
    die("Gagal mengambil data program studi.");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Program Studi - CampusHub</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/prodi.css">
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


    <!-- DAFTAR PROGRAM STUDI -->
    <section class="prodi-container">

        <h1>Program Studi</h1>

        <p class="prodi-description">
            Informasi program studi yang tersedia di CampusHub.
        </p>

        <div class="prodi-grid">

            <?php if (mysqli_num_rows($query) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($query)): ?>

                    <div class="prodi-card">

                        <span class="prodi-code">
                            <?= htmlspecialchars($row['kode_prodi']) ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($row['nama_prodi']) ?>
                        </h3>

                        <span class="prodi-level">
                            Jenjang <?= htmlspecialchars($row['jenjang']) ?>
                        </span>

                        <p>
                            <?= htmlspecialchars(
                                $row['deskripsi'] ?: 'Belum ada deskripsi.'
                            ) ?>
                        </p>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p>Belum ada data program studi.</p>

            <?php endif; ?>

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
