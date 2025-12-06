<?php
// proses_tambah_transaksi.php (final: konsisten dengan tabel 'user')
session_start();
include 'db.php'; // $conn harus didefinisikan (mysqli)

// Pastikan login — jika sistem boleh mengizinkan pengajuan tanpa login, sesuaikan kebijakan
if (!isset($_SESSION['id_user'])) {
    // Jika kamu ingin mengizinkan pengajuan tanpa login, ubah kebijakan ini.
    http_response_code(403);
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Ambil id_user dari session (kemudian kita verifikasi keberadaannya di tabel 'user')
$session_user_id = isset($_SESSION['id_user']) ? intval($_SESSION['id_user']) : 0;

// Validasi bahwa id_user ada di tabel 'user'; jika tidak, fallback ke NULL
$id_user_petugas = null;
if ($session_user_id > 0) {
    $check_sql = "SELECT id_user FROM `user` WHERE id_user = ? LIMIT 1";
    $stmtc = @mysqli_prepare($conn, $check_sql);
    if ($stmtc) {
        mysqli_stmt_bind_param($stmtc, "i", $session_user_id);
        mysqli_stmt_execute($stmtc);
        mysqli_stmt_store_result($stmtc);
        if (mysqli_stmt_num_rows($stmtc) > 0) {
            $id_user_petugas = $session_user_id;
        } else {
            // tidak ditemukan -> log dan fallback ke NULL
            error_log("Warning: session id_user={$session_user_id} tidak ditemukan di tabel `user`. Insert akan memakai NULL. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $id_user_petugas = null;
        }
        mysqli_stmt_close($stmtc);
    } else {
        // jika prepare gagal (mis. koneksi), set null juga
        $id_user_petugas = null;
    }
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: transaksi.php");
    exit;
}

// Optional: CSRF check jika digunakan
if (!empty($_SESSION['csrf_token'])) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token tidak valid.");
    }
}

// Ambil dan sanitasi input
$tanggal_raw     = $_POST['tanggal'] ?? '';
$id_barang       = isset($_POST['id_barang']) ? intval($_POST['id_barang']) : 0;
$jenis_transaksi = $_POST['jenis_transaksi'] ?? '';
$jumlah          = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : 0;
$asal            = $_POST['asal'] ?? null; // untuk barang_masuk (string)
$id_tujuan       = isset($_POST['id_tujuan']) && $_POST['id_tujuan'] !== '' ? intval($_POST['id_tujuan']) : null; // untuk barang_keluar (int)
$status_input    = $_POST['status'] ?? 'pending';

// Sanitasi & validasi status enum (hanya terima 3 nilai)
$allowed_status = ['pending', 'approved', 'reject'];
$status = in_array($status_input, $allowed_status, true) ? $status_input : 'pending';

// Proteksi role: hanya admin boleh menentukan approved/reject
// Pastikan session menyimpan role (mis. 'admin', 'staff', dsb). Jika tidak ada, anggap non-admin.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $status = 'pending';
}

// Validasi input dasar
$errors = [];
if ($id_barang <= 0) $errors[] = "Pilih barang yang valid.";
if ($jumlah <= 0) $errors[] = "Jumlah harus lebih dari 0.";

// Validasi tanggal (YYYY-MM-DD)
$d = DateTime::createFromFormat('Y-m-d', $tanggal_raw);
if (!$d || $d->format('Y-m-d') !== $tanggal_raw) {
    $errors[] = "Format tanggal tidak valid (YYYY-MM-DD).";
}
$tanggal = $d ? $d->format('Y-m-d') : null;

if (!in_array($jenis_transaksi, ['masuk', 'keluar'], true)) {
    $errors[] = "Jenis transaksi tidak valid.";
}
if ($jenis_transaksi === 'keluar' && empty($id_tujuan)) {
    $errors[] = "Tujuan wajib diisi untuk transaksi keluar.";
}

if (!empty($errors)) {
    $msg = implode("\\n", $errors);
    echo "<script>alert('{$msg}'); window.history.back();</script>";
    exit;
}

// Mulai transaksi DB
mysqli_begin_transaction($conn);

try {
    // Jika keluar, cek stok
    if ($jenis_transaksi === 'keluar') {
        $stok = null;
        // Coba cek di view/tabel persediaan
        $q1 = "SELECT stok FROM persediaan WHERE id_barang = ? LIMIT 1";
        $stmt = @mysqli_prepare($conn, $q1);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_barang);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $stok_res);
            if (mysqli_stmt_fetch($stmt)) $stok = intval($stok_res);
            mysqli_stmt_close($stmt);
        }
        // fallback ke table barang
        if ($stok === null) {
            $q2 = "SELECT stok FROM barang WHERE id_barang = ? LIMIT 1";
            $stmt2 = @mysqli_prepare($conn, $q2);
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "i", $id_barang);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_bind_result($stmt2, $stok_res2);
                if (mysqli_stmt_fetch($stmt2)) $stok = intval($stok_res2);
                mysqli_stmt_close($stmt2);
            }
        }
        if ($stok === null) $stok = 0;
        if ($stok < $jumlah) {
            mysqli_rollback($conn);
            echo "<script>alert('Stok tidak cukup. Stok saat ini: {$stok}'); window.history.back();</script>";
            exit;
        }
    }

    // Insert sesuai jenis transaksi — menyertakan kolom status
    if ($jenis_transaksi === 'masuk') {
        // Struktur kolom di barang_masuk: tanggal_masuk, jumlah_masuk, id_barang, id_user, asal, status, verified_by, verified_at
        $sql = "INSERT INTO barang_masuk (tanggal_masuk, jumlah_masuk, id_barang, id_user, asal, status, verified_by, verified_at)
                VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) throw new Exception("Prepare gagal: " . mysqli_error($conn));
        // bind: s = tanggal, i = jumlah, i = id_barang, i/null = id_user, s = asal, s = status
        // Jika $id_user_petugas null, perlu handling: bind_param tidak menerima null tipe langsung; kita gunakan conditional binding.
        if ($id_user_petugas === null) {
            // bind sebagai NULL: gunakan 'siiiss' dan pass null as null (mysqli will send empty string) -> safer approach: use INTEGER with null via mysqli_stmt_send_long_data not trivial.
            // Simpler: gunakan query dengan placeholder dan NULL literal ketika PHP null:
            mysqli_stmt_close($stmt);
            $sql2 = "INSERT INTO barang_masuk (tanggal_masuk, jumlah_masuk, id_barang, id_user, asal, status, verified_by, verified_at)
                     VALUES (?, ?, ?, NULL, ?, ?, NULL, NULL)";
            $stmt2 = mysqli_prepare($conn, $sql2);
            if (!$stmt2) throw new Exception("Prepare gagal: " . mysqli_error($conn));
            mysqli_stmt_bind_param($stmt2, "sii ss", $tanggal, $jumlah, $id_barang, $asal, $status);
            // Note: typo-safe bind signature: "siiss" (tanggal s, jumlah i, id_barang i, asal s, status s)
            mysqli_stmt_close($stmt2); // we'll re-create correctly below
            // Recreate properly:
            $stmt2 = mysqli_prepare($conn, $sql2);
            if (!$stmt2) throw new Exception("Prepare gagal: " . mysqli_error($conn));
            mysqli_stmt_bind_param($stmt2, "siiss", $tanggal, $jumlah, $id_barang, $asal, $status);
            if (!mysqli_stmt_execute($stmt2)) throw new Exception("Eksekusi gagal: " . mysqli_stmt_error($stmt2));
            mysqli_stmt_close($stmt2);
        } else {
            // id_user available
            mysqli_stmt_bind_param($stmt, "siiiss", $tanggal, $jumlah, $id_barang, $id_user_petugas, $asal, $status);
            if (!mysqli_stmt_execute($stmt)) throw new Exception("Eksekusi gagal: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
        }

    } else { // keluaran
        $sql = "INSERT INTO barang_keluar (tanggal_keluar, jumlah_keluar, id_barang, id_user, id_tujuan, status, verified_by, verified_at)
                VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) throw new Exception("Prepare gagal: " . mysqli_error($conn));
        if ($id_user_petugas === null) {
            // insert with id_user = NULL
            mysqli_stmt_close($stmt);
            $sql2 = "INSERT INTO barang_keluar (tanggal_keluar, jumlah_keluar, id_barang, id_user, id_tujuan, status, verified_by, verified_at)
                     VALUES (?, ?, ?, NULL, ?, ?, NULL, NULL)";
            $stmt2 = mysqli_prepare($conn, $sql2);
            if (!$stmt2) throw new Exception("Prepare gagal: " . mysqli_error($conn));
            mysqli_stmt_bind_param($stmt2, "siiis", $tanggal, $jumlah, $id_barang, $id_tujuan, $status);
            if (!mysqli_stmt_execute($stmt2)) throw new Exception("Eksekusi gagal: " . mysqli_stmt_error($stmt2));
            mysqli_stmt_close($stmt2);
        } else {
            mysqli_stmt_bind_param($stmt, "siiiss", $tanggal, $jumlah, $id_barang, $id_user_petugas, $id_tujuan, $status);
            if (!mysqli_stmt_execute($stmt)) throw new Exception("Eksekusi gagal: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
        }

        // (Opsional) update stok jika kamu menyimpan stok fisik
        $update_sql = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
        $stupd = @mysqli_prepare($conn, $update_sql);
        if ($stupd) {
            mysqli_stmt_bind_param($stupd, "ii", $jumlah, $id_barang);
            mysqli_stmt_execute($stupd);
            mysqli_stmt_close($stupd);
        }
    }

    // Commit
    mysqli_commit($conn);
    header("Location: transaksi.php?status=success");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $err = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "<script>alert('Terjadi kesalahan: {$err}'); window.history.back();</script>";
    exit;
}
