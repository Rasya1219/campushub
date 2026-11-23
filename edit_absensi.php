<?php
require_once 'config/database.php';
require_once 'config/auth.php';

date_default_timezone_set('Asia/Jakarta');
harusLogin();

if ($_SESSION['role'] !== 'dosen') {
    die("Akses ditolak.");
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT id FROM dosen WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$dosen = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$dosen) {
    die("Data dosen tidak ditemukan.");
}

$dosen_id = (int)$dosen['id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = mysqli_prepare($conn, "
    SELECT * FROM sesi_absensi
    WHERE id = ? AND dosen_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $id, $dosen_id);
mysqli_stmt_execute($stmt);
$sesi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sesi) {
    die("Pertemuan tidak ditemukan.");
}

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kegiatan'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $mulai = $_POST['jam_mulai'] ?? '';
    $selesai = $_POST['jam_selesai'] ?? '';

    if ($nama === '' || $tanggal === '' || $mulai === '' || $selesai === '') {
        $pesan = "Semua kolom wajib diisi.";
    } elseif ($selesai <= $mulai) {
        $pesan = "Jam selesai harus setelah jam mulai.";
    } else {
        $expired = $tanggal . " " . $selesai . ":00";
        $token_baru = bin2hex(random_bytes(32));

        $stmt = mysqli_prepare($conn, "
            UPDATE sesi_absensi
            SET nama_kegiatan = ?,
                tanggal = ?,
                jam_mulai = ?,
                jam_selesai = ?,
                qr_token = ?,
                qr_expired_at = ?
            WHERE id = ? AND dosen_id = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssii",
            $nama,
            $tanggal,
            $mulai,
            $selesai,
            $token_baru,
            $expired,
            $id,
            $dosen_id
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: absensi.php?edit=berhasil");
            exit;
        } else {
            $pesan = "Gagal mengubah pertemuan. Periksa apakah tanggalnya bertabrakan dengan pertemuan lain.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pertemuan - CampusHub</title>

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

        <button type="button" id="themeToggle" class="theme-btn">🌙</button>

        <span class="user-name">
            <?= htmlspecialchars($_SESSION['nama']) ?>
        </span>

        <a href="auth/logout.php" class="btn logout-btn">Logout</a>
    </div>
</nav>

<main class="absensi-container">

    <h1>Edit Pertemuan</h1>

    <section class="form-absensi">

        <?php if ($pesan): ?>
            <div class="pesan"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">

            <label>Mata Kuliah</label>
            <input
                type="text"
                name="nama_kegiatan"
                value="<?= htmlspecialchars($sesi['nama_kegiatan']) ?>"
                required
            >

            <label>Tanggal Pertemuan</label>
            <input
                type="date"
                name="tanggal"
                value="<?= htmlspecialchars($sesi['tanggal']) ?>"
                required
            >

            <label>Jam Mulai</label>
            <input
                type="time"
                name="jam_mulai"
                value="<?= htmlspecialchars(substr($sesi['jam_mulai'], 0, 5)) ?>"
                required
            >

            <label>Jam Selesai</label>
            <input
                type="time"
                name="jam_selesai"
                value="<?= htmlspecialchars(substr($sesi['jam_selesai'], 0, 5)) ?>"
                required
            >

            <button type="submit">Simpan Perubahan</button>
            <a href="absensi.php" class="btn">Batal</a>
        </form>

    </section>

</main>

<script src="assets/js/main.js"></script>

</body>
</html>