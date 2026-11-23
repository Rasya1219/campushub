<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function harusLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /campushub/auth/login.php");
        exit;
    }
}

function harusRole($role)
{
    harusLogin();

    if ($_SESSION['role'] !== $role) {
        header("Location: /campushub/index.php");
        exit;
    }
}

function harusAdmin()
{
    harusRole('admin');
}
