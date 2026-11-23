<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $prodi_id = (int) ($_POST['prodi_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($nama === '' || $nim === '' || $email === '' || $prodi_id <= 0) {
        $error = "Semua data wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter!";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $cekProdi = mysqli_prepare($conn,
            "SELECT id FROM prodi WHERE id = ?"
        );
        mysqli_stmt_bind_param($cekProdi, "i", $prodi_id);
        mysqli_stmt_execute($cekProdi);
        $hasilProdi = mysqli_stmt_get_result($cekProdi);

        if (mysqli_num_rows($hasilProdi) === 0) {
            $error = "Program studi tidak ditemukan!";
        } else {
            $cek = mysqli_prepare($conn,
                "SELECT id FROM users WHERE email = ? OR nim = ?"
            );
            mysqli_stmt_bind_param($cek, "ss", $email, $nim);
            mysqli_stmt_execute($cek);
            $hasil = mysqli_stmt_get_result($cek);

            if (mysqli_num_rows($hasil) > 0) {
                $error = "Email atau NIM sudah terdaftar!";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare($conn,
                    "INSERT INTO users
                    (nama, nim, email, password, role, prodi_id)
                    VALUES (?, ?, ?, ?, 'mahasiswa', ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt, "ssssi",
                    $nama, $nim, $email, $hash, $prodi_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    header("Location: login.php?daftar=berhasil");
                    exit;
                } else {
                    $error = "Pendaftaran gagal. Silakan coba lagi.";
                }

                mysqli_stmt_close($stmt);
            }

            mysqli_stmt_close($cek);
        }

        mysqli_stmt_close($cekProdi);
    }
}

$daftarProdi = mysqli_query($conn,
    "SELECT id, nama_prodi FROM prodi ORDER BY nama_prodi"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - CampusHub</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

<div class="register-page">

    <button type="button"
            class="theme-btn register-theme"
            id="themeToggle">🌙</button>

    <div class="register-card">

        <img src="../assets/images/logo.png"
             class="register-logo"
             alt="CampusHub">

        <h2>Buat Akun Mahasiswa</h2>
        <p>Daftar untuk mengakses CampusHub.</p>

        <?php if ($error): ?>
            <div class="register-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Nama Lengkap</label>
            <input type="text" name="nama"
                   placeholder="Masukkan nama lengkap" required>

            <label>NIM</label>
            <input type="text" name="nim"
                   placeholder="Masukkan NIM" required>

            <label>Email</label>
            <input type="email" name="email"
                   placeholder="Masukkan email" required>

            <label>Program Studi</label>
            <select name="prodi_id" required>
                <option value="">Pilih Program Studi</option>

                <?php while ($p = mysqli_fetch_assoc($daftarProdi)): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['nama_prodi']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password"
                       id="password"
                       placeholder="Minimal 8 karakter"
                       minlength="8" required>

                <button type="button" class="toggle-password"
                        onclick="togglePassword('password', this)">
                    👁
                </button>
            </div>

            <label>Konfirmasi Password</label>
            <div class="password-wrapper">
                <input type="password" name="konfirmasi"
                       id="konfirmasi"
                       placeholder="Ulangi password" required>

                <button type="button" class="toggle-password"
                        onclick="togglePassword('konfirmasi', this)">
                    👁
                </button>
            </div>

            <button type="submit" class="btn">
                Daftar Sekarang
            </button>

        </form>

        <a href="login.php" class="register-link">
            Sudah punya akun? Login
        </a>

    </div>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/auth.js"></script>

</body>
</html>
