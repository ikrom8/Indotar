<?php
// dashboard.php
// Dashboard ringkasan stok & laporan untuk PT Indotar Gentra Raya
require_once 'db.php';
require_once 'auth_check.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();

// --- 1. DATA RINGKASAN (CARDS) ---

// A. Total Items (Produk)
// Sumber: Tabel 'barang' (Image 5)
$total_items = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM barang");
if ($res && $r = $res->fetch_assoc()) {
    $total_items = intval($r['cnt']);
}

// B. Total Stok Fisik
// Sumber: Tabel 'barang' kolom 'stok' (Image 5)
$total_stock = 0;
$res = $conn->query("SELECT SUM(stok) AS total_stok FROM barang");
if ($res && $r = $res->fetch_assoc()) {
    $total_stock = intval($r['total_stok']);
}

// C. Low Stock (Stok Menipis)
// Logic: Gabungkan tabel 'barang' dengan 'analytic_results' untuk dapat min_stock.
// Jika tidak ada data di analytic_results, gunakan default threshold 10.

$low_threshold = 10; // <--- TAMBAHKAN BARIS INI (Definisikan variabelnya)
$low_threshold_default = $low_threshold; // Opsional: untuk konsistensi query di bawah

$low_stock_count = 0;
$sql_low = "
    SELECT COUNT(*) AS cnt 
    FROM barang b
    LEFT JOIN analytic_results ar ON b.id_barang = ar.id_barang
    WHERE b.stok <= COALESCE(ar.min_stock, $low_threshold)
";
$res = $conn->query($sql_low);
if ($res && $r = $res->fetch_assoc()) {
    $low_stock_count = intval($r['cnt']);
}

// D. Reorder Recommended (ROP)
// Sumber: Tabel 'analytic_results' kolom 'reorder_point' (Image 4)
$items_to_reorder = 0;
$sql_reorder = "
    SELECT COUNT(*) AS cnt
    FROM barang b
    JOIN analytic_results ar ON b.id_barang = ar.id_barang
    WHERE b.stok <= ar.reorder_point
";
$res = $conn->query($sql_reorder);
if ($res && $r = $res->fetch_assoc()) {
    $items_to_reorder = intval($r['cnt']);
}

// --- 2. DATA CHART KATEGORI ---
// Menggunakan id_kategori dari tabel barang (Image 5). 
// Asumsi: Ada tabel 'kategori' dengan PK 'id_kategori' dan kolom 'nama_kategori'.
$cat_labels = [];
$cat_values = [];
$sql_cat = "
    SELECT k.nama_kategori, SUM(b.stok) AS total_stok
    FROM barang b
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
    GROUP BY b.id_kategori, k.nama_kategori
    ORDER BY total_stok DESC
    LIMIT 10
";
$res = $conn->query($sql_cat);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Jika nama kategori NULL (tidak ada join), labeli sebagai 'Lainnya/Uncategorized'
        $cat_labels[] = $row['nama_kategori'] ? $row['nama_kategori'] : 'Umum';
        $cat_values[] = intval($row['total_stok']);
    }
}

// --- 3. DATA CHART TREN 6 BULAN ---
// Logic: Mengambil data barang_masuk (Image 2) dan barang_keluar (Image 3)

// Data Masuk
$mapIn = [];
$res = $conn->query("
    SELECT DATE_FORMAT(tanggal_masuk, '%Y-%m') AS ym, SUM(jumlah_masuk) AS total_in
    FROM barang_masuk
    WHERE tanggal_masuk >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
");
if ($res) {
    while ($r = $res->fetch_assoc()) $mapIn[$r['ym']] = intval($r['total_in']);
}

// Data Keluar
$mapOut = [];
$res = $conn->query("
    SELECT DATE_FORMAT(tanggal_keluar, '%Y-%m') AS ym, SUM(jumlah_keluar) AS total_out
    FROM barang_keluar
    WHERE tanggal_keluar >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
");
if ($res) {
    while ($r = $res->fetch_assoc()) $mapOut[$r['ym']] = intval($r['total_out']);
}

// Zero-filling bulan kosong
$months = [];
$in_vals = [];
$out_vals = [];
$start = (new DateTime('first day of this month'))->modify('-5 months');
$end   = (new DateTime('first day of this month'))->modify('+1 month');
$period = new DatePeriod($start, new DateInterval('P1M'), $end);

foreach ($period as $dt) {
    $k = $dt->format('Y-m');
    $months[] = $k;
    $in_vals[] = $mapIn[$k] ?? 0;
    $out_vals[] = $mapOut[$k] ?? 0;
}

// --- 4. DATA TABEL TERBARU ---
// Union antara barang_masuk dan barang_keluar
// Menggunakan kolom sesuai struktur gambar:
// barang_masuk: id_masuk, tanggal_masuk, jumlah_masuk, received_by (Image 2)
// barang_keluar: id_keluar, tanggal_keluar, jumlah_keluar, requested_by (Image 3)

$recent = [];
$sql_recent = "
    (SELECT 
        'IN' as ttype, 
        bm.id_masuk as idt, 
        b.nama_barang, 
        bm.jumlah_masuk as qty, 
        bm.tanggal_masuk as dt, 
        bm.received_by as actor
    FROM barang_masuk bm 
    JOIN barang b ON bm.id_barang = b.id_barang)
    
    UNION ALL
    
    (SELECT 
        'OUT' as ttype, 
        bk.id_keluar as idt, 
        b.nama_barang, 
        bk.jumlah_keluar as qty, 
        bk.tanggal_keluar as dt, 
        bk.requested_by as actor
    FROM barang_keluar bk 
    JOIN barang b ON bk.id_barang = b.id_barang)
    
    ORDER BY dt DESC
    LIMIT 10
";

$res = $conn->query($sql_recent);
if ($res) {
    while ($r = $res->fetch_assoc()) $recent[] = $r;
}

// Encode JSON untuk Frontend JS
$cat_labels_js = json_encode($cat_labels);
$cat_values_js = json_encode($cat_values);
$months_display = array_map(function ($m) {
    return date('M Y', strtotime($m . '-01'));
}, $months);
$months_js = json_encode($months_display);
$in_vals_js = json_encode($in_vals);
$out_vals_js = json_encode($out_vals);

// Ambil info user (Opsional, untuk display nama)
// Sumber: Tabel 'user' kolom 'full_name' (Image 1)
$current_user_name = "User";
if (isset($_SESSION['user_id'])) {
    // Sesuaikan id_user dari session login Anda
    $uid = $_SESSION['user_id'];
    $u_res = $conn->query("SELECT full_name FROM user WHERE id_user = '$uid'");
    if ($u_res && $u_row = $u_res->fetch_assoc()) {
        $current_user_name = $u_row['full_name'];
    }
} elseif (isset($_SESSION['username'])) {
    $current_user_name = $_SESSION['username'];
}

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Inventori</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="d-flex">
        <!-- SIDEBAR -->
        <?php require 'sidebar.php'; ?>

        <!-- MAIN AREA -->
        <div id="mainarea" class="flex-fill">
            <!-- TOPBAR -->
            <header class="bg-white border-bottom py-2">
                <div class="container-fluid d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary" title="Toggle sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                        <h5 class="mb-0">Dashboard</h5>
                        <small class="text-muted ms-2">Ringkasan Laporan Persediaan</small>
                    </div>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="container-fluid py-3">
                <!-- METRICS -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card card-ghost p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric"><?php echo number_format($total_items); ?></div>
                                    <div class="metric-sub">Total Produk</div>
                                </div>
                                <div class="text-end">
                                    <i class="fa fa-box fa-2x text-primary"></i>
                                </div>
                            </div>
                            <div class="mt-2 small text-muted">Semua produk terdaftar</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card card-ghost p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric"><?php echo number_format($total_stock); ?></div>
                                    <div class="metric-sub">Total Stok Akhir</div>
                                </div>
                                <div class="text-end">
                                    <i class="fa fa-layer-group fa-2x text-success"></i>
                                </div>
                            </div>
                            <div class="mt-2 small text-muted">Jumlah unit tersedia (computed)</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card card-ghost p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric text-warning"><?php echo number_format($low_stock_count); ?></div>
                                    <div class="metric-sub">Produk Kurang Stok (≤ <?php echo $low_threshold; ?>)</div>
                                </div>
                                <div class="text-end">
                                    <i class="fa fa-exclamation-triangle fa-2x text-warning"></i>
                                </div>
                            </div>
                            <div class="mt-2 small text-muted">Perlu perhatian / reorder</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card card-ghost p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric text-danger"><?php echo number_format($items_to_reorder); ?></div>
                                    <div class="metric-sub">Reorder Recommended</div>
                                </div>
                                <div class="text-end">
                                    <i class="fa fa-redo-alt fa-2x text-danger"></i>
                                </div>
                            </div>
                            <div class="mt-2 small text-muted">Produk di bawah ROP (analitik)</div>
                        </div>
                    </div>
                </div>

                <!-- CHARTS & TABLE -->
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card card-ghost p-3 h-100" style="position: relative; height: 300px; width: 100%;">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-0">Stok per Kategori</h6>
                                <small class="text-muted">Top 10 kategori</small>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="chartCategory"></canvas>
                            </div>
                            <div class="mt-2 small text-muted">Visualisasi distribusi stok berdasarkan kategori — gunakan ini untuk menentukan fokus replenishment.</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card card-ghost p-3 h-100">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-0">Tren Masuk / Keluar (6 bulan)</h6>
                                <small class="text-muted">Unit</small>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="chartTrend"></canvas>
                            </div>
                            <div class="mt-2 small text-muted">Perbandingan barang masuk dan keluar bulanan — membantu identifikasi siklus permintaan.</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card card-ghost p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Transaksi Terakhir</h6>
                                <div>
                                    <a href="reports.php" class="btn btn-sm btn-outline-primary"><i class="fa fa-file-alt"></i> Lihat Laporan</a>
                                    <button id="btnExportCSV" class="btn btn-sm btn-outline-success"><i class="fa fa-download"></i> Export CSV</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Tipe</th>
                                            <th>Barang</th>
                                            <th>Qty</th>
                                            <th>Oleh</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentTableBody">
                                        <?php if (count($recent) === 0): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Belum ada transaksi</td>
                                            </tr>
                                            <?php else: foreach ($recent as $i => $t): ?>
                                                <tr>
                                                    <td><?php echo $i + 1; ?></td>
                                                    <td><?php echo $t['ttype'] === 'IN' ? '<span class="badge bg-success">Masuk</span>' : '<span class="badge bg-danger">Keluar</span>'; ?></td>
                                                    <td><?php echo htmlspecialchars($t['nama_barang']); ?></td>
                                                    <td><?php echo number_format($t['qty']); ?></td>
                                                    <td><?php echo htmlspecialchars($t['actor']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($t['dt'])); ?></td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <footer class="py-3 text-muted small text-center border-top">
                © PT Indotar Gentra Raya — Sistem Informasi Inventori
            </footer>
        </div>
    </div>

    <script>
        // Chart: Category (donut)
        const catLabels = <?php echo $cat_labels_js ?: '[]'; ?>;
        const catValues = <?php echo $cat_values_js ?: '[]'; ?>;
        const ctxCat = document.getElementById('chartCategory');
        if (ctxCat && catLabels.length) {
            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catValues,
                        backgroundColor: [
                            '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#fd7e14', '#198754', '#20c997', '#0dcaf0', '#0dc3a7', '#ffc107'
                        ],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    maintainAspectRatio: false
                }
            });
        } else if (ctxCat) {
            ctxCat.parentNode.innerHTML = '<div class="p-4 text-center text-muted">Tidak ada data kategori untuk ditampilkan.</div>';
        }

        // Chart: Trend In / Out
        const months = <?php echo $months_js ?: '[]'; ?>;
        const inVals = <?php echo $in_vals_js ?: '[]'; ?>;
        const outVals = <?php echo $out_vals_js ?: '[]'; ?>;
        const ctxTrend = document.getElementById('chartTrend');
        if (ctxTrend && months.length) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Masuk',
                        data: inVals,
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: 'rgba(13,110,253,0.08)',
                        borderColor: 'rgba(13,110,253,1)'
                    }, {
                        label: 'Keluar',
                        data: outVals,
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: 'rgba(220,53,69,0.06)',
                        borderColor: 'rgba(220,53,69,1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else if (ctxTrend) {
            ctxTrend.parentNode.innerHTML = '<div class="p-4 text-center text-muted">Tidak ada data tren untuk ditampilkan.</div>';
        }

        // Export CSV button (client-side visible table)
        document.getElementById('btnExportCSV').addEventListener('click', function() {
            const rows = Array.from(document.querySelectorAll('#recentTableBody tr')).filter(r => r.style.display !== 'none');
            if (!rows.length) return alert('Tidak ada data untuk diexport');
            let csv = 'No,Tipe,Barang,Qty,Oleh,Tanggal\n';
            rows.forEach(r => {
                const cols = Array.from(r.querySelectorAll('td')).map(td => td.innerText.trim().replace(/\n/g, ' '));
                if (cols.length) csv += cols.join(',') + '\n';
            });
            const blob = new Blob([csv], {
                type: 'text/csv'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'recent_transaksi.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        });
    </script>

    <!-- Bootstrap bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>