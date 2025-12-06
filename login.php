<?php
require "db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id_user, username, password, role FROM user WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res && password_verify($password, $res['password'])) {
            $_SESSION['id_user'] = $res['id_user'];
            $_SESSION['username'] = $res['username'];
            $_SESSION['role'] = $res['role'];

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    }
}

// login.php (template + hooks untuk backend)
// BACKEND: require 'db.php' di bagian atas bila ingin langsung query DB
// BACKEND: jika memakai header.php/layout include, comment bagian <head> / navbar di bawah.

// Jika Anda memakai layout.php sebagai header/footer terpisah:
// include 'header.php'; // pastikan session_start() dipanggil di header atau di sini

// BEGIN: jika backend ingin meng-handle POST, letakkan kode autentikasi di sini

?>




<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login — Sistem Inventori</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; }
    .wrap { max-width: 420px; margin: 6vh auto; }
    .card { border: none; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    .brand { font-weight:700; letter-spacing:0.3px; }
  </style>
</head>
<body>
  <main class="wrap">
    <div class="text-center mb-3">
      <h3 class="brand">PT Indotar — Inventori</h3>
      <p class="text-muted small">Masuk untuk mengelola stok & transaksi</p>
    </div>

    <!-- BACKEND: tampilkan message jika $error / $success di-set -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-4">
        <!-- Form: backend akan memproses POST di halaman ini -->
        <form id="login-form" method="post" action="login.php" novalidate>
          <!-- BACKEND: sisipkan CSRF token di sini jika diperlukan -->
          <!-- <input type="hidden" name="csrf_token" value="<?php // echo $_SESSION['csrf_token'] ?? ''; ?>"> -->

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" id="username" type="text" class="form-control" placeholder="Masukkan username" required autofocus>
            <div class="invalid-feedback">Masukkan username.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" id="password" type="password" class="form-control" placeholder="Masukkan password" required>
            <div class="invalid-feedback">Masukkan password.</div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary" id="btn-login">Masuk</button>
          </div>

          <div class="mt-3 text-center small text-muted">
            Lupa password? Hubungi admin.
          </div>
        </form>
      </div>
    </div>

    <div class="text-center mt-3 small text-muted">
      &copy; PT Indotar Gentra Raya
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Simple client-side validation (non-blocking; backend tetap wajib memvalidasi)
    (function(){
      'use strict';
      const form = document.getElementById('login-form');
      form.addEventListener('submit', function(e){
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    })();
  </script>
</body>
</html>
