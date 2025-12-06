<?php
// auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user login
function require_login() {
    if (empty($_SESSION['id_user'])) {
        header("Location: login.php");
        exit;
    }
}

// Cek role
function require_role($allowed_roles) {
    require_login();
    if (!in_array($_SESSION['role'], (array)$allowed_roles)) {
        http_response_code(403);
        echo "Akses ditolak";
        exit;
    }
}

// Ambil info user
function current_user() {
    return [
        'id_user' => $_SESSION['id_user'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}
