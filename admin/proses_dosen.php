<?php
require_once '../config/database.php';
require_once '../config/auth.php';

harusAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dosen.php');
    exit;
}

$aksi = $_POST['aksi'] ?? '';

function kembali($pesan) {
    header('Location: dosen.php?pesan=' . urlencode($pesan));
    exit;
}

// TAMBAH DOSEN
if ($aksi === 'tambah') {
    $nama = trim($_POST['nama_dosen'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $prodi = (int) ($_POST['prodi_id'] ?? 0);
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $nip === '' || $email === '' || $prodi <= 0 || $password === '') {
        kembali('Semua data wajib diisi!');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        kembali('Format email tidak valid!');
    }

    if (strlen($password) < 8) {
        kembali('Password minimal 8 karakter!');
    }

    mysqli_begin_transaction($conn);

    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (nama, email, password, role, prodi_id)
             VALUES (?, ?, ?, 'dosen', ?)"
        );

        mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $hash, $prodi);
        mysqli_stmt_execute($stmt);

        $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO dosen (user_id, nip, nama_dosen, prodi_id, email)
             VALUES (?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "issis", $user_id, $nip, $nama, $prodi, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        kembali('Data dosen berhasil ditambahkan!');

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        kembali('Gagal menambahkan dosen. Pastikan email dan NIP belum digunakan.');
    }
}

// EDIT DOSEN
if ($aksi === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $nama = trim($_POST['nama_dosen'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $prodi = (int) ($_POST['prodi_id'] ?? 0);

    if ($id <= 0 || $user_id <= 0 || $nama === '' || $nip === '' || $email === '' || $prodi <= 0) {
        kembali('Semua data wajib diisi!');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        kembali('Format email tidak valid!');
    }

    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare($conn,
            "UPDATE users
             SET nama = ?, email = ?, prodi_id = ?
             WHERE id = ? AND role = 'dosen'"
        );
        mysqli_stmt_bind_param($stmt, "ssii", $nama, $email, $prodi, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn,
            "UPDATE dosen
             SET nip = ?, nama_dosen = ?, prodi_id = ?, email = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssisii", $nip, $nama, $prodi, $email, $id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        kembali('Data dosen berhasil diperbarui!');

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        kembali('Gagal mengedit dosen. Periksa email dan NIP.');
    }
}

// HAPUS DOSEN
if ($aksi === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);
    $user_id = (int) ($_POST['user_id'] ?? 0);

    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM dosen WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn,
            "DELETE FROM users WHERE id = ? AND role = 'dosen'"
        );
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        kembali('Data dosen berhasil dihapus!');

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        kembali('Gagal menghapus dosen. Mungkin masih memiliki riwayat absensi.');
    }
}

kembali('Aksi tidak dikenali!');
