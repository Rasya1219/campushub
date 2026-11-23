
<?php
require_once '../config/database.php';
require_once '../config/auth.php';

harusAdmin();

$pesan = '';
$edit = null;

// TAMBAH PRODI
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_prodi']);
    $kode = trim($_POST['kode_prodi']);
    $jenjang = $_POST['jenjang'];
    $deskripsi = trim($_POST['deskripsi']);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO prodi (nama_prodi, kode_prodi, jenjang, deskripsi)
         VALUES (?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "ssss",
        $nama, $kode, $jenjang, $deskripsi);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: prodi.php?pesan=tambah");
        exit;
    } else {
        $pesan = "Gagal menambahkan prodi. Kode mungkin sudah digunakan.";
    }
}

// UPDATE PRODI
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama_prodi']);
    $kode = trim($_POST['kode_prodi']);
    $jenjang = $_POST['jenjang'];
    $deskripsi = trim($_POST['deskripsi']);

    $stmt = mysqli_prepare($conn,
        "UPDATE prodi
         SET nama_prodi=?, kode_prodi=?, jenjang=?, deskripsi=?
         WHERE id=?");

    mysqli_stmt_bind_param($stmt, "ssssi",
        $nama, $kode, $jenjang, $deskripsi, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: prodi.php?pesan=update");
        exit;
    } else {
        $pesan = "Gagal memperbarui data prodi.";
    }
}

// HAPUS PRODI
if (isset($_POST['hapus'])) {
    $id = (int) $_POST['id'];

    $stmt = mysqli_prepare($conn,
        "DELETE FROM prodi WHERE id=?");

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: prodi.php?pesan=hapus");
        exit;
    } else {
        $pesan = "Prodi tidak bisa dihapus karena masih digunakan.";
    }
}

// NOTIFIKASI
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == 'tambah') {
        $pesan = "Program studi berhasil ditambahkan!";
    } elseif ($_GET['pesan'] == 'update') {
        $pesan = "Program studi berhasil diperbarui!";
    } elseif ($_GET['pesan'] == 'hapus') {
        $pesan = "Program studi berhasil dihapus!";
    }
}

// AMBIL DATA UNTUK EDIT
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM prodi WHERE id=?");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// AMBIL SEMUA PRODI
$query = mysqli_query($conn,
    "SELECT * FROM prodi ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Prodi - CampusHub</title>

<script>
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark');
}
</script>

<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/prodi.css">

</head>

<body>

<nav class="navbar">
    <a href="../index.php" class="logo">
        <img src="../assets/images/logo.png" alt="CampusHub">
    </a>

    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../absensi.php">Absensi</a>
        <a href="prodi.php">Prodi</a>
        <a href="dosen.php">Dosen</a>
        <a href="../auth/logout.php">Logout</a>

        <button type="button" class="theme-btn" id="themeToggle">🌙</button>
    </div>
</nav>

<div class="admin-container">

    <h1>Kelola Program Studi</h1>
    <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>.</p>

    <?php if ($pesan): ?>
        <div class="message">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">

        <h2><?= $edit ? 'Edit Program Studi' : 'Tambah Program Studi' ?></h2>

        <form method="POST">

            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <label>Nama Program Studi</label>
            <input type="text" name="nama_prodi"
                value="<?= htmlspecialchars($edit['nama_prodi'] ?? '') ?>"
                required>

            <label>Kode Program Studi</label>
            <input type="text" name="kode_prodi"
                value="<?= htmlspecialchars($edit['kode_prodi'] ?? '') ?>"
                required>

            <label>Jenjang</label>
            <select name="jenjang" required>
                <option value="">Pilih Jenjang</option>

                <?php foreach (['D3','S1','S2','S3'] as $j): ?>
                    <option value="<?= $j ?>"
                        <?= isset($edit['jenjang']) && $edit['jenjang'] == $j ? 'selected' : '' ?>>
                        <?= $j ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="3"><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>

            <?php if ($edit): ?>
                <button type="submit" name="update" class="btn">
                    Simpan Perubahan
                </button>

                <a href="prodi.php" class="btn-delete"
                   style="display:inline-block;text-decoration:none">
                    Batal
                </a>
            <?php else: ?>
                <button type="submit" name="tambah" class="btn">
                    Tambah Prodi
                </button>
            <?php endif; ?>

        </form>
    </div>

    <div class="admin-card">

        <h2>Daftar Program Studi</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Prodi</th>
                    <th>Kode</th>
                    <th>Jenjang</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
            <?php $no = 1; ?>

            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_prodi']) ?></td>
                    <td><?= htmlspecialchars($row['kode_prodi']) ?></td>
                    <td><?= htmlspecialchars($row['jenjang']) ?></td>
                    <td><?= htmlspecialchars($row['deskripsi'] ?? '') ?></td>

                    <td>
                        <a class="btn-edit"
                           href="prodi.php?edit=<?= $row['id'] ?>">
                           Edit
                        </a>

                        <form method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus prodi ini?')">

                            <input type="hidden" name="id"
                                value="<?= $row['id'] ?>">

                            <button type="submit" name="hapus"
                                class="btn-delete">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>

            </tbody>
        </table>

    </div>
</div>

<script>
const themeToggle = document.getElementById('themeToggle');

function updateThemeIcon() {
    const dark = document.documentElement.classList.contains('dark');
    themeToggle.textContent = dark ? '☀️' : '🌙';
}

updateThemeIcon();

themeToggle.addEventListener('click', function() {
    document.documentElement.classList.toggle('dark');

    const dark = document.documentElement.classList.contains('dark');

    localStorage.setItem('theme', dark ? 'dark' : 'light');

    updateThemeIcon();
});
</script>

</body>
</html>