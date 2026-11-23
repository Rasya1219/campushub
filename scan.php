<?php
require_once 'config/database.php';
require_once 'config/auth.php';

date_default_timezone_set('Asia/Jakarta');

harusLogin();

if ($_SESSION['role'] !== 'mahasiswa') {
    die("Halaman ini hanya dapat diakses oleh mahasiswa.");
}

$pesan = "";
$berhasil = false;
$sesi = null;

$token = $_GET['token'] ?? '';

if ($token === '') {
    die("QR Code tidak valid.");
}

// Cari sesi absensi
$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM sesi_absensi
    WHERE qr_token = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sesi = mysqli_fetch_assoc($result);

if (!$sesi) {
    die("Sesi absensi tidak ditemukan.");
}

$user_id = $_SESSION['user_id'];

$sekarang = date('Y-m-d H:i:s');
$waktu_mulai = $sesi['tanggal'] . ' ' . $sesi['jam_mulai'];
$waktu_selesai = $sesi['tanggal'] . ' ' . $sesi['jam_selesai'];

// Cek apakah sudah pernah absen
$stmt = mysqli_prepare($conn, "
    SELECT id FROM absensi
    WHERE sesi_id = ? AND user_id = ?
");

mysqli_stmt_bind_param($stmt, "ii", $sesi['id'], $user_id);
mysqli_stmt_execute($stmt);
$result_absen = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result_absen) > 0) {
    $pesan = "Kamu sudah melakukan absensi pada sesi ini.";
} elseif ($sekarang < $waktu_mulai) {
    $pesan = "Absensi belum dibuka.";
} elseif ($sekarang > $sesi['qr_expired_at'] || $sekarang > $waktu_selesai) {
    $pesan = "Waktu absensi sudah berakhir.";
} else {
    $pesan = "Silakan tekan tombol Hadir untuk melakukan absensi.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pesan === "Silakan tekan tombol Hadir untuk melakukan absensi.") {

    $waktu_scan = date('Y-m-d H:i:s');

    $batas_terlambat = date(
        'Y-m-d H:i:s',
        strtotime($waktu_mulai . ' +15 minutes')
    );

    $status = ($waktu_scan <= $batas_terlambat)
        ? 'hadir'
        : 'terlambat';

    $stmt = mysqli_prepare($conn, "
        INSERT INTO absensi (sesi_id, user_id, waktu_scan, status)
        VALUES (?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "iiss",
        $sesi['id'],
        $user_id,
        $waktu_scan,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {
        $berhasil = true;
        $pesan = "Absensi berhasil! Status: " . ucfirst($status);
    } else {
        $pesan = "Gagal menyimpan absensi. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Scan Absensi - CampusHub</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/absensi.css">
</head>

<body>

<nav class="navbar">
    <div class="logo">
        <img src="assets/images/logo.png" alt="Logo CampusHub">
        CampusHub
    </div>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="absensi.php">Absensi</a>

        <button
            type="button"
            id="themeToggle"
            class="theme-btn">
            🌙
        </button>

        <span class="user-name">
            <?= htmlspecialchars($_SESSION['nama']) ?>
        </span>

        <a href="auth/logout.php" class="btn logout-btn">Logout</a>
    </div>
</nav>

<main class="absensi-container">

    <section class="form-absensi">

        <h1>Absensi Mahasiswa</h1>

        <h2>
            <?= htmlspecialchars($sesi['nama_kegiatan']) ?>
        </h2>

        <p>
            Tanggal:
            <?= htmlspecialchars($sesi['tanggal']) ?>
        </p>

        <p>
            Jam:
            <?= htmlspecialchars($sesi['jam_mulai']) ?>
            -
            <?= htmlspecialchars($sesi['jam_selesai']) ?>
        </p>

        <p>
            Mahasiswa:
            <?= htmlspecialchars($_SESSION['nama']) ?>
        </p>

        <div class="pesan">
            <?= htmlspecialchars($pesan) ?>
        </div>

        <?php if ($berhasil): ?>

            <h2>Terima kasih, absensi tersimpan!</h2>

        <?php elseif ($pesan === "Silakan tekan tombol Hadir untuk melakukan absensi."): ?>

            <form method="POST">
                <button type="submit">
                    Saya Hadir
                </button>
            </form>

        <?php endif; ?>

        <a href="index.php" class="btn">
            Kembali ke Beranda
        </a>

    </section>

</main>

<footer>
    <p>&copy; <?= date('Y') ?> CampusHub - Universitas PGRI Silampari</p>
</footer>

<script src="assets/js/main.js"></script>

</body>
</html>
