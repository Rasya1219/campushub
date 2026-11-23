<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Email dan password wajib diisi!";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, nama, email, password, role
             FROM users WHERE email = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            header("Location: ../index.php");
            exit;
        } else {
            $error = "Email atau password salah!";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusHub</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

<div class="login-page">

    <button type="button"
            class="theme-btn login-theme"
            id="themeToggle">🌙</button>

    <div class="login-card">

        <img src="../assets/images/logo.png"
             alt="CampusHub"
             class="login-logo">

        <h2>Selamat Datang!</h2>

        <p class="login-subtitle">
            Silakan login untuk mengakses CampusHub.
        </p>

        <?php if ($error): ?>
            <div class="login-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['daftar']) && $_GET['daftar'] === 'berhasil'): ?>
            <div class="login-success">
                Pendaftaran berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Email</label>
            <input type="email"
                   name="email"
                   placeholder="Masukkan email"
                   required>

            <label>Password</label>

            <div class="password-wrapper">
                <input type="password"
                       name="password"
                       id="password"
                       placeholder="Masukkan password"
                       required>

                <button type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', this)">
                    👁️
                </button>
            </div>

            <button type="submit" class="btn login-submit">
                Login
            </button>

        </form>

        <a href="register.php" class="login-back">
            Belum punya akun? Daftar sekarang
        </a>

        <a href="../index.php" class="login-back">
            ← Kembali ke Beranda
        </a>

    </div>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/auth.js"></script>

</body>
</html>
