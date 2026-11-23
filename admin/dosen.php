<?php
require_once '../config/database.php';
require_once '../config/auth.php';

harusAdmin();

// Data untuk edit
$editData = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM dosen WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// Data program studi
$daftarProdi = mysqli_query($conn,
    "SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi"
);

// Data dosen
$daftarDosen = mysqli_query($conn,
    "SELECT dosen.*, prodi.nama_prodi
     FROM dosen
     LEFT JOIN prodi ON dosen.prodi_id = prodi.id
     ORDER BY dosen.nama_dosen"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dosen - CampusHub</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<nav class="navbar">
    <a href="../index.php" class="logo">
        <img src="../assets/images/logo.png" alt="CampusHub">
    </a>

    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="prodi.php">Prodi</a>
        <a href="dosen.php">Data Dosen</a>

        <button type="button" class="theme-btn" id="themeToggle">
            🌙
        </button>

        <span class="user-name">
            <?= htmlspecialchars($_SESSION['nama']) ?>
        </span>

        <a href="../auth/logout.php" class="btn logout-btn">
            Logout
        </a>
    </div>
</nav>

<main class="admin-container">

    <h1>Kelola Data Dosen</h1>
    <p class="admin-description">
        Tambah, edit, dan hapus data dosen CampusHub.
    </p>

    <?php if (isset($_GET['pesan'])): ?>
        <div class="admin-message">
            <?= htmlspecialchars($_GET['pesan']) ?>
        </div>
    <?php endif; ?>

    <!-- FORM DOSEN -->
    <section class="admin-form-card">

        <h2><?= $editData ? 'Edit Data Dosen' : 'Tambah Dosen' ?></h2>

        <form action="proses_dosen.php" method="POST">

            <input type="hidden" name="aksi"
                   value="<?= $editData ? 'edit' : 'tambah' ?>">

            <?php if ($editData): ?>
                <input type="hidden" name="id"
                       value="<?= $editData['id'] ?>">
                <input type="hidden" name="user_id"
                       value="<?= $editData['user_id'] ?>">
            <?php endif; ?>

            <label>Nama Dosen</label>
            <input type="text" name="nama_dosen"
                   value="<?= htmlspecialchars($editData['nama_dosen'] ?? '') ?>"
                   placeholder="Masukkan nama dosen" required>

            <label>NIP</label>
            <input type="text" name="nip"
                   value="<?= htmlspecialchars($editData['nip'] ?? '') ?>"
                   placeholder="Masukkan NIP" required>

            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($editData['email'] ?? '') ?>"
                   placeholder="Masukkan email" required>

            <label>Program Studi</label>
            <select name="prodi_id" required>
                <option value="">Pilih Program Studi</option>

                <?php while ($p = mysqli_fetch_assoc($daftarProdi)): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= isset($editData['prodi_id']) && $editData['prodi_id'] == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nama_prodi']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <?php if (!$editData): ?>
                <label>Password Akun Dosen</label>
                <div class="password-wrapper">
    <input type="password"
           name="password"
           id="passwordDosen"
           placeholder="Minimal 8 karakter"
           minlength="8"
           required>

    <button type="button"
        class="toggle-password"
        onclick="togglePassword('passwordDosen', this)">
    👁️
</button>
</div>
            <?php endif; ?>

            <button type="submit" class="btn admin-submit">
                <?= $editData ? 'Simpan Perubahan' : 'Tambah Dosen' ?>
            </button>

            <?php if ($editData): ?>
                <a href="dosen.php" class="btn admin-cancel">
                    Batal Edit
                </a>
            <?php endif; ?>

        </form>

    </section>

    <!-- TABEL DOSEN -->
    <section class="admin-table-card">

        <h2>Daftar Dosen</h2>

        <div class="table-responsive">
            <table class="admin-table">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Dosen</th>
                        <th>Email</th>
                        <th>Program Studi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($daftarDosen) > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($daftarDosen)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($d['nip']) ?></td>
                                <td><?= htmlspecialchars($d['nama_dosen']) ?></td>
                                <td><?= htmlspecialchars($d['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($d['nama_prodi'] ?? '-') ?></td>

                                <td class="admin-actions">

                                    <a href="dosen.php?edit=<?= $d['id'] ?>"
                                       class="btn edit-btn">
                                        Edit
                                    </a>

                                    <form action="proses_dosen.php"
                                          method="POST"
                                          class="form-hapus">

                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id"
                                               value="<?= $d['id'] ?>">
                                        <input type="hidden" name="user_id"
                                               value="<?= $d['user_id'] ?>">

                                        <button type="submit" class="btn delete-btn">
                                            Hapus
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-data">
                                Belum ada data dosen.
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>

            </table>
        </div>

    </section>

</main>

<footer>
    <p>© 2026 CampusHub. All Rights Reserved.</p>
    <p>Sistem Informasi Akademik dan Absensi Mahasiswa</p>
</footer>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin.js"></script>

</body>
</html>
