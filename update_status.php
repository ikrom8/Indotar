<?php
// update_status.php (improved)
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db.php'; // pastikan $conn (mysqli) tersedia

// Only POST (JSON) accepted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

// Auth: only admin allowed to change status
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    error_log("update_status: access denied - role=" . ($_SESSION['role'] ?? 'null') . " IP=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    echo json_encode(['success'=>false,'message'=>'Akses ditolak. Hanya admin.']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id_transaksi']) ? intval($input['id_transaksi']) : 0;
$jenis = isset($input['jenis']) ? trim($input['jenis']) : '';
$newStatus = isset($input['status']) ? trim($input['status']) : '';

$allowed = ['pending','approved','reject'];
if ($id <= 0 || !in_array($jenis, ['masuk','keluar'], true) || !in_array($newStatus, $allowed, true)) {
    http_response_code(400);
    error_log("update_status: invalid params - " . json_encode($input));
    echo json_encode(['success'=>false,'message'=>'Parameter tidak valid']);
    exit;
}

// Map table/cols
if ($jenis === 'masuk') {
    $table = 'barang_masuk'; $pk = 'id_masuk'; $col_qty = 'jumlah_masuk';
} else {
    $table = 'barang_keluar'; $pk = 'id_keluar'; $col_qty = 'jumlah_keluar';
}

mysqli_begin_transaction($conn);

try {
    // 1) Lock and fetch transaksi
    $sql = "SELECT {$col_qty}, id_barang, status FROM {$table} WHERE {$pk} = ? FOR UPDATE";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception('Prepare failed (select transaksi): '.mysqli_error($conn));
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) throw new Exception('Execute failed (select transaksi): '.mysqli_stmt_error($stmt));
    mysqli_stmt_bind_result($stmt, $qty, $id_barang, $curStatus);
    if (!mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('Transaksi tidak ditemukan.');
    }
    mysqli_stmt_close($stmt);

    // No-op if same status
    if ($curStatus === $newStatus) {
        mysqli_commit($conn);
        echo json_encode(['success'=>true,'message'=>'Tidak ada perubahan status','status'=>$curStatus]);
        exit;
    }

    // 2) Update stok logic
    if ($newStatus === 'approved') {
        if ($jenis === 'keluar') {
            $sstmt = mysqli_prepare($conn, "SELECT stok FROM barang WHERE id_barang = ? FOR UPDATE");
            if (!$sstmt) throw new Exception('Prepare failed (select barang stok): '.mysqli_error($conn));
            mysqli_stmt_bind_param($sstmt, "i", $id_barang);
            if (!mysqli_stmt_execute($sstmt)) throw new Exception('Execute failed (select barang stok): '.mysqli_stmt_error($sstmt));
            mysqli_stmt_bind_result($sstmt, $stok_now);
            if (!mysqli_stmt_fetch($sstmt)) { mysqli_stmt_close($sstmt); throw new Exception('Barang tidak ditemukan.'); }
            mysqli_stmt_close($sstmt);

            if ($stok_now < $qty) throw new Exception("Stok tidak cukup. Tersisa {$stok_now}.");

            $ustmt = mysqli_prepare($conn, "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
            if (!$ustmt) throw new Exception('Prepare failed (update stok): '.mysqli_error($conn));
            mysqli_stmt_bind_param($ustmt, "ii", $qty, $id_barang);
            if (!mysqli_stmt_execute($ustmt)) throw new Exception('Execute failed (update stok): '.mysqli_stmt_error($ustmt));
            mysqli_stmt_close($ustmt);

        } else { // masuk -> add stok
            $ustmt = mysqli_prepare($conn, "UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
            if (!$ustmt) throw new Exception('Prepare failed (update stok masuk): '.mysqli_error($conn));
            mysqli_stmt_bind_param($ustmt, "ii", $qty, $id_barang);
            if (!mysqli_stmt_execute($ustmt)) throw new Exception('Execute failed (update stok masuk): '.mysqli_stmt_error($ustmt));
            mysqli_stmt_close($ustmt);
        }
    }

    // Revert if previously approved and now set non-approved
    if ($curStatus === 'approved' && in_array($newStatus, ['pending','reject'], true)) {
        if ($jenis === 'keluar') {
            $ustmt = mysqli_prepare($conn, "UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
            if (!$ustmt) throw new Exception('Prepare failed (revert stok keluar): '.mysqli_error($conn));
            mysqli_stmt_bind_param($ustmt, "ii", $qty, $id_barang);
            if (!mysqli_stmt_execute($ustmt)) throw new Exception('Execute failed (revert stok keluar): '.mysqli_stmt_error($ustmt));
            mysqli_stmt_close($ustmt);
        } else {
            // check stok sufficient to subtract
            $sstmt = mysqli_prepare($conn, "SELECT stok FROM barang WHERE id_barang = ? FOR UPDATE");
            if (!$sstmt) throw new Exception('Prepare failed (select barang stok revert): '.mysqli_error($conn));
            mysqli_stmt_bind_param($sstmt, "i", $id_barang);
            if (!mysqli_stmt_execute($sstmt)) throw new Exception('Execute failed (select barang stok revert): '.mysqli_stmt_error($sstmt));
            mysqli_stmt_bind_result($sstmt, $stok_now2);
            if (!mysqli_stmt_fetch($sstmt)) { mysqli_stmt_close($sstmt); throw new Exception('Barang tidak ditemukan.'); }
            mysqli_stmt_close($sstmt);

            if ($stok_now2 < $qty) {
                throw new Exception("Tidak bisa revert: stok saat ini ({$stok_now2}) kurang dari jumlah yang harus dikurangi ({$qty}).");
            }

            $ustmt = mysqli_prepare($conn, "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
            if (!$ustmt) throw new Exception('Prepare failed (revert stok masuk): '.mysqli_error($conn));
            mysqli_stmt_bind_param($ustmt, "ii", $qty, $id_barang);
            if (!mysqli_stmt_execute($ustmt)) throw new Exception('Execute failed (revert stok masuk): '.mysqli_stmt_error($ustmt));
            mysqli_stmt_close($ustmt);
        }
    }

    // 3) Update status column
    $upd = mysqli_prepare($conn, "UPDATE {$table} SET status = ? WHERE {$pk} = ?");
    if (!$upd) throw new Exception('Prepare failed (update status): '.mysqli_error($conn));
    mysqli_stmt_bind_param($upd, "si", $newStatus, $id);
    if (!mysqli_stmt_execute($upd)) throw new Exception('Execute failed (update status): '.mysqli_stmt_error($upd));
    mysqli_stmt_close($upd);


    // fetch current stock after updates to return to caller
    $stock_after = null;
    $s2 = mysqli_prepare($conn, "SELECT stok FROM barang WHERE id_barang = ? LIMIT 1");
    if ($s2) {
        mysqli_stmt_bind_param($s2, "i", $id_barang);
        mysqli_stmt_execute($s2);
        mysqli_stmt_bind_result($s2, $stock_after);
        mysqli_stmt_fetch($s2);
        mysqli_stmt_close($s2);
    }

    mysqli_commit($conn);
    echo json_encode(['success'=>true,'message'=>'Status diubah dan stok terupdate','status'=>$newStatus,'stok'=>$stock_after]);
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $msg = $e->getMessage();
    error_log("update_status error: " . $msg . " | input=" . json_encode($input) . " | IP=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}
