<?php
require_once 'config/database.php';
require_once 'config/auth.php';

date_default_timezone_set('Asia/Jakarta');
harusLogin();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$dosen_id = 0;

if (!in_array($role, ['admin', 'dosen', 'mahasiswa'])) {
    die("Akses ditolak.");
}

// Ambil ID dosen jika login sebagai dosen
if ($role === 'dosen') {
    $stmt = mysqli_prepare($conn, "
        SELECT id FROM dosen WHERE user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $dosen = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$dosen) {
        die("Data dosen tidak ditemukan.");
    }

    $dosen_id = $dosen['id'];
}

// Ambil daftar sesi sesuai role
if ($role === 'admin') {
    $sql = "SELECT * FROM sesi_absensi
            ORDER BY tanggal DESC, jam_mulai DESC";
    $sesi = mysqli_query($conn, $sql);

} elseif ($role === 'dosen') {
    $stmt = mysqli_prepare($conn, "
        SELECT * FROM sesi_absensi
        WHERE dosen_id = ?
        ORDER BY tanggal DESC, jam_mulai DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $dosen_id);
    mysqli_stmt_execute($stmt);
    $sesi = mysqli_stmt_get_result($stmt);

} else {
    $stmt = mysqli_prepare($conn, "
        SELECT DISTINCT s.*
        FROM sesi_absensi s
        INNER JOIN absensi a ON s.id = a.sesi_id
        WHERE a.user_id = ?
        ORDER BY s.tanggal DESC, s.jam_mulai DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $sesi = mysqli_stmt_get_result($stmt);
}

// Ambil detail sesi jika dipilih
$id_sesi = (int)($_GET['id'] ?? 0);
$data_sesi = null;
$daftar_hadir = null;

if ($id_sesi > 0) {

    if ($role === 'admin') {
        $stmt = mysqli_prepare($conn, "
            SELECT * FROM sesi_absensi WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $id_sesi);

    } elseif ($role === 'dosen') {
        $stmt = mysqli_prepare($conn, "
            SELECT * FROM sesi_absensi
            WHERE id = ? AND dosen_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $id_sesi, $dosen_id);

    } else {
        $stmt = mysqli_prepare($conn, "
            SELECT s.*
            FROM sesi_absensi s
            INNER JOIN absensi a ON s.id = a.sesi_id
            WHERE s.id = ? AND a.user_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $id_sesi, $user_id);
    }

    mysqli_stmt_execute($stmt);
    $data_sesi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($data_sesi) {

        if ($role === 'mahasiswa') {
            $stmt = mysqli_prepare($conn, "
                SELECT u.nama, u.nim,
                       a.waktu_scan, a.status
                FROM absensi a
                INNER JOIN users u ON a.user_id = u.id
                WHERE a.sesi_id = ? AND a.user_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $id_sesi, $user_id);

        } else {
            $stmt = mysqli_prepare($conn, "
                SELECT u.nama, u.nim,
                       a.waktu_scan, a.status
                FROM absensi a
                INNER JOIN users u ON a.user_id = u.id
                WHERE a.sesi_id = ?
                ORDER BY a.waktu_scan ASC
            ");
            mysqli_stmt_bind_param($stmt, "i", $id_sesi);
        }

        mysqli_stmt_execute($stmt);
        $daftar_hadir = mysqli_stmt_get_result($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rekap Absensi - CampusHub</title>

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

        <?php if ($role === 'dosen'): ?>
            <a href="absensi.php">Kelola Absensi</a>
        <?php endif; ?>

        <button type="button" id="themeToggle" class="theme-btn">
            🌙
        </button>

        <span class="user-name">
            <?= htmlspecialchars($_SESSION['nama']) ?>
        </span>

        <a href="auth/logout.php" class="btn logout-btn">Logout</a>
    </div>
</nav>


<main class="absensi-container">

    <h1>Hasil Rekap Absensi</h1>

    <?php if ($role === 'admin'): ?>
        <p>Rekap seluruh kehadiran mahasiswa.</p>
    <?php elseif ($role === 'dosen'): ?>
        <p>Rekap kehadiran dari sesi yang Anda buat.</p>
    <?php else: ?>
        <p>Riwayat kehadiran pribadi Anda.</p>
    <?php endif; ?>

    <?php if ($id_sesi > 0): ?>

        <?php if ($data_sesi): ?>

            <section class="daftar-sesi">

                <a href="rekap_absensi.php" class="btn">
                    ← Kembali ke Daftar Sesi
                </a>

                <h2 style="margin-top:25px">
                    <?= htmlspecialchars($data_sesi['nama_kegiatan']) ?>
                </h2>

                <p>
                    Tanggal:
                    <?= htmlspecialchars($data_sesi['tanggal']) ?>
                </p>

                <p>
                    Jam:
                    <?= htmlspecialchars($data_sesi['jam_mulai']) ?>
                    -
                    <?= htmlspecialchars($data_sesi['jam_selesai']) ?>
                </p>

                <div class="sesi-card">

                    <h3>
                        <?= $role === 'mahasiswa'
                            ? 'Detail Kehadiran Saya'
                            : 'Daftar Mahasiswa Hadir' ?>
                    </h3>

                    <div class="daftar-hadir">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Waktu Scan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (mysqli_num_rows($daftar_hadir) > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($mhs = mysqli_fetch_assoc($daftar_hadir)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($mhs['nim'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($mhs['nama']) ?></td>
                                        <td><?= htmlspecialchars($mhs['waktu_scan']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($mhs['status'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" style="text-align:center">
                                        Belum ada mahasiswa yang melakukan absensi.
                                    </td>
                                </tr>

                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </section>

        <?php else: ?>

            <div class="pesan">
                Sesi tidak ditemukan atau Anda tidak memiliki akses.
            </div>

            <a href="rekap_absensi.php" class="btn">
                Kembali
            </a>

        <?php endif; ?>

    <?php else: ?>

        <section class="daftar-sesi">

            <h2>
                <?= $role === 'mahasiswa'
                    ? 'Riwayat Absensi Saya'
                    : 'Daftar Sesi Absensi' ?>
            </h2>

            <div class="sesi-grid">

                <?php if (mysqli_num_rows($sesi) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($sesi)): ?>

                        <div class="sesi-card">

                            <h3>
                                <?= htmlspecialchars($row['nama_kegiatan']) ?>
                            </h3>

                            <p>
                                Tanggal:
                                <?= htmlspecialchars($row['tanggal']) ?>
                            </p>

                            <p>
                                Jam:
                                <?= htmlspecialchars($row['jam_mulai']) ?>
                                -
                                <?= htmlspecialchars($row['jam_selesai']) ?>
                            </p>

                            <a class="btn"
                               href="rekap_absensi.php?id=<?= (int)$row['id'] ?>">
                                Lihat Rekap
                            </a>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="sesi-empty">
                        <h3>Belum Ada Riwayat Absensi</h3>
                        <p>Belum ada data kehadiran yang tersedia.</p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>

</main>

<footer>
    <p>&copy; <?= date('Y') ?> CampusHub - Universitas PGRI Silampari</p>
</footer>

<script src="assets/js/main.js"></script>

</body>
</html>
