<?php
// analitik.php
session_start();
include 'db.php';

// --- 1. AMBIL DAFTAR BARANG UNTUK DROPDOWN ---
$barang_list = [];
$res = mysqli_query($conn, "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $barang_list[] = $row;
}

$selected_barang = '';
$historis = [];
$analisa = null;
$message = null; // Variabel untuk menampung pesan notifikasi

// --- 2. LOGIKA HITUNG & SIMPAN (HANYA JALAN JIKA POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barang'])) {
    $selected_barang = intval($_POST['barang']);

    // A. Ambil Parameter & Harga Beli
    $query_param = "SELECT p.*, b.harga_beli 
                    FROM parameter_produk p 
                    JOIN barang b ON p.id_barang = b.id_barang 
                    WHERE p.id_barang = ?";
    $stmt = mysqli_prepare($conn, $query_param);

    if (!$stmt) {
        die("Query Error (Param): " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'i', $selected_barang);
    mysqli_stmt_execute($stmt);
    $res_param = mysqli_stmt_get_result($stmt);

    if ($res_param && mysqli_num_rows($res_param) > 0) {
        $parameter = mysqli_fetch_assoc($res_param);

        // --- B. PROSES HITUNG RUMUS ---
        $sl_raw = floatval($parameter['service_level']);
        $service_level = ($sl_raw > 1) ? $sl_raw / 100 : $sl_raw;
        if ($service_level >= 1) $service_level = 0.9999;

        $std_dev = floatval($parameter['standar_deviasi']);
        $lead_time = floatval($parameter['lead_time']);
        $permintaan_harian = floatval($parameter['permintaan_harian']);
        $biaya_pemesanan = floatval($parameter['biaya_pemesanan']);
        $jml_hari_aktif = floatval($parameter['jml_hari_aktif']);
        $harga_barang = ($parameter['harga_beli'] > 0) ? floatval($parameter['harga_beli']) : 10000;

        // 1. Safety Stock
        $Z_score = normsinv($service_level);
        $SS = $Z_score * $std_dev * sqrt($lead_time);

        // 2. ROP
        $ROP = ($permintaan_harian * $lead_time) + $SS;

        // 3. EOQ
        $holding_cost = 0.01 * $harga_barang;
        $D_annual = $permintaan_harian * $jml_hari_aktif;
        $EOQ = ($holding_cost > 0) ? sqrt((2 * $D_annual * $biaya_pemesanan) / $holding_cost) : 0;

        // 4. Min, Max, TC
        $min_stock = $ROP;
        $max_stock = $min_stock + $EOQ;
        $TC = ($EOQ > 0) ? ($D_annual / $EOQ) * $biaya_pemesanan + ($EOQ / 2) * $holding_cost : 0;

        // --- C. CEK DUPLIKASI SEBELUM SIMPAN ---

        // Persiapan Data Baru (Integer)
        $db_barang = (int) $selected_barang;
        $db_min    = (int) ceil($min_stock);
        $db_max    = (int) round($max_stock);
        $db_ss     = (int) ceil($SS);
        $db_rop    = (int) ceil($ROP);
        $db_eoq    = (int) round($EOQ);
        $db_tc     = (int) $TC;
        $db_user   = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : NULL;

        // Cek data TERAKHIR di database untuk barang ini
        $q_check = "SELECT min_stock, max_stock, safety_stock, reorder_point, eoq, total_biaya_ops 
                    FROM analytic_results 
                    WHERE id_barang = ? 
                    ORDER BY computed_at DESC LIMIT 1";

        $stmt_check = mysqli_prepare($conn, $q_check);
        mysqli_stmt_bind_param($stmt_check, 'i', $db_barang);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        $last_data = mysqli_fetch_assoc($res_check);

        // Flag untuk menentukan apakah perlu simpan
        $perlu_simpan = true;

        if ($last_data) {
            // Bandingkan data baru dengan data lama
            if (
                $last_data['min_stock'] == $db_min &&
                $last_data['max_stock'] == $db_max &&
                $last_data['safety_stock'] == $db_ss &&
                $last_data['reorder_point'] == $db_rop &&
                $last_data['eoq'] == $db_eoq &&
                $last_data['total_biaya_ops'] == $db_tc
            ) {
                // Jika SEMUA sama persis, jangan simpan lagi
                $perlu_simpan = false;
            }
        }

        if ($perlu_simpan) {
            // Query Insert
            $q_insert = "INSERT INTO analytic_results 
                        (id_barang, min_stock, max_stock, safety_stock, reorder_point, eoq, total_biaya_ops, computed_by, computed_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt_ins = mysqli_prepare($conn, $q_insert);
            if (!$stmt_ins) {
                die("Gagal Prepare: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt_ins,
                'iiiiiiii',
                $db_barang,
                $db_min,
                $db_max,
                $db_ss,
                $db_rop,
                $db_eoq,
                $db_tc,
                $db_user
            );

            if (mysqli_stmt_execute($stmt_ins)) {
                $message = [
                    'type' => 'success',
                    'text' => 'Analisa berhasil dihitung dan disimpan ke database!'
                ];
            } else {
                $message = [
                    'type' => 'danger',
                    'text' => 'Gagal menyimpan data: ' . mysqli_stmt_error($stmt_ins)
                ];
            }
            mysqli_stmt_close($stmt_ins);
        } else {
            // Jika data duplikat
            $message = [
                'type' => 'info',
                'text' => 'Analisa berhasil dihitung. Data <b>tidak disimpan baru</b> karena hasil perhitungan identik dengan data terakhir.'
            ];
        }
    }
}

// --- 3. LOGIKA AMBIL DATA (SELECT) UNTUK DITAMPILKAN ---
if (!empty($selected_barang)) {
    // A. Ambil Hasil Analisa Terbaru
    $q_get = "SELECT * FROM analytic_results WHERE id_barang = ? ORDER BY computed_at DESC LIMIT 1";
    $stmt_get = mysqli_prepare($conn, $q_get);
    mysqli_stmt_bind_param($stmt_get, 'i', $selected_barang);
    mysqli_stmt_execute($stmt_get);
    $res_get = mysqli_stmt_get_result($stmt_get);

    if ($res_get && mysqli_num_rows($res_get) > 0) {
        $row_analisa = mysqli_fetch_assoc($res_get);
        $analisa = [
            'SS'  => $row_analisa['safety_stock'],
            'ROP' => $row_analisa['reorder_point'],
            'EOQ' => $row_analisa['eoq'],
            'Min' => $row_analisa['min_stock'],
            'Max' => $row_analisa['max_stock'],
            'TC'  => $row_analisa['total_biaya_ops'],
            'Z_score' => 'Auto',
            'HoldingCost' => 'Auto'
        ];
    }

    // B. Ambil Historis Transaksi
    $query_hist = "SELECT transaksi.tgl, b.stok AS stok_saat_ini, COALESCE(pp.permintaan_harian, 0) as permintaan_harian, transaksi.masuk, transaksi.keluar
          FROM (
              SELECT tanggal_masuk AS tgl, jumlah_masuk AS masuk, 0 AS keluar FROM barang_masuk WHERE id_barang = ?
              UNION ALL
              SELECT tanggal_keluar AS tgl, 0 AS masuk, jumlah_keluar AS keluar FROM barang_keluar WHERE id_barang = ?
          ) AS transaksi
          JOIN barang b ON b.id_barang = ? LEFT JOIN parameter_produk pp ON pp.id_barang = ?
          ORDER BY transaksi.tgl ASC";

    $stmt_hist = mysqli_prepare($conn, $query_hist);
    mysqli_stmt_bind_param($stmt_hist, 'iiii', $selected_barang, $selected_barang, $selected_barang, $selected_barang);
    mysqli_stmt_execute($stmt_hist);
    $res2 = mysqli_stmt_get_result($stmt_hist);

    $data_transaksi = [];
    while ($row = mysqli_fetch_assoc($res2)) {
        $data_transaksi[] = $row;
    }

    $total_masuk_all = array_sum(array_column($data_transaksi, 'masuk'));
    $total_keluar_all = array_sum(array_column($data_transaksi, 'keluar'));
    $stok_real_db = count($data_transaksi) > 0 ? $data_transaksi[0]['stok_saat_ini'] : 0;
    $stok_berjalan = $stok_real_db - $total_masuk_all + $total_keluar_all;

    foreach ($data_transaksi as $row) {
        $masuk = $row['masuk'];
        $keluar = $row['keluar'];
        $stok_awal_row = $stok_berjalan;
        $stok_akhir_row = $stok_awal_row + $masuk - $keluar;
        $stok_berjalan = $stok_akhir_row;

        $rop_val = isset($analisa) ? $analisa['ROP'] : 0;
        $eoq_val = isset($analisa) ? $analisa['EOQ'] : 0;
        $ss_val  = isset($analisa) ? $analisa['SS'] : 0;

        $historis[] = [
            'tgl' => $row['tgl'],
            'permintaan' => $row['permintaan_harian'],
            'stok_awal' => $stok_awal_row,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'stok_akhir' => $stok_akhir_row,
            'ss'  => $ss_val,
            'rop' => $rop_val,
            'eoq' => $eoq_val
        ];
    }
}

// Fungsi Helper Z-Value
function normsinv($p)
{
    $a1 = -39.6968302866538;
    $a2 = 220.946098424521;
    $a3 = -275.928510446969;
    $a4 = 138.357751867269;
    $a5 = -30.6647980661472;
    $a6 = 2.50662827745924;
    $b1 = -54.4760987982241;
    $b2 = 161.585836858041;
    $b3 = -155.698979859887;
    $b4 = 66.8013118877197;
    $b5 = -13.2806815528857;
    if ($p >= 1) $p = 0.999999999;
    if ($p <= 0) $p = 0.000000001;
    $q = ($p < 0.5) ? $p : (1 - $p);
    $t = sqrt(-2 * log($q));
    $numerator = (((($a1 * $t + $a2) * $t + $a3) * $t + $a4) * $t + $a5) * $t + $a6;
    $denominator = (((($b1 * $t + $b2) * $t + $b3) * $t + $b4) * $t + $b5) * $t + 1;
    $z = $numerator / $denominator;
    return ($p < 0.5) ? -$z : $z;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik Stok - Indotar Inventory</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div id="sidebarCol" class="col-md-2 sidebar-column d-none d-md-flex">
                <?php include 'sidebar.php'; ?>
            </div>

            <div id="mainCol" class="col-md-10 main-content-col">
                <header class="bg-white border-bottom py-2">
                    <div class="container-fluid d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary"><i class="fa fa-bars"></i></button>
                            <h5 class="mb-0">Analitik</h5>
                            <small class="text-muted">Analisis Optimalisasi Stok</small>
                        </div>
                    </div>
                </header>

                <div class="container-fluid px-4 mt-4">

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                            <?php if ($message['type'] == 'success') echo '<i class="fas fa-check-circle me-2"></i>'; ?>
                            <?php if ($message['type'] == 'info') echo '<i class="fas fa-info-circle me-2"></i>'; ?>
                            <?= $message['text'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-sliders-h me-2 text-primary"></i> Historis Parameter</div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <label class="form-label text-muted small fw-bold">Pilih Barang</label>
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-box"></i></span>
                                            <select class="form-select border-start-0 ps-0" name="barang">
                                                <option selected disabled>-- Silahkan pilih barang --</option>
                                                <?php foreach ($barang_list as $b): ?>
                                                    <option value="<?= $b['id_barang'] ?>" <?= $selected_barang == $b['id_barang'] ? 'selected' : '' ?>><?= $b['nama_barang'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-calculator me-2"></i> Hitung & Simpan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><span><i class="fas fa-chart-area me-2 text-primary"></i> Visualisasi Stok</span></div>
                        <div class="card-body">
                            <?php if (!empty($historis)): ?>
                                <div style="height: 350px;">
                                    <canvas id="stokChart"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-chart-bar fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0 fw-medium">Grafik Pergerakan Stok</p>
                                    <small>Data akan muncul setelah proses hitung.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($analisa): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-primary text-white"><i class="fas fa-calculator me-2"></i> Hasil Analisa Inventory</div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-2 border-end">
                                                <h6 class="text-muted fw-bold small">Safety Stock (SS)</h6>
                                                <h3 class="text-danger fw-bold"><?= $analisa['SS'] ?></h3>
                                                <small class="text-muted" style="font-size: 10px;">Buffer Stok Aman</small>
                                            </div>
                                            <div class="col-md-2 border-end">
                                                <h6 class="text-muted fw-bold small">Reorder Point (ROP)</h6>
                                                <h3 class="text-warning fw-bold"><?= $analisa['ROP'] ?></h3>
                                                <small class="text-muted" style="font-size: 10px;">Titik Pesan Kembali</small>
                                            </div>
                                            <div class="col-md-2 border-end">
                                                <h6 class="text-muted fw-bold small">EOQ</h6>
                                                <h3 class="text-success fw-bold"><?= $analisa['EOQ'] ?></h3>
                                                <small class="text-muted" style="font-size: 10px;">Jumlah Pesan Ekonomis</small>
                                            </div>
                                            <div class="col-md-3 border-end">
                                                <h6 class="text-muted fw-bold small">Min - Max Level</h6>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <span class="badge bg-warning text-dark me-2">Min: <?= $analisa['Min'] ?></span>
                                                    <i class="fas fa-arrow-right small"></i>
                                                    <span class="badge bg-info text-dark ms-2">Max: <?= $analisa['Max'] ?></span>
                                                </div>
                                                <small class="text-muted" style="font-size: 10px;">Rentang Stok Ideal</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h6 class="text-muted fw-bold small">Estimasi Total Cost</h6>
                                                <h4 class="text-primary fw-bold">Rp <?= number_format($analisa['TC'], 0, ',', '.') ?></h4>
                                                <small class="text-muted" style="font-size: 10px;">Per Tahun</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-5">
                        <div class="card-header"><i class="fas fa-table me-2 text-primary"></i> Tabel Perhitungan</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th class="py-3 ps-4 text-start">Periode</th>
                                            <th class="py-3">Masuk</th>
                                            <th class="py-3">Keluar</th>
                                            <th class="py-3 table-primary">Stok Akhir</th>
                                            <th class="py-3 text-muted small">SS</th>
                                            <th class="py-3 text-muted small">ROP</th>
                                            <th class="py-3 text-muted small">EOQ</th>
                                            <th class="py-3 pe-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($historis): ?>
                                            <?php foreach ($historis as $h): ?>
                                                <?php
                                                $is_reorder = ($h['stok_akhir'] <= $h['rop']) && ($h['rop'] > 0);
                                                $row_class = $is_reorder ? "bg-danger bg-opacity-10" : "";
                                                ?>
                                                <tr class="<?= $row_class ?> text-center">
                                                    <td class="py-3 ps-4 text-start fw-medium"><?= date('d M Y', strtotime($h['tgl'])) ?></td>
                                                    <td class="text-success"><?= $h['masuk'] > 0 ? '+' . $h['masuk'] : '-' ?></td>
                                                    <td class="text-danger"><?= $h['keluar'] > 0 ? '-' . $h['keluar'] : '-' ?></td>
                                                    <td class="fw-bold table-primary"><?= number_format($h['stok_akhir']) ?></td>
                                                    <td class="text-muted"><?= number_format($h['ss']) ?></td>
                                                    <td class="text-muted"><?= number_format($h['rop']) ?></td>
                                                    <td class="text-muted"><?= number_format($h['eoq']) ?></td>
                                                    <td class="pe-4">
                                                        <?php if ($is_reorder): ?>
                                                            <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> ORDER</span>
                                                            <div style="font-size: 9px;" class="text-danger mt-1">Beli <strong><?= $h['eoq'] ?></strong> Unit</div>
                                                        <?php elseif ($h['stok_akhir'] <= $h['ss']): ?>
                                                            <span class="badge bg-warning text-dark">WASPADA</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success bg-opacity-75">Aman</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">Belum ada data.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="page-footer text-center pb-4">
                    <small>Copyright &copy; 2025 <strong>Indotar Inventory</strong>. All rights reserved.</small>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- 1. INISIALISASI CHART ---
        let stokChart;
        <?php if (!empty($historis)): ?>
            const dataLabels = <?php echo json_encode(array_column($historis, 'tgl')); ?>;
            const dataStok = <?php echo json_encode(array_map('floatval', array_column($historis, 'stok_akhir'))); ?>;
            const dataSafety = <?php echo json_encode(array_map('floatval', array_column($historis, 'ss'))); ?>;

            const ctx = document.getElementById('stokChart').getContext('2d');
            stokChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataLabels,
                    datasets: [{
                        label: 'Stok Akhir',
                        data: dataStok,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }, {
                        label: 'Safety Stock',
                        data: dataSafety,
                        borderColor: '#dc3545',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        <?php endif; ?>

        // --- 2. LOGIKA TOGGLE SIDEBAR (DIPERBAIKI) ---
        document.addEventListener("DOMContentLoaded", function() {
            const btnToggle = document.getElementById('btnToggleSidebar');
            const sidebar = document.getElementById('sidebarCol');
            const mainCol = document.getElementById('mainCol');

            if (btnToggle && sidebar && mainCol) {
                btnToggle.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Cek kondisi sidebar saat ini (apakah sedang sembunyi?)
                    // Kita cek apakah class 'd-none' ada.
                    const isHidden = sidebar.classList.contains('d-none');

                    if (isHidden) {
                        // KASUS: MAU MENAMPILKAN SIDEBAR (UNHIDE)

                        // 1. Munculkan Sidebar
                        sidebar.classList.remove('d-none');
                        sidebar.classList.add('d-md-flex');

                        // 2. KECILKAN Konten Utama (PENTING AGAR TIDAK TURUN)
                        // Ubah dari lebar 12 (full) menjadi 10 (sebagian)
                        mainCol.classList.remove('col-md-12');
                        mainCol.classList.add('col-md-10');

                    } else {
                        // KASUS: MAU MENYEMBUNYIKAN SIDEBAR (HIDE)

                        // 1. Sembunyikan Sidebar
                        sidebar.classList.add('d-none');
                        sidebar.classList.remove('d-md-flex');

                        // 2. LEBARKAN Konten Utama
                        // Ubah dari lebar 10 (sebagian) menjadi 12 (full)
                        mainCol.classList.remove('col-md-10');
                        mainCol.classList.add('col-md-12');
                    }

                    // 3. Resize Chart agar grafik menyesuaikan lebar baru
                    if (typeof stokChart !== 'undefined') {
                        setTimeout(() => {
                            stokChart.resize();
                        }, 200);
                    }
                });
            }
        });
    </script>
</body>

</html>