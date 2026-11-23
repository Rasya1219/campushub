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
$pesan = "";

/* PROSES HAPUS */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    mysqli_begin_transaction($conn);

    try {
        if ($aksi === 'hapus_pertemuan') {
            $sesi_id = (int)$_POST['sesi_id'];

            $stmt = mysqli_prepare($conn, "
                SELECT id FROM sesi_absensi
                WHERE id = ? AND dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $sesi_id, $dosen_id);
            mysqli_stmt_execute($stmt);

            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                throw new Exception("Pertemuan tidak ditemukan.");
            }

            $stmt = mysqli_prepare($conn, "DELETE FROM absensi WHERE sesi_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $sesi_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn, "
                DELETE FROM sesi_absensi
                WHERE id = ? AND dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $sesi_id, $dosen_id);
            mysqli_stmt_execute($stmt);
        }

        elseif ($aksi === 'hapus_jadwal') {
            $jadwal_id = (int)$_POST['jadwal_id'];

            $stmt = mysqli_prepare($conn, "
                SELECT id FROM jadwal_absensi
                WHERE id = ? AND dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $dosen_id);
            mysqli_stmt_execute($stmt);

            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                throw new Exception("Jadwal tidak ditemukan.");
            }

            $stmt = mysqli_prepare($conn, "
                DELETE a FROM absensi a
                INNER JOIN sesi_absensi s ON s.id = a.sesi_id
                WHERE s.jadwal_id = ? AND s.dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $dosen_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn, "
                DELETE FROM sesi_absensi
                WHERE jadwal_id = ? AND dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $dosen_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn, "
                DELETE FROM jadwal_absensi
                WHERE id = ? AND dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $dosen_id);
            mysqli_stmt_execute($stmt);
        }

        elseif ($aksi === 'hapus_semua') {
            $stmt = mysqli_prepare($conn, "
                DELETE a FROM absensi a
                INNER JOIN sesi_absensi s ON s.id = a.sesi_id
                WHERE s.dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "i", $dosen_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn, "
                DELETE FROM sesi_absensi WHERE dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "i", $dosen_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn, "
                DELETE FROM jadwal_absensi WHERE dosen_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "i", $dosen_id);
            mysqli_stmt_execute($stmt);
        }

        else {
            throw new Exception("Aksi tidak dikenal.");
        }

        mysqli_commit($conn);
        header("Location: absensi.php?hapus=berhasil");
        exit;

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $pesan = "Gagal menghapus data: " . $e->getMessage();
    }
}

/* BUAT JADWAL DAN 16 PERTEMUAN */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_jadwal'])) {
    $nama = trim($_POST['nama_kegiatan'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $mulai = $_POST['jam_mulai'] ?? '';
    $selesai = $_POST['jam_selesai'] ?? '';

    if ($nama === '' || $tanggal === '' || $mulai === '' || $selesai === '') {
        $pesan = "Semua kolom wajib diisi.";
    } elseif ($selesai <= $mulai) {
        $pesan = "Jam selesai harus setelah jam mulai.";
    } else {
        mysqli_begin_transaction($conn);

        try {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO jadwal_absensi
                (dosen_id, nama_kegiatan, tanggal_mulai, jam_mulai, jam_selesai, aktif)
                VALUES (?, ?, ?, ?, ?, 1)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "issss",
                $dosen_id,
                $nama,
                $tanggal,
                $mulai,
                $selesai
            );
            mysqli_stmt_execute($stmt);

            $jadwal_id = mysqli_insert_id($conn);

            $stmt_sesi = mysqli_prepare($conn, "
                INSERT INTO sesi_absensi
                (jadwal_id, dosen_id, nama_kegiatan, tanggal,
                 jam_mulai, jam_selesai, qr_token, qr_expired_at, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'terjadwal')
            ");

            for ($i = 0; $i < 16; $i++) {
                $tgl = new DateTime($tanggal);
                $tgl->modify("+".($i * 7)." days");
                $tanggal_sesi = $tgl->format('Y-m-d');

                $token = bin2hex(random_bytes(32));
                $expired = $tanggal_sesi . " " . $selesai . ":00";

                mysqli_stmt_bind_param(
                    $stmt_sesi,
                    "iissssss",
                    $jadwal_id,
                    $dosen_id,
                    $nama,
                    $tanggal_sesi,
                    $mulai,
                    $selesai,
                    $token,
                    $expired
                );

                mysqli_stmt_execute($stmt_sesi);
            }

            mysqli_commit($conn);
            header("Location: absensi.php?buat=berhasil");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $pesan = "Gagal membuat jadwal: " . $e->getMessage();
        }
    }
}

if (isset($_GET['hapus'])) {
    $pesan = "Data berhasil dihapus.";
} elseif (isset($_GET['buat'])) {
    $pesan = "Jadwal dengan 16 pertemuan berhasil dibuat.";
} elseif (isset($_GET['edit'])) {
    $pesan = "Pertemuan berhasil diperbarui.";
}

/* UPDATE STATUS SESI */
$stmt = mysqli_prepare($conn, "
    UPDATE sesi_absensi
    SET status = CASE
        WHEN CONCAT(tanggal, ' ', jam_selesai) < NOW() THEN 'selesai'
        WHEN CONCAT(tanggal, ' ', jam_mulai) <= NOW()
             AND CONCAT(tanggal, ' ', jam_selesai) >= NOW() THEN 'aktif'
        ELSE 'terjadwal'
    END
    WHERE dosen_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $dosen_id);
mysqli_stmt_execute($stmt);

/* AMBIL JADWAL */
$stmt = mysqli_prepare($conn, "
    SELECT * FROM jadwal_absensi
    WHERE dosen_id = ? AND aktif = 1
    ORDER BY id DESC
");
mysqli_stmt_bind_param($stmt, "i", $dosen_id);
mysqli_stmt_execute($stmt);
$jadwal_list = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi QR - CampusHub</title>

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
        <a href="prodi.php">Prodi</a>
        <a href="dosen.php">Dosen</a>
        <a href="absensi.php">Absensi</a>

        <button type="button" id="themeToggle" class="theme-btn">🌙</button>

        <span class="user-name">
            <?= htmlspecialchars($_SESSION['nama']) ?>
        </span>

        <a href="auth/logout.php" class="btn logout-btn">Logout</a>
    </div>
</nav>

<main class="absensi-container">

    <h1>Kelola Absensi</h1>
    <p>Buat dan kelola 16 pertemuan mata kuliah.</p>

    <?php if ($pesan): ?>
        <div class="pesan"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <section class="form-absensi">
        <h2>Buat Jadwal Mata Kuliah</h2>

        <form method="POST">
            <input type="hidden" name="buat_jadwal" value="1">

            <label>Mata Kuliah</label>
            <input type="text" name="nama_kegiatan"
                   placeholder="Contoh: Pemrograman Web" required>

            <label>Tanggal Pertemuan Pertama</label>
            <input type="date" name="tanggal" required>

            <label>Jam Mulai</label>
            <input type="time" name="jam_mulai" required>

            <label>Jam Selesai</label>
            <input type="time" name="jam_selesai" required>

            <button type="submit">Buat 16 Pertemuan</button>
        </form>
    </section>

    <section class="daftar-sesi">

        <h2>Daftar Jadwal Mata Kuliah</h2>

        <form method="POST" class="btn-hapus-semua"
              onsubmit="return confirm('YAKIN hapus SEMUA jadwal, seluruh QR, dan seluruh data absensi milik Anda?')">
            <input type="hidden" name="aksi" value="hapus_semua">
            <button type="submit" class="btn-aksi btn-hapus">
                Hapus Semua Jadwal
            </button>
        </form>

        <?php if (mysqli_num_rows($jadwal_list) > 0): ?>

            <?php while ($jadwal = mysqli_fetch_assoc($jadwal_list)): ?>

                <?php
                $jadwal_id = (int)$jadwal['id'];

                $stmt_sesi = mysqli_prepare($conn, "
                    SELECT * FROM sesi_absensi
                    WHERE jadwal_id = ? AND dosen_id = ?
                    ORDER BY tanggal ASC
                ");

                mysqli_stmt_bind_param($stmt_sesi, "ii", $jadwal_id, $dosen_id);
                mysqli_stmt_execute($stmt_sesi);
                $sesi_list = mysqli_stmt_get_result($stmt_sesi);
                ?>

                <div class="jadwal-card">

                    <div class="jadwal-header">
                        <h3><?= htmlspecialchars($jadwal['nama_kegiatan']) ?></h3>

                        <form method="POST"
                              onsubmit="return confirm('Hapus jadwal ini beserta seluruh 16 pertemuan dan data absensinya?')">
                            <input type="hidden" name="aksi" value="hapus_jadwal">
                            <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">

                            <button type="submit" class="btn-aksi btn-hapus">
                                Hapus Jadwal
                            </button>
                        </form>
                    </div>

                    <div class="jadwal-info">
                        <p>Mulai: <?= htmlspecialchars($jadwal['tanggal_mulai']) ?></p>
                        <p>Jam:
                            <?= htmlspecialchars($jadwal['jam_mulai']) ?>
                            -
                            <?= htmlspecialchars($jadwal['jam_selesai']) ?>
                        </p>
                        <p>Total: <?= mysqli_num_rows($sesi_list) ?> Pertemuan</p>
                    </div>

                    <details>
                        <summary class="btn-lihat">
                            Lihat Pertemuan
                        </summary>

                        <div class="pertemuan-list">

                            <?php
                            $nomor = 1;
                            while ($row = mysqli_fetch_assoc($sesi_list)):
                            ?>

                                <div class="pertemuan-item">

                                    <h4>Pertemuan <?= $nomor++ ?></h4>

                                    <p>Tanggal:
                                        <?= htmlspecialchars($row['tanggal']) ?>
                                    </p>

                                    <p>Jam:
                                        <?= htmlspecialchars($row['jam_mulai']) ?>
                                        -
                                        <?= htmlspecialchars($row['jam_selesai']) ?>
                                    </p>

                                    <p>Status:
                                        <?= htmlspecialchars($row['status']) ?>
                                    </p>

                                    <div class="qr-code"
                                         data-token="<?= htmlspecialchars($row['qr_token'], ENT_QUOTES) ?>">
                                    </div>

                                    <p class="qr-caption">
                                        Scan QR untuk membuka halaman absensi
                                    </p>

                                    <div class="aksi-pertemuan">

                                <!-- TOMBOL LIHAT REKAP -->
                                    <a href="rekap_absensi.php?id=<?= (int)$row['id'] ?>"
                                       class="btn-aksi btn-lihat-rekap">
                                       Lihat Rekap
                                    </a>

                                <!-- TOMBOL EDIT -->
                                    <a href="edit_absensi.php?id=<?= (int)$row['id'] ?>"
                                       class="btn-aksi btn-edit">
                                       Edit Pertemuan
                                    </a>

                                <!-- TOMBOL HAPUS -->
                                    <form method="POST" class="form-hapus"
                                        onsubmit="return confirm('Hapus pertemuan ini beserta data absensi mahasiswanya?')">

                                        <input type="hidden" name="aksi" value="hapus_pertemuan">

                                        <input type="hidden" name="sesi_id"
                                            value="<?= (int)$row['id'] ?>">

                                        <button type="submit" class="btn-aksi btn-hapus">
                                            Hapus Pertemuan
                                        </button>
                                    </form>

                                    </div>

                                </div>

                            <?php endwhile; ?>

                        </div>
                    </details>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="sesi-empty">
                <h3>Belum Ada Jadwal</h3>
                <p>Buat jadwal mata kuliah melalui formulir di atas.</p>
            </div>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>&copy; <?= date('Y') ?> CampusHub - Universitas PGRI Silampari</p>
</footer>

<script src="assets/js/main.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
document.querySelectorAll(".qr-code").forEach(function(element) {
    const token = element.dataset.token;

    const url = "https://campushub2026.wuaze.com/scan.php?token=" + encodeURIComponent(token);

    new QRCode(element, {
        text: url,
        width: 180,
        height: 180
    });
});
</script>

</body>
</html>