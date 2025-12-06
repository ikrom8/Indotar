<?php
// master_data.php (Versi Update: Tujuan + Barang)
require_once "db.php";
require_once "auth_check.php";
require_login();
$current = current_user();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$CSRF = $_SESSION['csrf_token'];

function set_flash($msg, $type = 'info')
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function get_flash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
function esc($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// ----------------- HANDLE POST -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        set_flash('Invalid CSRF token.', 'danger');
        header('Location: master_data.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    $entity = $_POST['entity'] ?? '';

    // --- Kategori ---
    if ($entity === 'kategori') {
        if ($action === 'create') {
            $nama = trim($_POST['nama_kategori'] ?? '');
            if ($nama === '') {
                set_flash('Nama kategori wajib.', 'danger');
                header('Location: master_data.php#tab-kategori');
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $stmt->bind_param('s', $nama);
            if ($stmt->execute()) set_flash('Kategori ditambahkan.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-kategori');
            exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id_kategori'] ?? 0);
            $nama = trim($_POST['nama_kategori'] ?? '');
            if ($id <= 0 || $nama === '') {
                set_flash('Data tidak valid.', 'danger');
                header('Location: master_data.php#tab-kategori');
                exit;
            }
            $stmt = $conn->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
            $stmt->bind_param('si', $nama, $id);
            if ($stmt->execute()) set_flash('Kategori diperbarui.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-kategori');
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id_kategori'] ?? 0);
            if ($id <= 0) {
                set_flash('ID tidak valid.', 'danger');
                header('Location: master_data.php#tab-kategori');
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM kategori WHERE id_kategori = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) set_flash('Kategori dihapus.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-kategori');
            exit;
        }
    }

    // --- Supplier ---
    if ($entity === 'supplier') {
        if ($action === 'create') {
            $nama = trim($_POST['nama_supplier'] ?? '');
            $alamat = trim($_POST['alamat_supplier'] ?? '');
            $kontak = trim($_POST['kontak_supplier'] ?? '');

            if ($nama === '') {
                set_flash('Nama supplier wajib.', 'danger');
                header('Location: master_data.php#tab-supplier');
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO supplier (nama_supplier, alamat_supplier, no_telp) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nama, $alamat, $kontak);
            if ($stmt->execute()) set_flash('Supplier ditambahkan.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-supplier');
            exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id_supplier'] ?? 0);
            $nama = trim($_POST['nama_supplier'] ?? '');
            $alamat = trim($_POST['alamat_supplier'] ?? '');
            $kontak = trim($_POST['kontak_supplier'] ?? '');
            if ($id <= 0 || $nama === '') {
                set_flash('Data tidak valid.', 'danger');
                header('Location: master_data.php#tab-supplier');
                exit;
            }
            $stmt = $conn->prepare("UPDATE supplier SET nama_supplier = ?, alamat_supplier = ?, no_telp = ? WHERE id_supplier = ?");
            $stmt->bind_param('sssi', $nama, $alamat, $kontak, $id);
            if ($stmt->execute()) set_flash('Supplier diperbarui.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-supplier');
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id_supplier'] ?? 0);
            if ($id <= 0) {
                set_flash('ID tidak valid.', 'danger');
                header('Location: master_data.php#tab-supplier');
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM supplier WHERE id_supplier = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) set_flash('Supplier dihapus.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-supplier');
            exit;
        }
    }

    // --- Tujuan (UPDATE: Tambah Alamat) ---
    if ($entity === 'tujuan') {
        if ($action === 'create') {
            $nama = trim($_POST['nama_tujuan'] ?? '');
            $alamat = trim($_POST['alamat'] ?? ''); // New Field

            if ($nama === '') {
                set_flash('Nama tujuan wajib.', 'danger');
                header('Location: master_data.php#tab-tujuan');
                exit;
            }
            // Query insert diperbarui
            $stmt = $conn->prepare("INSERT INTO tujuan (nama_tujuan, alamat) VALUES (?, ?)");
            $stmt->bind_param('ss', $nama, $alamat);
            if ($stmt->execute()) set_flash('Tujuan ditambahkan.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-tujuan');
            exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id_tujuan'] ?? 0);
            $nama = trim($_POST['nama_tujuan'] ?? '');
            $alamat = trim($_POST['alamat'] ?? ''); // New Field

            if ($id <= 0 || $nama === '') {
                set_flash('Data tidak valid.', 'danger');
                header('Location: master_data.php#tab-tujuan');
                exit;
            }
            // Query update diperbarui
            $stmt = $conn->prepare("UPDATE tujuan SET nama_tujuan = ?, alamat = ? WHERE id_tujuan = ?");
            $stmt->bind_param('ssi', $nama, $alamat, $id);
            if ($stmt->execute()) set_flash('Tujuan diperbarui.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-tujuan');
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id_tujuan'] ?? 0);
            if ($id <= 0) {
                set_flash('ID tidak valid.', 'danger');
                header('Location: master_data.php#tab-tujuan');
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM tujuan WHERE id_tujuan = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) set_flash('Tujuan dihapus.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-tujuan');
            exit;
        }
    }

    // --- Barang (PENGGANTI USER) ---
    if ($entity === 'barang') {
        if ($action === 'create') {
            $id_kategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
            $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
            $nama = trim($_POST['nama_barang'] ?? '');
            $satuan = trim($_POST['satuan'] ?? '');
            $harga_beli = (float)($_POST['harga_beli'] ?? 0);
            $harga_jual = (float)($_POST['harga_jual'] ?? 0);
            $stok = (int)($_POST['stok'] ?? 0);

            if ($nama === '') {
                set_flash('Nama barang wajib diisi.', 'danger');
                header('Location: master_data.php#tab-barang');
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO barang (id_kategori, id_supplier, nama_barang, satuan, harga_beli, harga_jual) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisssd', $id_kategori, $id_supplier, $nama, $satuan, $harga_beli, $harga_jual);

            if ($stmt->execute()) set_flash('Barang ditambahkan.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-barang');
            exit;
        }

        if ($action === 'update') {
            $id = (int)($_POST['id_barang'] ?? 0);
            $id_kategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
            $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
            $nama = trim($_POST['nama_barang'] ?? '');
            $satuan = trim($_POST['satuan'] ?? '');
            $harga_beli = (float)($_POST['harga_beli'] ?? 0);
            $harga_jual = (float)($_POST['harga_jual'] ?? 0);


            if ($id <= 0 || $nama === '') {
                set_flash('Data barang tidak valid.', 'danger');
                header('Location: master_data.php#tab-barang');
                exit;
            }

            $stmt = $conn->prepare("UPDATE barang SET id_kategori=?, id_supplier=?, nama_barang=?, satuan=?, harga_beli=?, harga_jual=? WHERE id_barang=?");
            $stmt->bind_param('iisssdd', $id_kategori, $id_supplier, $nama, $satuan, $harga_beli, $harga_jual, $id);

            if ($stmt->execute()) set_flash('Barang diperbarui.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-barang');
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id_barang'] ?? 0);
            if ($id <= 0) {
                set_flash('ID barang tidak valid.', 'danger');
                header('Location: master_data.php#tab-barang');
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) set_flash('Barang dihapus.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-barang');
            exit;
        }
    }

    // ---------parameter----------------
    // --- Parameter Produk (NEW) ---
    if ($entity === 'parameter') {
        if ($action === 'create') {
            $id_barang = (int)($_POST['id_barang'] ?? 0);
            $sl = (float)$_POST['service_level'];
            $sd = (float)$_POST['standar_deviasi'];
            $lt = (int)$_POST['lead_time'];
            $ph = (float)$_POST['permintaan_harian'];
            $bp = (float)$_POST['biaya_pemesanan'];
            $ha = (int)$_POST['jml_hari_aktif'];

            if ($id_barang <= 0) {
                set_flash('Pilih barang terlebih dahulu.', 'danger');
                header('Location: master_data.php#tab-parameter');
                exit;
            }

            // Cek duplikat
            $cek = $conn->query("SELECT id_parameter FROM parameter_produk WHERE id_barang = $id_barang");
            if ($cek->num_rows > 0) {
                set_flash('Parameter untuk barang ini sudah ada. Gunakan Edit.', 'warning');
                header('Location: master_data.php#tab-parameter');
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO parameter_produk (id_barang, service_level, standar_deviasi, lead_time, permintaan_harian, biaya_pemesanan, jml_hari_aktif) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iddiddi', $id_barang, $sl, $sd, $lt, $ph, $bp, $ha);

            if ($stmt->execute()) set_flash('Parameter berhasil ditambahkan.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');

            $stmt->close();
            header('Location: master_data.php#tab-parameter');
            exit;
        }

        if ($action === 'update') {
            $id = (int)($_POST['id_parameter'] ?? 0);
            $sl = (float)$_POST['service_level'];
            $sd = (float)$_POST['standar_deviasi'];
            $lt = (int)$_POST['lead_time'];
            $ph = (float)$_POST['permintaan_harian'];
            $bp = (float)$_POST['biaya_pemesanan'];
            $ha = (int)$_POST['jml_hari_aktif'];

            $stmt = $conn->prepare("UPDATE parameter_produk SET service_level=?, standar_deviasi=?, lead_time=?, permintaan_harian=?, biaya_pemesanan=?, jml_hari_aktif=? WHERE id_parameter=?");
            $stmt->bind_param('ddiddii', $sl, $sd, $lt, $ph, $bp, $ha, $id);

            if ($stmt->execute()) set_flash('Parameter diperbarui.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');

            $stmt->close();
            header('Location: master_data.php#tab-parameter');
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id_parameter'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM parameter_produk WHERE id_parameter = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) set_flash('Parameter dihapus.', 'success');
            else set_flash('Gagal: ' . $conn->error, 'danger');
            $stmt->close();
            header('Location: master_data.php#tab-parameter');
            exit;
        }
    }

    set_flash('Aksi tidak dikenali.', 'danger');
    header('Location: master_data.php');
    exit;
}

// ----------------- READ for display -----------------
$kategoris = [];
$suppliers = [];
$tujuans = [];
$barangs = [];

$q = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
if ($q) {
    while ($r = $q->fetch_assoc()) $kategoris[] = $r;
    $q->free();
}

$q = $conn->query("SELECT id_supplier, nama_supplier, alamat_supplier, no_telp FROM supplier ORDER BY nama_supplier ASC");
if ($q) {
    while ($r = $q->fetch_assoc()) $suppliers[] = $r;
    $q->free();
}

// Update: Select alamat juga
$q = $conn->query("SELECT id_tujuan, nama_tujuan, alamat FROM tujuan ORDER BY nama_tujuan ASC");
if ($q) {
    while ($r = $q->fetch_assoc()) $tujuans[] = $r;
    $q->free();
}

// Barang: Join dengan kategori & supplier untuk tampilan nama
$sql_barang = "SELECT b.*, k.nama_kategori, s.nama_supplier 
               FROM barang b 
               LEFT JOIN kategori k ON b.id_kategori = k.id_kategori 
               LEFT JOIN supplier s ON b.id_supplier = s.id_supplier 
               ORDER BY b.nama_barang ASC";
$q = $conn->query($sql_barang);
if ($q) {
    while ($r = $q->fetch_assoc()) $barangs[] = $r;
    $q->free();
}

// Parameter
$parameters = [];
$sql_param = "SELECT p.*, b.nama_barang 
              FROM parameter_produk p 
              JOIN barang b ON p.id_barang = b.id_barang 
              ORDER BY b.nama_barang ASC";
$q = $conn->query($sql_param);
if ($q) {
    while ($r = $q->fetch_assoc()) $parameters[] = $r;
    $q->free();
}

$flash = get_flash();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Master Data — Inventori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            overflow-x: hidden;
        }

        .main-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .content-wrapper {
            flex-grow: 1;
            width: 100%;
            padding: 20px;
        }

        .card {
            border-radius: 8px;
        }

        .inline-form {
            display: none;
            margin-bottom: 1rem;
        }

        .inline-edit-row {
            background: #fff9e6;
        }

        .small-actions .btn {
            padding: .28rem .5rem;
        }

        /* Supaya kolom tabel barang tidak terlalu sempit */
        .col-rupiah {
            text-align: right;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-wrapper">
            <header class="bg-white border-bottom py-2">
                <div class="container-fluid d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary" title="Toggle sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                        <h5 class="mb-0">Master Data</h5>
                        <small class="text-muted">Kelola kategori, supplier, tujuan, dan data barang.</small>
                    </div>
                </div>
            </header>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">

                </div>
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo esc($flash['type']); ?> alert-dismissible">
                        <?php echo esc($flash['msg']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-kategori">Kategori</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-supplier">Supplier</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tujuan">Tujuan</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-barang">Barang</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-parameter">Parameter</button></li>
                </ul>

                <div class="tab-content">
                    <!-- tab-kategori -->
                    <div class="tab-pane fade show active" id="tab-kategori">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div><i class="fa fa-tags"></i> Data Kategori</div>
                                    <div><button id="btnToggleAddKategori" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah</button></div>
                                </div>
                                <form id="formAddKategori" class="inline-form" method="post" action="master_data.php#tab-kategori">
                                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                                    <input type="hidden" name="entity" value="kategori">
                                    <input type="hidden" name="action" value="create">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-8"><input name="nama_kategori" class="form-control" placeholder="Nama kategori" required></div>
                                        <div class="col-md-4">
                                            <button class="btn btn-primary">Simpan</button>
                                            <button type="button" id="btnCancelAddKategori" class="btn btn-outline-secondary">Batal</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Kategori</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kategoriTableBody">
                                            <?php $i = 1;
                                            foreach ($kategoris as $row): ?>
                                                <tr data-id="<?php echo esc($row['id_kategori']); ?>">
                                                    <td><?php echo $i++; ?></td>
                                                    <td class="kategori-name"><?php echo esc($row['nama_kategori']); ?></td>
                                                    <td class="small-actions">
                                                        <button class="btn btn-sm btn-warning btn-edit-kategori">Edit</button>
                                                        <button class="btn btn-sm btn-danger btn-delete-kategori">Hapus</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                            if (empty($kategoris)) echo '<tr><td colspan="3" class="text-muted">Belum ada data.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- tan-supplier -->
                    <div class="tab-pane fade" id="tab-supplier">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div><i class="fa fa-truck"></i> Data Supplier</div>
                                    <div><button id="btnToggleAddSupplier" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah</button></div>
                                </div>
                                <form id="formAddSupplier" class="inline-form" method="post" action="master_data.php#tab-supplier">
                                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                                    <input type="hidden" name="entity" value="supplier">
                                    <input type="hidden" name="action" value="create">
                                    <div class="row g-2">
                                        <div class="col-md-4"><input name="nama_supplier" class="form-control" placeholder="Nama supplier" required></div>
                                        <div class="col-md-4"><input name="kontak_supplier" class="form-control" placeholder="No Telp (Kontak)"></div>
                                        <div class="col-md-4"><input name="alamat_supplier" class="form-control" placeholder="Alamat Supplier"></div>
                                        <div class="col-12 mt-2">
                                            <button class="btn btn-primary">Simpan</button>
                                            <button type="button" id="btnCancelAddSupplier" class="btn btn-outline-secondary">Batal</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Alamat</th>
                                                <th>No. Telp</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="supplierTableBody">
                                            <?php $i = 1;
                                            foreach ($suppliers as $s): ?>
                                                <tr data-id="<?php echo esc($s['id_supplier']); ?>">
                                                    <td><?php echo $i++; ?></td>
                                                    <td class="supplier-name"><?php echo esc($s['nama_supplier']); ?></td>
                                                    <td class="supplier-alamat"><?php echo esc($s['alamat_supplier']); ?></td>
                                                    <td class="supplier-kontak"><?php echo esc($s['no_telp']); ?></td>
                                                    <td class="small-actions">
                                                        <button class="btn btn-sm btn-warning btn-edit-supplier">Edit</button>
                                                        <button class="btn btn-sm btn-danger btn-delete-supplier">Hapus</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                            if (empty($suppliers)) echo '<tr><td colspan="5" class="text-muted">Belum ada data.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- tab-tujuan -->
                    <div class="tab-pane fade" id="tab-tujuan">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div><i class="fa fa-location-dot"></i> Tujuan Pengiriman</div>
                                    <div><button id="btnToggleAddTujuan" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah</button></div>
                                </div>
                                <form id="formAddTujuan" class="inline-form" method="post" action="master_data.php#tab-tujuan">
                                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                                    <input type="hidden" name="entity" value="tujuan">
                                    <input type="hidden" name="action" value="create">
                                    <div class="row g-2">
                                        <div class="col-md-5"><input name="nama_tujuan" class="form-control" placeholder="Nama tujuan" required></div>
                                        <div class="col-md-5"><input name="alamat" class="form-control" placeholder="Alamat lengkap"></div>
                                        <div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div>
                                        <div class="col-12"><button type="button" id="btnCancelAddTujuan" class="btn btn-sm btn-outline-secondary">Batal</button></div>
                                    </div>
                                </form>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Tujuan</th>
                                                <th>Alamat</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tujuanTableBody">
                                            <?php $i = 1;
                                            foreach ($tujuans as $t): ?>
                                                <tr data-id="<?php echo esc($t['id_tujuan']); ?>">
                                                    <td><?php echo $i++; ?></td>
                                                    <td class="tujuan-name"><?php echo esc($t['nama_tujuan']); ?></td>
                                                    <td class="tujuan-alamat"><?php echo esc($t['alamat']); ?></td>
                                                    <td class="small-actions">
                                                        <button class="btn btn-sm btn-warning btn-edit-tujuan">Edit</button>
                                                        <button class="btn btn-sm btn-danger btn-delete-tujuan">Hapus</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                            if (empty($tujuans)) echo '<tr><td colspan="4" class="text-muted">Belum ada data.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- tab-barang -->
                    <div class="tab-pane fade" id="tab-barang">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div><i class="fa fa-box"></i> Data Barang</div>
                                    <div><button id="btnToggleAddBarang" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Barang</button></div>
                                </div>
                                <form id="formAddBarang" class="inline-form" method="post" action="master_data.php#tab-barang">
                                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                                    <input type="hidden" name="entity" value="barang">
                                    <input type="hidden" name="action" value="create">

                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Nama Barang</label>
                                            <input name="nama_barang" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Kategori</label>
                                            <select name="id_kategori" class="form-select form-select-sm">
                                                <option value="">-- Pilih --</option>
                                                <?php foreach ($kategoris as $k): ?>
                                                    <option value="<?php echo $k['id_kategori']; ?>"><?php echo esc($k['nama_kategori']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Supplier</label>
                                            <select name="id_supplier" class="form-select form-select-sm">
                                                <option value="">-- Pilih --</option>
                                                <?php foreach ($suppliers as $s): ?>
                                                    <option value="<?php echo $s['id_supplier']; ?>"><?php echo esc($s['nama_supplier']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Satuan</label>
                                            <input name="satuan" class="form-control form-control-sm" placeholder="Pcs/Kg">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Harga Beli</label>
                                            <input type="number" name="harga_beli" class="form-control form-control-sm" step="0.01">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Harga Jual</label>
                                            <input type="number" name="harga_jual" class="form-control form-control-sm" step="0.01">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <div class="w-100">
                                                <button class="btn btn-sm btn-primary w-100">Simpan</button>
                                                <button type="button" id="btnCancelAddBarang" class="btn btn-sm btn-outline-secondary w-100 mt-1">Batal</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped align-middle" style="font-size:0.9rem">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Barang</th>
                                                <th>Kategori</th>
                                                <th>Supplier</th>
                                                <th>Satuan</th>
                                                <th class="text-end">Hrg Beli</th>
                                                <th class="text-end">Hrg Jual</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="barangTableBody">
                                            <?php $i = 1;
                                            foreach ($barangs as $b): ?>
                                                <tr data-id="<?php echo esc($b['id_barang']); ?>"
                                                    data-id-kategori="<?php echo esc($b['id_kategori']); ?>"
                                                    data-id-supplier="<?php echo esc($b['id_supplier']); ?>"
                                                    data-harga-beli="<?php echo esc($b['harga_beli']); ?>"
                                                    data-harga-jual="<?php echo esc($b['harga_jual']); ?>">
                                                    <td><?php echo $i++; ?></td>
                                                    <td class="barang-nama fw-bold"><?php echo esc($b['nama_barang']); ?></td>
                                                    <td class="barang-kategori"><?php echo esc($b['nama_kategori'] ?? '-'); ?></td>
                                                    <td class="barang-supplier"><?php echo esc($b['nama_supplier'] ?? '-'); ?></td>
                                                    <td class="barang-satuan"><?php echo esc($b['satuan']); ?></td>
                                                    <td class="col-rupiah"><?php echo number_format($b['harga_beli'], 0, ',', '.'); ?></td>
                                                    <td class="col-rupiah"><?php echo number_format($b['harga_jual'], 0, ',', '.'); ?></td>
                                                    <td class="small-actions">
                                                        <button class="btn btn-sm btn-warning btn-edit-barang">Edit</button>
                                                        <button class="btn btn-sm btn-danger btn-delete-barang">Hapus</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                            if (empty($barangs)) echo '<tr><td colspan="9" class="text-center text-muted">Belum ada data barang.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- tab-paramater -->
                    <div class="tab-pane fade" id="tab-parameter">
                        <div class="card mb-3">
                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div><i class="fa fa-sliders"></i> <strong>Parameter Produk</strong> (Safety Stock & ROP)</div>
                                    <div><button id="btnToggleAddParameter" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Parameter</button></div>
                                </div>

                                <div class="alert alert-info border-0 shadow-sm mb-4" style="font-size: 0.9rem;">
                                    <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="collapse" data-bs-target="#guideCollapse" aria-expanded="false" aria-controls="guideCollapse">
                                        <h6 class="fw-bold mb-0 text-primary">
                                            <i class="fa fa-book-open me-2"></i>Petunjuk Pengisian Parameter (Klik untuk Buka/Tutup)
                                        </h6>
                                        <i class="fa fa-chevron-down ms-auto text-primary"></i>
                                    </a>

                                    <div class="collapse" id="guideCollapse">
                                        <hr class="mt-2 mb-2 border-primary opacity-25">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <ul class="list-group list-group-flush bg-transparent">
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>ID Parameter:</strong><br>
                                                        <small class="text-muted">Sistem akan membuatkan ID secara otomatis. Tidak perlu diisi manual.</small>
                                                    </li>
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>ID Barang:</strong><br>
                                                        <small class="text-muted">Pilih barang yang ingin Anda tetapkan parameternya dari daftar barang yang tersedia. Pastikan memilih barang yang benar agar perhitungan stok tepat.</small>
                                                    </li>
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>Service Level:</strong><br>
                                                        <small class="text-muted">Masukkan tingkat pelayanan yang diinginkan (desimal 0 - 1). <br>Contoh: <strong>0.95</strong> artinya 95% kemungkinan barang tersedia.</small>
                                                    </li>
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>Standar Deviasi:</strong><br>
                                                        <small class="text-muted">Masukkan standar deviasi permintaan harian barang. Angka ini digunakan untuk menghitung safety stock agar stok lebih aman.</small>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-group list-group-flush bg-transparent">
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>Lead Time:</strong><br>
                                                        <small class="text-muted">Waktu tunggu pemesanan (hari) dari supplier hingga barang diterima. Contoh: jika butuh 5 hari, masukkan <strong>5</strong>.</small>
                                                    </li>
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>Permintaan Harian:</strong><br>
                                                        <small class="text-muted">Rata-rata jumlah barang yang diminta per hari. Gunakan data historis agar akurat.</small>
                                                    </li>
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>Biaya Pemesanan:</strong><br>
                                                        <small class="text-muted">Biaya yang dikeluarkan setiap kali order (ongkir/admin). Bukan harga barang.</small>
                                                    </li>
                                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                        <strong>Jumlah Hari Aktif:</strong><br>
                                                        <small class="text-muted">Jumlah hari kerja dalam periode perhitungan. Contoh: <strong>30</strong> (sebulan), <strong>360</strong> (setahun).</small>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <form id="formAddParameter" class="inline-form p-3 border rounded bg-white shadow-sm mb-4" method="post" action="master_data.php#tab-parameter">
                                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                                    <input type="hidden" name="entity" value="parameter">
                                    <input type="hidden" name="action" value="create">

                                    <h6 class="mb-3 text-primary">Form Input Data Baru</h6>

                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">Pilih Barang</label>
                                            <select name="id_barang" class="form-select" required>
                                                <option value="">-- Pilih Barang --</option>
                                                <?php foreach ($barangs as $b): ?>
                                                    <option value="<?php echo $b['id_barang']; ?>"><?php echo esc($b['nama_barang']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Permintaan Harian (Qty)</label>
                                            <input type="number" step="0.01" name="permintaan_harian" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Lead Time (Hari)</label>
                                            <input type="number" name="lead_time" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Standar Deviasi</label>
                                            <input type="number" step="0.01" name="standar_deviasi" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Service Level (0 - 1)</label>
                                            <input type="number" step="0.01" max="1" name="service_level" class="form-control" placeholder="0.95" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Biaya Pemesanan (Rp)</label>
                                            <input type="number" name="biaya_pemesanan" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Hari Aktif (Periode)</label>
                                            <input type="number" name="jml_hari_aktif" class="form-control" placeholder="360" required>
                                        </div>

                                        <div class="col-12 mt-4 d-flex gap-2">
                                            <button class="btn btn-primary px-4"><i class="fa fa-save"></i> Simpan</button>
                                            <button type="button" id="btnCancelAddParam" class="btn btn-outline-secondary px-4">Batal</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped align-middle text-nowrap" style="font-size:0.85rem">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Barang</th>
                                                <th class="text-center">Svc Lvl</th>
                                                <th class="text-center">Std Dev</th>
                                                <th class="text-center">Lead Time</th>
                                                <th class="text-center">Demand/Day</th>
                                                <th class="text-end">Biaya Pesan</th>
                                                <th class="text-center">Hari Aktif</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paramTableBody">
                                            <?php foreach ($parameters as $p): ?>
                                                <tr data-id="<?php echo $p['id_parameter']; ?>"
                                                    data-sl="<?php echo $p['service_level']; ?>"
                                                    data-sd="<?php echo $p['standar_deviasi']; ?>"
                                                    data-lt="<?php echo $p['lead_time']; ?>"
                                                    data-ph="<?php echo $p['permintaan_harian']; ?>"
                                                    data-bp="<?php echo $p['biaya_pemesanan']; ?>"
                                                    data-ha="<?php echo $p['jml_hari_aktif']; ?>">
                                                    <td class="fw-bold text-primary param-name"><?php echo esc($p['nama_barang']); ?></td>
                                                    <td class="text-center"><?php echo $p['service_level']; ?></td>
                                                    <td class="text-center"><?php echo $p['standar_deviasi']; ?></td>
                                                    <td class="text-center"><?php echo $p['lead_time']; ?></td>
                                                    <td class="text-center"><?php echo $p['permintaan_harian']; ?></td>
                                                    <td class="text-end"><?php echo number_format($p['biaya_pemesanan'], 0, ',', '.'); ?></td>
                                                    <td class="text-center"><?php echo $p['jml_hari_aktif']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning btn-edit-param py-0 px-2">Edit</button>
                                                        <button class="btn btn-sm btn-danger btn-delete-param py-0 px-2">Hapus</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                            if (empty($parameters)) echo '<tr><td colspan="8" class="text-muted text-center py-3">Belum ada data parameter. Klik tambah untuk mulai.</td></tr>';
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <form id="hiddenDeleteForm" method="post" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
        <input type="hidden" name="entity" value="">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_kategori" value="">
        <input type="hidden" name="id_supplier" value="">
        <input type="hidden" name="id_tujuan" value="">
        <input type="hidden" name="id_barang" value="">
    </form>

    <template id="kategoriEditTemplate">
        <tr class="inline-edit-row">
            <td class="col-no"></td>
            <td>
                <form class="form-edit-kategori" method="post" action="master_data.php#tab-kategori">
                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                    <input type="hidden" name="entity" value="kategori">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_kategori" value="">
                    <div class="input-group input-group-sm"><input name="nama_kategori" class="form-control" required></div>
                </form>
            </td>
            <td>
                <div class="d-flex gap-1"><button class="btn btn-sm btn-primary btn-save-edit">Simpan</button><button class="btn btn-sm btn-outline-secondary btn-cancel-edit">Batal</button></div>
            </td>
        </tr>
    </template>

    <template id="supplierEditTemplate">
        <tr class="inline-edit-row">
            <td class="col-no"></td>
            <td colspan="1">
                <form class="form-edit-supplier" method="post" action="master_data.php#tab-supplier">
                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                    <input type="hidden" name="entity" value="supplier">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_supplier" value="">
                    <div class="row g-2">
                        <div class="col-md-5"><input name="nama_supplier" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><input name="kontak_supplier" class="form-control form-control-sm" placeholder="No Telp"></div>
                        <div class="col-md-3"><input name="alamat_supplier" class="form-control form-control-sm" placeholder="Alamat"></div>
                    </div>
                </form>
            </td>
            <td>
                <div class="d-flex gap-1"><button class="btn btn-sm btn-primary btn-save-edit">Simpan</button><button class="btn btn-sm btn-outline-secondary btn-cancel-edit">Batal</button></div>
            </td>
        </tr>
    </template>

    <template id="tujuanEditTemplate">
        <tr class="inline-edit-row">
            <td class="col-no"></td>
            <td colspan="2">
                <form class="form-edit-tujuan" method="post" action="master_data.php#tab-tujuan">
                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                    <input type="hidden" name="entity" value="tujuan">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_tujuan" value="">
                    <input type="hidden" name="id_parameter" value="">
                    <div class="row g-1">
                        <div class="col-md-5"><input name="nama_tujuan" class="form-control form-control-sm" placeholder="Nama" required></div>
                        <div class="col-md-7"><input name="alamat" class="form-control form-control-sm" placeholder="Alamat"></div>
                    </div>
                </form>
            </td>
            <td>
                <div class="d-flex gap-1"><button class="btn btn-sm btn-primary btn-save-edit">Simpan</button><button class="btn btn-sm btn-outline-secondary btn-cancel-edit">Batal</button></div>
            </td>
        </tr>
    </template>

    <template id="barangEditTemplate">
        <tr class="inline-edit-row">
            <td class="col-no"></td>
            <td colspan="7">
                <form class="form-edit-barang" method="post" action="master_data.php#tab-barang">
                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                    <input type="hidden" name="entity" value="barang">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_barang" value="">
                    <input type="hidden" name="id_parameter" value="">

                    <div class="row g-1">
                        <div class="col-md-3">
                            <input name="nama_barang" class="form-control form-control-sm" placeholder="Nama Barang" required>
                        </div>
                        <div class="col-md-2">
                            <select name="id_kategori" class="form-select form-select-sm">
                                <option value="">- Kategori -</option>
                                <?php foreach ($kategoris as $k): ?>
                                    <option value="<?php echo $k['id_kategori']; ?>"><?php echo esc($k['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="id_supplier" class="form-select form-select-sm">
                                <option value="">- Supplier -</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id_supplier']; ?>"><?php echo esc($s['nama_supplier']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <input name="satuan" class="form-control form-control-sm" placeholder="Satuan">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="harga_beli" class="form-control form-control-sm" placeholder="Hrg Beli">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="harga_jual" class="form-control form-control-sm" placeholder="Hrg Jual">
                        </div>
                    </div>
                </form>
            </td>
            <td>
                <div class="d-flex gap-1"><button class="btn btn-sm btn-primary btn-save-edit">Simpan</button><button class="btn btn-sm btn-outline-secondary btn-cancel-edit">Batal</button></div>
            </td>
        </tr>
    </template>

    <template id="paramEditTemplate">
        <tr class="inline-edit-row">
            <td class="param-name-display fw-bold text-primary"></td>
            <td colspan="7">
                <form class="form-edit-param  p-3 border rounded bg-white shadow-sm mb-4" method="post" action="master_data.php#tab-parameter">
                    <input type="hidden" name="csrf_token" value="<?php echo $CSRF; ?>">
                    <input type="hidden" name="entity" value="parameter">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_parameter" value="">

                    <h6 class="mb-3 text-warning"><i class="fa fa-pencil"></i> Edit Parameter</h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Permintaan Harian</label>
                            <input type="number" step="0.01" name="permintaan_harian" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Lead Time (Hari)</label>
                            <input type="number" name="lead_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Standar Deviasi</label>
                            <input type="number" step="0.01" name="standar_deviasi" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Service Level (0-1)</label>
                            <input type="number" step="0.01" max="1" name="service_level" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Biaya Pesan (Rp)</label>
                            <input type="number" name="biaya_pemesanan" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Hari Aktif</label>
                            <input type="number" name="jml_hari_aktif" class="form-control" required>
                        </div>
                        <div class="col-12 mt-3 text-end">
                            <button class="btn btn-sm btn-primary btn-save-edit">Simpan Perubahan</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel-edit">Batal</button>
                        </div>
                    </div>
                </form>
            </td>
        </tr>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // SCRIPT JAVASCRIPT
        const toggle = (btnId, formId, cancelId) => {
            const btn = document.getElementById(btnId);
            const form = document.getElementById(formId);
            const cancel = document.getElementById(cancelId);
            btn && btn.addEventListener('click', () => form.style.display = (form.style.display === 'block' ? 'none' : 'block'));
            cancel && cancel.addEventListener('click', () => form.style.display = 'none');
        };
        toggle('btnToggleAddKategori', 'formAddKategori', 'btnCancelAddKategori');
        toggle('btnToggleAddSupplier', 'formAddSupplier', 'btnCancelAddSupplier');
        toggle('btnToggleAddTujuan', 'formAddTujuan', 'btnCancelAddTujuan');
        toggle('btnToggleAddBarang', 'formAddBarang', 'btnCancelAddBarang');
        toggle('btnToggleAddParameter', 'formAddParameter', 'btnCancelAddParameter');

        function startInlineEdit(tr, templateId, populateFn) {
            const tpl = document.getElementById(templateId);
            const clone = tpl.content.cloneNode(true);
            const editRow = clone.querySelector('tr');
            const colNo = editRow.querySelector('.col-no');
            if (colNo) colNo.textContent = tr.querySelector('td').textContent;
            populateFn(tr, editRow);
            tr.style.display = 'none';
            tr.parentNode.insertBefore(editRow, tr.nextSibling);
            editRow.querySelectorAll('.btn-cancel-edit').forEach(btn => {
                btn.addEventListener('click', () => {
                    editRow.remove();
                    tr.style.display = '';
                });
            });
            editRow.querySelectorAll('.btn-save-edit').forEach(btn => {
                btn.addEventListener('click', () => {
                    const form = editRow.querySelector('form');
                    if (form) form.submit();
                });
            });
        }

        /* Listener Kategori */
        document.querySelectorAll('.btn-edit-kategori').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                startInlineEdit(tr, 'kategoriEditTemplate', (origTr, editRow) => {
                    editRow.querySelector('input[name="id_kategori"]').value = origTr.getAttribute('data-id');
                    editRow.querySelector('input[name="nama_kategori"]').value = origTr.querySelector('.kategori-name').textContent.trim();
                });
            });
        });
        document.querySelectorAll('.btn-delete-kategori').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                if (confirm('Hapus kategori "' + tr.querySelector('.kategori-name').textContent.trim() + '"?')) {
                    const f = document.getElementById('hiddenDeleteForm');
                    f.querySelector('input[name="entity"]').value = 'kategori';
                    f.querySelector('input[name="id_kategori"]').value = tr.getAttribute('data-id');
                    f.submit();
                }
            });
        });

        /* Listener Supplier */
        document.querySelectorAll('.btn-edit-supplier').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                startInlineEdit(tr, 'supplierEditTemplate', (origTr, editRow) => {
                    editRow.querySelector('input[name="id_supplier"]').value = origTr.getAttribute('data-id');
                    editRow.querySelector('input[name="nama_supplier"]').value = origTr.querySelector('.supplier-name').textContent.trim();
                    editRow.querySelector('input[name="alamat_supplier"]').value = origTr.querySelector('.supplier-alamat').textContent.trim();
                    editRow.querySelector('input[name="kontak_supplier"]').value = origTr.querySelector('.supplier-kontak').textContent.trim();
                });
            });
        });
        document.querySelectorAll('.btn-delete-supplier').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                if (confirm('Hapus supplier "' + tr.querySelector('.supplier-name').textContent.trim() + '"?')) {
                    const f = document.getElementById('hiddenDeleteForm');
                    f.querySelector('input[name="entity"]').value = 'supplier';
                    f.querySelector('input[name="id_supplier"]').value = tr.getAttribute('data-id');
                    f.submit();
                }
            });
        });

        /* Listener Tujuan  */
        document.querySelectorAll('.btn-edit-tujuan').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                startInlineEdit(tr, 'tujuanEditTemplate', (origTr, editRow) => {
                    editRow.querySelector('input[name="id_tujuan"]').value = origTr.getAttribute('data-id');
                    editRow.querySelector('input[name="nama_tujuan"]').value = origTr.querySelector('.tujuan-name').textContent.trim();
                    editRow.querySelector('input[name="alamat"]').value = origTr.querySelector('.tujuan-alamat').textContent.trim();
                });
            });
        });
        document.querySelectorAll('.btn-delete-tujuan').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                if (confirm('Hapus tujuan "' + tr.querySelector('.tujuan-name').textContent.trim() + '"?')) {
                    const f = document.getElementById('hiddenDeleteForm');
                    f.querySelector('input[name="entity"]').value = 'tujuan';
                    f.querySelector('input[name="id_tujuan"]').value = tr.getAttribute('data-id');
                    f.submit();
                }
            });
        });

        /* Listener Barang  */
        document.querySelectorAll('.btn-edit-barang').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                startInlineEdit(tr, 'barangEditTemplate', (origTr, editRow) => {
                    editRow.querySelector('input[name="id_barang"]').value = origTr.getAttribute('data-id');
                    editRow.querySelector('input[name="nama_barang"]').value = origTr.querySelector('.barang-nama').textContent.trim();
                    editRow.querySelector('select[name="id_kategori"]').value = origTr.getAttribute('data-id-kategori');
                    editRow.querySelector('select[name="id_supplier"]').value = origTr.getAttribute('data-id-supplier');
                    editRow.querySelector('input[name="satuan"]').value = origTr.querySelector('.barang-satuan').textContent.trim();
                    editRow.querySelector('input[name="harga_beli"]').value = origTr.getAttribute('data-harga-beli');
                    editRow.querySelector('input[name="harga_jual"]').value = origTr.getAttribute('data-harga-jual');

                });
            });
        });


        document.querySelectorAll('.btn-delete-barang').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                if (confirm('Hapus barang "' + tr.querySelector('.barang-nama').textContent.trim() + '"?')) {
                    const f = document.getElementById('hiddenDeleteForm');
                    f.querySelector('input[name="entity"]').value = 'barang';
                    f.querySelector('input[name="id_barang"]').value = tr.getAttribute('data-id');
                    f.submit();
                }
            });
        });

        /* --- LISTENER PARAMETER (YANG SEBELUMNYA MACET) --- */
        document.querySelectorAll('.btn-edit-param').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                startInlineEdit(tr, 'paramEditTemplate', (origTr, editRow) => {
                    // Populate display name
                    editRow.querySelector('.param-name-display').textContent = origTr.querySelector('.param-name').textContent;

                    // Populate hidden ID
                    editRow.querySelector('input[name="id_parameter"]').value = origTr.getAttribute('data-id');

                    // Populate inputs
                    editRow.querySelector('input[name="service_level"]').value = origTr.getAttribute('data-sl');
                    editRow.querySelector('input[name="standar_deviasi"]').value = origTr.getAttribute('data-sd');
                    editRow.querySelector('input[name="lead_time"]').value = origTr.getAttribute('data-lt');
                    editRow.querySelector('input[name="permintaan_harian"]').value = origTr.getAttribute('data-ph');
                    editRow.querySelector('input[name="biaya_pemesanan"]').value = origTr.getAttribute('data-bp');
                    editRow.querySelector('input[name="jml_hari_aktif"]').value = origTr.getAttribute('data-ha');
                });
            });
        });

        document.querySelectorAll('.btn-delete-param').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.target.closest('tr');
                if (confirm('Hapus parameter untuk "' + tr.querySelector('.param-name').textContent.trim() + '"?')) {
                    const f = document.getElementById('hiddenDeleteForm');
                    f.querySelector('input[name="entity"]').value = 'parameter';
                    // Pastikan input id_parameter ada di hidden form
                    let inputId = f.querySelector('input[name="id_parameter"]');
                    if (!inputId) {
                        // Buat input jika belum ada (safety check)
                        inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'id_parameter';
                        f.appendChild(inputId);
                    }
                    inputId.value = tr.getAttribute('data-id');
                    f.submit();
                }
            });
        });
    </script>
</body>

</html>