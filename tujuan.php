<?php
// tujuan.php
require_once "db.php";
require_once "auth_check.php";

require_login();
require_role(['admin']); // ubah sesuai kebutuhan

// =========================
// BACKEND: TAMBAH TUJUAN
// =========================
if (isset($_POST['add_tujuan'])) {
    $nama = trim($_POST['nama_tujuan']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama !== "") {
        $stmt = $conn->prepare("INSERT INTO tujuan (nama_tujuan, keterangan) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama, $keterangan);
        $stmt->execute();
    }
    header("Location: tujuan.php");
    exit;
}

// =========================
// BACKEND: UPDATE TUJUAN
// =========================
if (isset($_POST['edit_tujuan'])) {
    $id = intval($_POST['id_tujuan']);
    $nama = trim($_POST['nama_tujuan_edit']);
    $keterangan = trim($_POST['keterangan_edit']);

    if ($nama !== "") {
        $stmt = $conn->prepare("UPDATE tujuan SET nama_tujuan = ?, keterangan = ? WHERE id_tujuan = ?");
        $stmt->bind_param("ssi", $nama, $keterangan, $id);
        $stmt->execute();
    }
    header("Location: tujuan.php");
    exit;
}

// =========================
// BACKEND: HAPUS TUJUAN
// =========================
if (isset($_POST['delete_tujuan'])) {
    $id = intval($_POST['id_tujuan_delete']);
    $stmt = $conn->prepare("DELETE FROM tujuan WHERE id_tujuan = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: tujuan.php");
    exit;
}

// =========================
// BACKEND: AMBIL DATA TUJUAN
// =========================
$tujuan_q = $conn->query("SELECT * FROM tujuan ORDER BY id_tujuan DESC");
$current = current_user();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tujuan — Master Data</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* gunakan file css utama jika sudah ada, fallback minimal di sini */
        <?php if (file_exists('style_masterdata_sidebar.css')) {
            echo file_get_contents('style_masterdata_sidebar.css');
        } else { ?>
        :root {
            --sidebar-w: 250px; --sidebar-collapsed-w:64px; --soft:#f8fafc;
        }
        body { background: var(--soft); }
        #sidebar { width: var(--sidebar-w); min-height:100vh; background:#001f3f; color:#fff; }
        #mainarea { margin-left: var(--sidebar-w); }
        .card-ghost { background:#fff; border-radius:12px; box-shadow:0 6px 22px rgba(15,23,42,0.06); }
        <?php } ?>
    </style>
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
                <h5 class="mb-0">Tujuan</h5>
                <small class="text-muted">Kelola tujuan / lokasi pengiriman</small>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="container-fluid py-3">

            <div class="card card-ghost p-3">
                <div class="d-flex justify-content-between mb-3">
                    <h6 class="mb-0"><i class="fa fa-map-marker-alt"></i> Data Tujuan</h6>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fa fa-plus"></i> Tambah Tujuan
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Tujuan</th>
                                <th>Keterangan</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tujuan_q->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">Belum ada data tujuan.</td></tr>
                            <?php else:
                                $no = 1;
                                while ($row = $tujuan_q->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= htmlspecialchars($row['nama_tujuan']); ?></td>
                                        <td><?= htmlspecialchars($row['keterangan']); ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm btnEdit"
                                                    data-id="<?= $row['id_tujuan']; ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_tujuan']); ?>"
                                                    data-keterangan="<?= htmlspecialchars($row['keterangan']); ?>">
                                                <i class="fa fa-edit"></i>
                                            </button>

                                            <button class="btn btn-danger btn-sm btnDelete"
                                                    data-id="<?= $row['id_tujuan']; ?>">
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

        <footer class="py-3 text-muted small text-center border-top">
            © PT Indotar Gentra Raya
        </footer>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Tambah Tujuan</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label>Nama Tujuan</label>
                <input type="text" name="nama_tujuan" class="form-control mb-2" required>
                <label>Keterangan (opsional)</label>
                <textarea name="keterangan" class="form-control mb-2" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" name="add_tujuan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id_tujuan" id="edit_id">
            <div class="modal-header">
                <h6 class="modal-title">Edit Tujuan</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label>Nama Tujuan</label>
                <input type="text" id="edit_nama" name="nama_tujuan_edit" class="form-control mb-2" required>
                <label>Keterangan (opsional)</label>
                <textarea id="edit_keterangan" name="keterangan_edit" class="form-control mb-2" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-warning" name="edit_tujuan">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DELETE -->
<div class="modal fade" id="modalDelete">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id_tujuan_delete" id="delete_id">
            <div class="modal-header">
                <h6 class="modal-title text-danger">Hapus Tujuan</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Yakin ingin menghapus tujuan ini?
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger" name="delete_tujuan">Hapus</button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// buka modal edit
document.querySelectorAll('.btnEdit').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_nama').value = btn.dataset.nama;
        document.getElementById('edit_keterangan').value = btn.dataset.keterangan ?? '';
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    });
});

// buka modal delete
document.querySelectorAll('.btnDelete').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('delete_id').value = btn.dataset.id;
        new bootstrap.Modal(document.getElementById('modalDelete')).show();
    });
});
</script>

<!-- include sidebar JS once (pastikan tidak di-include dua kali) -->
<?php if (file_exists('sidebar_js.js')): ?>
<script src="sidebar_js.js"></script>
<?php endif; ?>

</body>
</html>
