<?php
require_once "db.php";
require_once "auth_check.php";


require_login();
require_role(['admin']); // hanya admin yang boleh CRUD supplier

// =========================
// BACKEND: TAMBAH SUPPLIER
// =========================
if (isset($_POST['add_supplier'])) {
    $nama   = trim($_POST['nama_supplier']);
    $alamat = trim($_POST['alamat_supplier']);
    $kontak = trim($_POST['no_telp']);

    if ($nama !== "") {
        $stmt = $conn->prepare("INSERT INTO supplier (nama_supplier, alamat_supplier, no_telp) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $alamat, $kontak);
        $stmt->execute();
    }
    header("Location: supplier.php");
    exit;
}

// =========================
// BACKEND: UPDATE SUPPLIER
// =========================
if (isset($_POST['edit_supplier'])) {
    $id     = intval($_POST['id_supplier']);
    $nama   = trim($_POST['nama_supplier_edit']);
    $alamat = trim($_POST['alamat_edit']);
    $kontak = trim($_POST['kontak_edit']);

    if ($nama !== "") {
        $stmt = $conn->prepare("UPDATE supplier SET nama_supplier=?, alamat_supplier=?, no_telp=? WHERE id_supplier=?");
        $stmt->bind_param("sssi", $nama, $alamat, $kontak, $id);
        $stmt->execute();
    }

    header("Location: supplier.php");
    exit;
}

// =========================
// BACKEND: HAPUS SUPPLIER
// =========================
if (isset($_POST['delete_supplier'])) {
    $id = intval($_POST['id_supplier_delete']);
    $stmt = $conn->prepare("DELETE FROM supplier WHERE id_supplier=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: supplier.php");
    exit;
}

// =========================
// BACKEND: AMBIL SUPPLIER
// =========================
$supplier = $conn->query("SELECT * FROM supplier ORDER BY id_supplier DESC");
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier — Master Data</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



</head>

<body>

    <div class="d-flex">

        <!-- SIDEBAR -->
        <?php require 'sidebar.php'; ?>

        <!-- MAIN AREA -->
        <div id="mainarea" class="flex-fill">

            <!-- TOPBAR -->
            <header class="bg-white border-bottom py-2">
                <div class="container-fluid d-flex align-items-center gap-2">
                    <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary"><i class="fa fa-bars"></i></button>
                    <h5 class="mb-0">Supplier</h5>
                    <small class="text-muted">Kelola data supplier barang</small>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="container-fluid py-3">

                <div class="card card-ghost p-3">

                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="mb-0"><i class="fa fa-truck"></i> Data Supplier</h6>

                        <!-- BUTTON TAMBAH -->
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="fa fa-plus"></i> Tambah Supplier
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Supplier</th>
                                    <th>Alamat</th>
                                    <th>Kontak</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($supplier->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada supplier.</td>
                                    </tr>
                                    <?php else:
                                    $no = 1;
                                    while ($row = $supplier->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_supplier']); ?></td>
                                            <td><?= htmlspecialchars($row['alamat_supplier']); ?></td>
                                            <td><?= htmlspecialchars($row['no_telp']); ?></td>

                                            <td>
                                                <button class="btn btn-warning btn-sm btnEdit"
                                                    data-id="<?= $row['id_supplier']; ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_supplier']); ?>"
                                                    data-alamat="<?= htmlspecialchars($row['alamat_supplier']); ?>"
                                                    data-kontak="<?= htmlspecialchars($row['no_telp']); ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>

                                                <button class="btn btn-danger btn-sm btnDelete"
                                                    data-id="<?= $row['id_supplier']; ?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                <?php endwhile;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </main>
        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">

                <div class="modal-header">
                    <h6 class="modal-title">Tambah Supplier</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label>Nama Supplier</label>
                    <input type="text" name="nama_supplier" class="form-control mb-2" required>

                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control mb-2"></textarea>

                    <label>Kontak</label>
                    <input type="text" name="kontak" class="form-control">
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" name="add_supplier">Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div class="modal fade" id="modalEdit">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">

                <input type="hidden" name="id_supplier" id="edit_id">

                <div class="modal-header">
                    <h6 class="modal-title">Edit Supplier</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <label>Nama Supplier</label>
                    <input type="text" id="edit_nama" name="nama_supplier_edit" class="form-control mb-2" required>

                    <label>Alamat</label>
                    <textarea id="edit_alamat" name="alamat_edit" class="form-control mb-2"></textarea>

                    <label>Kontak</label>
                    <input type="text" id="edit_kontak" name="kontak_edit" class="form-control">

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-warning" name="edit_supplier">Update</button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL DELETE -->
    <div class="modal fade" id="modalDelete">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">

                <input type="hidden" name="id_supplier_delete" id="delete_id">

                <div class="modal-header">
                    <h6 class="modal-title text-danger">Hapus Supplier</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Yakin ingin menghapus supplier ini?
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger" name="delete_supplier">Hapus</button>
                </div>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ------------------------
        // MODAL EDIT
        // ------------------------
        document.querySelectorAll('.btnEdit').forEach(btn => {
            btn.onclick = () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_nama').value = btn.dataset.nama;
                document.getElementById('edit_alamat').value = btn.dataset.alamat;
                document.getElementById('edit_kontak').value = btn.dataset.kontak;

                new bootstrap.Modal(document.getElementById('modalEdit')).show();
            };
        });

        // ------------------------
        // MODAL DELETE
        // ------------------------
        document.querySelectorAll('.btnDelete').forEach(btn => {
            btn.onclick = () => {
                document.getElementById('delete_id').value = btn.dataset.id;
                new bootstrap.Modal(document.getElementById('modalDelete')).show();
            };
        });
    </script>
    

</body>

</html>