<?php
require_once "db.php";
require_once "auth_check.php";

require_login();
require_role(['admin']); // hanya admin yang boleh CRUD kategori

// =========================
// BACKEND: TAMBAH KATEGORI
// =========================
if (isset($_POST['add_kategori'])) {
    $nama = trim($_POST['nama_kategori']);

    if ($nama !== "") {
        $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
    }
    header("Location: kategori.php");
    exit;
}

// =========================
// BACKEND: UPDATE KATEGORI
// =========================
if (isset($_POST['edit_kategori'])) {
    $id = intval($_POST['id_kategori']);
    $nama = trim($_POST['nama_kategori_edit']);

    if ($nama !== "") {
        $stmt = $conn->prepare("UPDATE kategori SET nama_kategori=? WHERE id_kategori=?");
        $stmt->bind_param("si", $nama, $id);
        $stmt->execute();
    }
    header("Location: kategori.php");
    exit;
}

// =========================
// BACKEND: HAPUS KATEGORI
// =========================
if (isset($_POST['delete_kategori'])) {
    $id = intval($_POST['id_kategori_delete']);
    $stmt = $conn->prepare("DELETE FROM kategori WHERE id_kategori=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: kategori.php");
    exit;
}

// =========================
// BACKEND: AMBIL DATA KATEGORI
// =========================
$kategori = $conn->query("SELECT * FROM kategori ORDER BY id_kategori DESC");
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori — Master Data</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="d-flex">

        <!-- SIDEBAR -->
        <?php include "sidebar.php"; ?>

        <!-- MAIN AREA -->
        <div id="mainarea" class="flex-fill">

            <!-- TOPBAR -->
            <header class="bg-white border-bottom py-2">
                <div class="container-fluid d-flex align-items-center gap-2">
                    <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary"><i class="fa fa-bars"></i></button>
                    <h5 class="mb-0">Kategori</h5>
                    <small class="text-muted">Kelola kategori barang</small>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="container-fluid py-3">

                <div class="card card-ghost p-3">

                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="mb-0"><i class="fa fa-tags"></i> Data Kategori</h6>

                        <!-- BUTTON TAMBAH -->
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="fa fa-plus"></i> Tambah Kategori
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Kategori</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($kategori->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada kategori.</td>
                                    </tr>
                                    <?php else:
                                    $no = 1;
                                    while ($row = $kategori->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                                            <td>
                                                <button
                                                    class="btn btn-warning btn-sm btnEdit"
                                                    data-id="<?= $row['id_kategori']; ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_kategori']); ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>

                                                <button
                                                    class="btn btn-danger btn-sm btnDelete"
                                                    data-id="<?= $row['id_kategori']; ?>">
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


    <!-- ================================================
     MODAL TAMBAH
================================================ -->
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Kategori</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label>Nama Kategori</label>
                    <input type="text" class="form-control" name="nama_kategori" required>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" name="add_kategori">Simpan</button>
                </div>
            </form>
        </div>
    </div>


    <!-- ================================================
     MODAL EDIT
================================================ -->
    <div class="modal fade" id="modalEdit">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">

                <input type="hidden" name="id_kategori" id="edit_id">

                <div class="modal-header">
                    <h6 class="modal-title">Edit Kategori</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label>Nama Kategori</label>
                    <input type="text" class="form-control" name="nama_kategori_edit" id="edit_nama" required>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-warning" name="edit_kategori">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================
     MODAL DELETE
================================================ -->
    <div class="modal fade" id="modalDelete">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">

                <input type="hidden" name="id_kategori_delete" id="delete_id">

                <div class="modal-header">
                    <h6 class="modal-title text-danger">Hapus Kategori</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Yakin ingin menghapus kategori ini?
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger" name="delete_kategori">Hapus</button>
                </div>

            </form>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>