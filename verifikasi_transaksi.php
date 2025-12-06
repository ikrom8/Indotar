<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login untuk mendapatkan ID User (Petugas)
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Sesi habis, silakan login kembali.'); window.location='login.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil Data Umum
    $jenis_transaksi = $_POST['jenis_transaksi']; // 'masuk' atau 'keluar'
    $id_barang       = $_POST['id_barang'];
    $tanggal         = $_POST['tanggal'];
    $jumlah          = $_POST['jumlah'];
    $id_petugas      = $_SESSION['id_user']; // Ambil ID dari sesi login

    // Validasi data dasar
    if (empty($id_barang) || empty($jumlah) || empty($tanggal)) {
        echo "<script>alert('Mohon lengkapi data!'); window.history.back();</script>";
        exit;
    }

    if ($jenis_transaksi == "masuk") {
        // --- LOGIKA BARANG MASUK ---
        $asal      = $_POST['asal'];
        $kondisi   = $_POST['kondisi'];

        // Sesuai ERD: tabel barang_masuk
        // verified_by & verified_at dibiarkan NULL (menandakan status Pending)
        $query = "INSERT INTO barang_masuk 
                  (id_barang, tanggal_masuk, jumlah_masuk, asal, `condition`, received_by) 
                  VALUES 
                  ('$id_barang', '$tanggal', '$jumlah', '$asal', '$kondisi', '$id_petugas')";
        
    } else if ($jenis_transaksi == "keluar") {
        // --- LOGIKA BARANG KELUAR ---
        $tujuan    = $_POST['tujuan'];

        // Cek Stok Terlebih Dahulu (Opsional tapi disarankan)
        // Meskipun ini ajuan, sebaiknya jangan mengajukan barang keluar jika stok 0
        $cek_stok = mysqli_query($koneksi, "SELECT stok FROM barang WHERE id_barang = '$id_barang'");
        $data_stok = mysqli_fetch_assoc($cek_stok);
        
        if ($data_stok['stok'] < $jumlah) {
            echo "<script>alert('Stok barang tidak mencukupi untuk pengajuan ini!'); window.history.back();</script>";
            exit;
        }

        // Sesuai ERD: tabel barang_keluar
        // verified_by & verified_at dibiarkan NULL (menandakan status Pending)
        $query = "INSERT INTO barang_keluar 
                  (id_barang, tanggal_keluar, jumlah_keluar, tujuan, requested_by) 
                  VALUES 
                  ('$id_barang', '$tanggal', '$jumlah', '$tujuan', '$id_petugas')";
    }

    // Eksekusi Query
    if (mysqli_query($koneksi, $query)) {
        echo "<script>
                alert('Ajuan transaksi berhasil disimpan! Menunggu verifikasi Admin.');
                window.location = 'transaksi.php';
              </script>";
    } else {
        echo "Error: " . $query . "<br>" . mysqli_error($koneksi);
    }

} else {
    // Jika akses langsung tanpa POST
    header("Location: transaksi.php");
}
?>