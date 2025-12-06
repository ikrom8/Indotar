<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Indotar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }

        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }

        .card {
            border: none;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <?php include 'sidebar.php'; ?>

        <div class="flex-grow-1 d-flex flex-column" style="height: 100vh; overflow-y: auto;">

            <header class="bg-white px-4 py-3 border-bottom shadow-sm d-flex justify-content-between align-items-center sticky-top">
                <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary" title="Toggle sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                <h4 class="m-0 fw-bold text-dark">Inventory</h4>
                <span class="text-muted small"><?php echo date('l, d F Y'); ?></span>
            </header>

            <main class="p-4 flex-grow-1">

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-secondary">
                                    <tr style="border-bottom: 2px solid #e2e8f0;">
                                        <th class="py-3 ps-4" width="10%">NO</th>
                                        <th class="py-3" width="60%">NAMA BARANG</th>
                                        <th class="py-3 pe-4 text-center" width="30%">STOK</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // 1. Panggil Koneksi
                                    include 'db.php';

                                    // 2. Query Database (Menambahkan kolom 'satuan')
                                    $query = "SELECT nama_barang, stok, satuan FROM barang ORDER BY nama_barang ASC";
                                    $result = mysqli_query($conn, $query);

                                    if (mysqli_num_rows($result) > 0) {
                                        $no = 1;
                                        // 3. Looping Data
                                        while ($row = mysqli_fetch_assoc($result)) {

                                            // Variabel data
                                            $stok = $row['stok'];
                                            // Ambil satuan, jika kosong default ke string kosong
                                            $satuan = isset($row['satuan']) ? $row['satuan'] : '';

                                            // Logika Warna Badge Stok
                                            $badgeClass = 'bg-success-subtle text-success'; // Default Hijau

                                            if ($stok == 0) {
                                                $badgeClass = 'bg-danger-subtle text-danger'; // Merah
                                            } else if ($stok < 10) {
                                                $badgeClass = 'bg-warning-subtle text-warning-emphasis'; // Kuning
                                            }

                                    ?>
                                            <tr>
                                                <td class="ps-4 fw-semibold text-secondary"><?php echo $no++; ?></td>

                                                <td class="fw-medium text-dark">
                                                    <?php echo htmlspecialchars($row['nama_barang']); ?>
                                                </td>

                                                <td class="text-center pe-4">
                                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2">
                                                        <?php echo $stok . ' ' . htmlspecialchars($satuan); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center py-4 text-muted">Data barang belum tersedia di database.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-end rounded-bottom-12">
                        <button class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                            Next <i class="fas fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                        </button>
                    </div>
                </div>

            </main>

            <footer class="text-center py-3 text-muted border-top bg-white small">
                &copy; 2025 Indotar Gentra Raya. All rights reserved.
            </footer>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>