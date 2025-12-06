<?php
// --- KONEKSI DATABASE ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ptindotar_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- 1. LOGIKA FILTER (TANGGAL & TIPE TRANSAKSI) ---
$tgl_awal   = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir  = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$tipe_filter = isset($_GET['tipe_transaksi']) ? $_GET['tipe_transaksi'] : '';

// --- 2. QUERY RIWAYAT TRANSAKSI (DINAMIS SESUAI FILTER) ---

// Siapkan potongan query untuk 'Masuk'
$query_masuk = "SELECT 
                    bm.tanggal_masuk as tanggal, 
                    b.nama_barang, 
                    'Masuk' as tipe, 
                    bm.jumlah_masuk as jumlah, 
                    bm.status 
                FROM barang_masuk bm
                JOIN barang b ON bm.id_barang = b.id_barang
                WHERE bm.tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'";

// Siapkan potongan query untuk 'Keluar'
$query_keluar = "SELECT 
                    bk.tanggal_keluar as tanggal, 
                    b.nama_barang, 
                    'Keluar' as tipe, 
                    bk.jumlah_keluar as jumlah, 
                    bk.status 
                 FROM barang_keluar bk
                 JOIN barang b ON bk.id_barang = b.id_barang
                 WHERE bk.tanggal_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'";

// Cek Filter apa yang dipilih user
if ($tipe_filter == 'Masuk') {
    // Hanya ambil data masuk
    $sql_transaksi = $query_masuk . " ORDER BY tanggal DESC";
} elseif ($tipe_filter == 'Keluar') {
    // Hanya ambil data keluar
    $sql_transaksi = $query_keluar . " ORDER BY tanggal DESC";
} else {
    // Ambil keduanya (UNION)
    $sql_transaksi = "$query_masuk UNION ALL $query_keluar ORDER BY tanggal DESC";
}

$result_transaksi = $conn->query($sql_transaksi);

// --- 3. QUERY TOTAL STOK & NILAI ASET ---
// (Filter kategori dihapus karena dropdown diganti jenis transaksi)
$sql_stok = "SELECT b.*, k.nama_kategori, (b.stok * b.harga_beli) as nilai_aset 
             FROM barang b 
             LEFT JOIN kategori k ON b.id_kategori = k.id_kategori";
$result_stok = $conn->query($sql_stok);

// --- 4. QUERY VISUALISASI SAFETY STOCK ---
$sql_visual = "SELECT b.nama_barang, b.stok, ar.min_stock, ar.safety_stock 
               FROM barang b
               LEFT JOIN analytic_results ar ON b.id_barang = ar.id_barang
               WHERE ar.min_stock > 0 
               ORDER BY b.stok ASC LIMIT 5";
$result_visualisasi = $conn->query($sql_visual);

// --- 5. RINGKASAN BIAYA PEMBELIAN BULAN INI ---
$sql_biaya = "SELECT SUM(bm.jumlah_masuk * b.harga_beli) as total_biaya 
              FROM barang_masuk bm
              JOIN barang b ON bm.id_barang = b.id_barang
              WHERE MONTH(bm.tanggal_masuk) = MONTH(CURRENT_DATE()) 
              AND YEAR(bm.tanggal_masuk) = YEAR(CURRENT_DATE())
              AND bm.status = 'approved'";
$res_biaya = $conn->query($sql_biaya);
$row_biaya = $res_biaya->fetch_assoc();
$total_biaya = $row_biaya['total_biaya'] ?? 0;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Inventory - Indotar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        #content {
            width: 100%;
            min-height: 100vh;
            padding: 20px;
        }

        .custom-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }

        .table thead th {
            border-top: none;
            background-color: #f8f9fc;
            color: #4e73df;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .progress-label {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .progress {
            height: 10px;
            border-radius: 20px;
        }

        .footer {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="wrapper">
        <div style="min-width: 250px; max-width: 250px;">
            <?php include 'sidebar.php'; ?>
        </div>

        <div id="content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary" title="Toggle sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                    <h2 class="fw-bold text-dark mb-0">Laporan Inventory</h2>
                    <p class="text-muted small">PT. Indotar Gentra Raya</p>
                </div>
                <div class="user-info">
                    <span class="badge bg-primary p-2"><i class="fas fa-user me-2"></i>Admin Logged In</span>
                </div>
            </div>

            <div class="custom-card">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 text-primary"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
                    <form method="GET" action="">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Tanggal Awal</label>
                                <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Tanggal Akhir</label>
                                <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted">Jenis Transaksi</label>
                                <select name="tipe_transaksi" class="form-select">
                                    <option value="">Semua Transaksi</option>
                                    <option value="Masuk" <?= $tipe_filter == 'Masuk' ? 'selected' : '' ?>>Barang Masuk</option>
                                    <option value="Keluar" <?= $tipe_filter == 'Keluar' ? 'selected' : '' ?>>Barang Keluar</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="custom-card">
                <div class="card-header-custom">
                    <i class="fas fa-history me-2"></i> Riwayat Transaksi
                    <?php if ($tipe_filter) echo "($tipe_filter)"; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Barang</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_transaksi && $result_transaksi->num_rows > 0):
                                    while ($row = $result_transaksi->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td class="ps-4"><?= $no++ ?></td>
                                            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                            <td>
                                                <?php if ($row['tipe'] == 'Masuk'): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success"><i class="fas fa-arrow-down me-1"></i>Masuk</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger"><i class="fas fa-arrow-up me-1"></i>Keluar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $row['jumlah'] ?> Unit</td>
                                            <td>
                                                <?php if ($row['status'] == 'approved' || $row['status'] == 'selesai'): ?>
                                                    <span class="badge bg-primary">Approved</span>
                                                <?php elseif ($row['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Reject</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">Tidak ada transaksi ditemukan untuk filter ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="custom-card">
                <div class="card-header-custom" style="background: linear-gradient(45deg, #1cc88a, #13855c);">
                    <i class="fas fa-boxes me-2"></i> Stok Barang Saat Ini
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Total Stok</th>
                                    <th>Nilai Aset</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_stok->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">BRG-<?= str_pad($row['id_barang'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= $row['nama_barang'] ?></td>
                                        <td><?= $row['nama_kategori'] ?></td>
                                        <td class="text-primary fw-bold"><?= $row['stok'] ?> Pcs</td>
                                        <td>Rp <?= number_format($row['nilai_aset'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <div class="custom-card h-100">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 text-dark"><i class="fas fa-chart-pie me-2 text-warning"></i>Visualisasi Stok vs Min. Stok</h5>

                            <?php
                            if ($result_visualisasi->num_rows > 0):
                                while ($v = $result_visualisasi->fetch_assoc()):
                                    // Hitung Persentase Visual
                                    $persen = ($v['stok'] > 0 && $v['min_stock'] > 0) ? ($v['stok'] / ($v['min_stock'] * 2)) * 100 : 0;
                                    if ($persen > 100) $persen = 100;

                                    // Tentukan Warna
                                    $warna = 'bg-danger';
                                    $label_status = 'Critical';
                                    if ($v['stok'] >= $v['min_stock'] * 1.5) {
                                        $warna = 'bg-success';
                                        $label_status = 'Aman';
                                    } elseif ($v['stok'] >= $v['min_stock']) {
                                        $warna = 'bg-warning';
                                        $label_status = 'Warning';
                                    }
                            ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span class="progress-label"><?= $v['nama_barang'] ?> (<?= $label_status ?>)</span>
                                            <span class="small fw-bold text-muted"><?= $v['stok'] ?> / Min: <?= $v['min_stock'] ?></span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar <?= $warna ?>" role="progressbar" style="width: <?= $persen ?>%"></div>
                                        </div>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <p class="text-muted text-center small">Belum ada data analisis stok (min_stock kosong).</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="custom-card h-100 border-start border-4 border-info">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3 text-dark"><i class="fas fa-lightbulb me-2 text-info"></i>Ringkasan & Rekomendasi</h5>

                            <div class="alert alert-light border shadow-sm mb-3">
                                <h6 class="fw-bold text-dark">Total Pembelian (Approved) Bulan Ini</h6>
                                <h3 class="text-primary mb-0">Rp <?= number_format($total_biaya, 0, ',', '.') ?></h3>
                                <small class="text-muted">Hanya menghitung barang masuk yang disetujui</small>
                            </div>

                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle text-info mt-1 me-2"></i>
                                <p class="small text-muted mb-0">
                                    <strong>Info:</strong> Stok barang diurutkan berdasarkan prioritas. Pastikan selalu memantau visualisasi di samping untuk mencegah stock-out.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer text-center text-muted small">
                &copy; 2025 PT. Indotar Gentra Raya Inventory System. All rights reserved.
            </footer>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>