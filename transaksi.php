<?php
// transaksi.php
session_start();
include 'db.php'; // pastikan $conn tersedia

// Jika perlu redirect bila belum login, aktifkan baris ini:
// if (!isset($_SESSION['id_user'])) { header("Location: login.php"); exit; }

// Validasi role: hanya 'admin' dan 'staff' yang valid. Default = 'staff'
$valid_roles = ['admin', 'staff'];
$myrole = (isset($_SESSION['role']) && in_array($_SESSION['role'], $valid_roles, true))
    ? $_SESSION['role']
    : 'staff';

// Buat CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Query UNION: ambil data transaksi masuk & keluar, sertakan kolom status
$query = "
    SELECT 
        bm.id_masuk AS id_transaksi, 
        'masuk' AS jenis, 
        bm.tanggal_masuk AS tanggal, 
        b.nama_barang, 
        bm.jumlah_masuk AS jumlah, 
        u.username AS petugas,
        bm.status AS status,
        bm.asal AS info_tambahan
    FROM barang_masuk bm
    JOIN barang b ON bm.id_barang = b.id_barang
    LEFT JOIN `user` u ON bm.id_user = u.id_user

    UNION ALL

    SELECT 
        bk.id_keluar AS id_transaksi, 
        'keluar' AS jenis, 
        bk.tanggal_keluar AS tanggal, 
        b.nama_barang, 
        bk.jumlah_keluar AS jumlah, 
        u.username AS petugas,
        bk.status AS status,
        t.nama_tujuan AS info_tambahan
    FROM barang_keluar bk
    JOIN barang b ON bk.id_barang = b.id_barang
    LEFT JOIN `user` u ON bk.id_user = u.id_user
    LEFT JOIN tujuan t ON bk.id_tujuan = t.id_tujuan

    ORDER BY tanggal DESC
";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// ambil filter
$f_mulai   = $_GET['tgl_mulai'] ?? '';
$f_akhir   = $_GET['tgl_akhir'] ?? '';
$f_jenis   = $_GET['jenis'] ?? '';

// logika default: jika semua kosong, tampilkan semua
$conditions = [];
if (!empty($f_mulai)) $conditions[] = "tanggal >= '$f_mulai'";
if (!empty($f_akhir)) $conditions[] = "tanggal <= '$f_akhir'";
if (!empty($f_jenis)) $conditions[] = "jenis = '$f_jenis'";

$sql_where = '';
if (count($conditions) > 0) {
    $sql_where = "WHERE " . implode(' AND ', $conditions);
}

// query UNION tetap sama
$query = "
SELECT * FROM (
    SELECT bm.id_masuk AS id_transaksi, 'masuk' AS jenis, bm.tanggal_masuk AS tanggal,
           b.nama_barang, bm.jumlah_masuk AS jumlah, u.username AS petugas, bm.status, bm.asal AS info_tambahan
    FROM barang_masuk bm
    JOIN barang b ON bm.id_barang = b.id_barang
    LEFT JOIN `user` u ON bm.id_user = u.id_user

    UNION ALL

    SELECT bk.id_keluar AS id_transaksi, 'keluar' AS jenis, bk.tanggal_keluar AS tanggal,
           b.nama_barang, bk.jumlah_keluar AS jumlah, u.username AS petugas, bk.status, t.nama_tujuan AS info_tambahan
    FROM barang_keluar bk
    JOIN barang b ON bk.id_barang = b.id_barang
    LEFT JOIN `user` u ON bk.id_user = u.id_user
    LEFT JOIN tujuan t ON bk.id_tujuan = t.id_tujuan
) AS tabel_gabungan
$sql_where
ORDER BY tanggal DESC
";

$result = mysqli_query($conn, $query);
if (!$result) die("Query Error: " . mysqli_error($conn));
?>


<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Transaksi - Indotar Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .table tbody td {
            border-bottom: 2px solid #000;
            vertical-align: middle;
        }

        .opacity-75 {
            opacity: .75;
        }

        .small-badge {
            font-size: .8rem;
            padding: .35rem .5rem;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <div class="sidebar-wrapper">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="main-content p-4 w-100">
            <header class="bg-white border-bottom py-2">
                <div class="container-fluid d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary" title="Toggle sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                        <h5 class="mb-0">Transaksi</h5>
                        <small class="text-muted">Kelola Barang Masuk & Keluar.</small>
                    </div>
                </div>
            </header>

            <div class="table-container">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTransaksi">
                        <i class="fas fa-plus me-2"></i> Ajuan Transaksi Baru
                    </button>
                </div>
                <form action="" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                    <div>
                        <input type="date" name="tgl_mulai" class="form-control filter-input"
                            value="<?= htmlspecialchars($f_mulai) ?>" placeholder="Tanggal Awal">
                    </div>
                    <div>
                        <input type="date" name="tgl_akhir" class="form-control filter-input"
                            value="<?= htmlspecialchars($f_akhir) ?>" placeholder="Tanggal Akhir">
                    </div>
                    <div style="width: 80px;">
                        <select name="jenis" class="form-select filter-input fw-bold">
                            <option value="">All</option>
                            <option value="masuk" <?= ($f_jenis === 'masuk') ? 'selected' : '' ?>>IN</option>
                            <option value="keluar" <?= ($f_jenis === 'keluar') ? 'selected' : '' ?>>OUT</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                    <a href="transaksi.php" class="btn btn-secondary">Clear Filter</a>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover text-center">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Info</th>
                                <th>Pengaju</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                // sanitize
                                $id_trans = (int)$row['id_transaksi'];
                                $jenis_raw = $row['jenis'];
                                $jenis = ($jenis_raw === 'masuk') ? 'masuk' : 'keluar';
                                $tanggal = $row['tanggal'];
                                $nama_barang = htmlspecialchars($row['nama_barang']);
                                $jumlah = (int)$row['jumlah'];
                                $info = htmlspecialchars($row['info_tambahan'] ?? '-');
                                $petugas = htmlspecialchars($row['petugas'] ?? '-');
                                $status = $row['status'] ?? 'pending';

                                //build status HTML defensively: if admin -> select (disabled when not pending)
                                if ($myrole === 'admin') {
                                    $is_pending = ($status === 'pending');
                                    $disabled_attr = $is_pending ? '' : ' disabled';
                                    $selected_pending = ($status === 'pending') ? ' selected' : '';
                                    $selected_approved = ($status === 'approved') ? ' selected' : '';
                                    $selected_reject = ($status === 'reject') ? ' selected' : '';

                                    // include data-current (server authoritative)
                                    $status_html = '<select class="form-select form-select-sm change-status" data-id="' . $id_trans
                                        . '" data-jenis="' . $jenis . '" data-current="' . htmlspecialchars($status) . '"' . $disabled_attr . '>'
                                        . '<option value="pending"' . $selected_pending . '>pending</option>'
                                        . '<option value="approved"' . $selected_approved . '>approved</option>'
                                        . '<option value="reject"' . $selected_reject . '>reject</option>'
                                        . '</select>';
                                } else {
                                    // staff: show badge
                                    if ($status === 'pending') {
                                        $status_html = '<span class="badge bg-warning text-dark small-badge">Pending</span>';
                                    } elseif ($status === 'approved') {
                                        $status_html = '<span class="badge bg-success small-badge">Approved</span>';
                                    } else {
                                        $status_html = '<span class="badge bg-danger small-badge">Rejected</span>';
                                    }
                                }

                                // ... di dalam while loop ...

                                $status = $row['status']; // Nilai dari database: pending, approved, atau reject

                                // Tentukan tampilan HTML berdasarkan Role dan Status
                                if ($myrole === 'admin') {
                                    // KONDISI KHUSUS ADMIN
                                    if ($status === 'pending') {
                                        // Jika masih PENDING, Admin bisa melihat Dropdown untuk memilih aksi
                                        $status_html = '
                                        <select class="form-select form-select-sm change-status" 
                data-id="' . $id_trans . '" 
                data-jenis="' . $jenis . '" 
                data-current="pending">
            <option value="pending" selected>Pending</option>
            <option value="approved">Approve</option>
            <option value="reject">Reject</option>
        </select>';
                                    } elseif ($status === 'approved') {
                                        // Jika sudah APPROVED, Admin melihat Badge Hijau (Permanen, tidak bisa diubah lagi lewat sini)
                                        $status_html = '<span class="badge bg-success small-badge"><i class="fas fa-check me-1"></i> Approved</span>';
                                    } else {
                                        // Jika REJECT, Admin melihat Badge Merah
                                        $status_html = '<span class="badge bg-danger small-badge"><i class="fas fa-times me-1"></i> Rejected</span>';
                                    }
                                } else {
                                    // KONDISI UNTUK STAFF (Hanya melihat Badge)
                                    if ($status === 'pending') {
                                        $status_html = '<span class="badge bg-warning text-dark small-badge">Pending</span>';
                                    } elseif ($status === 'approved') {
                                        $status_html = '<span class="badge bg-success small-badge">Approved</span>';
                                    } else {
                                        $status_html = '<span class="badge bg-danger small-badge">Rejected</span>';
                                    }
                                }

                                // ... lanjutkan echo <tr> seperti biasa ...
                                $jenis_badge = ($jenis === 'masuk')
                                    ? '<span class="badge bg-primary small-badge">IN</span>'
                                    : '<span class="badge bg-danger small-badge">OUT</span>';

                                echo "<tr>
                                    <td>{$no}</td>
                                    <td>" . htmlspecialchars(date('d-m-Y', strtotime($tanggal))) . "</td>
                                    <td>{$nama_barang}</td>
                                    <td>{$jenis_badge}</td>
                                    <td>{$jumlah}</td>
                                    <td>{$info}</td>
                                    <td>{$petugas}</td>
                                    <td>{$status_html}</td>
                                  </tr>";
                                $no++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Form Ajuan Transaksi -->
    <div class="modal fade" id="modalTambahTransaksi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Form Ajuan Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="proses_tambah_transaksi.php" method="POST" id="formAjuan">
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <label class="form-label fw-bold d-block">Jenis Transaksi</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="jenis_transaksi" id="radioMasuk" value="masuk" checked>
                                <label class="btn btn-outline-primary" for="radioMasuk" onclick="toggleForm('masuk')">Barang Masuk (IN)</label>

                                <input type="radio" class="btn-check" name="jenis_transaksi" id="radioKeluar" value="keluar">
                                <label class="btn btn-outline-danger" for="radioKeluar" onclick="toggleForm('keluar')">Barang Keluar (OUT)</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" required value="<?= date('Y-m-d'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Barang</label>
                            <select class="form-select" name="id_barang" required>
                                <option selected disabled value="">-- Pilih Barang --</option>
                                <?php
                                $q_barang = mysqli_query($conn, "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang ASC");
                                if ($q_barang) {
                                    while ($b = mysqli_fetch_assoc($q_barang)) {
                                        echo "<option value='" . (int)$b['id_barang'] . "'>" . htmlspecialchars($b['nama_barang']) . "</option>";
                                    }
                                } else {
                                    echo "<option disabled>Error: " . htmlspecialchars(mysqli_error($conn)) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" class="form-control" name="jumlah" min="1" required placeholder="0">
                        </div>

                        <!-- Field MASUK -->
                        <div id="field-masuk">
                            <div class="mb-3">
                                <label class="form-label">Asal Barang (Sumber)</label>
                                <select class="form-select" name="asal" id="inputAsal">
                                    <option value="Pembelian">Pembelian</option>
                                    <option value="Retur">Retur</option>
                                </select>
                            </div>
                        </div>

                        <!-- Field KELUAR -->
                        <div id="field-keluar" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Tujuan Barang (Divisi/Lokasi)</label>
                                <select class="form-select" name="id_tujuan" id="inputTujuan">
                                    <option selected disabled value="">-- Pilih Tujuan --</option>
                                    <?php
                                    $q_tujuan = mysqli_query($conn, "SELECT id_tujuan, nama_tujuan FROM tujuan ORDER BY nama_tujuan ASC");
                                    if ($q_tujuan) {
                                        while ($t = mysqli_fetch_assoc($q_tujuan)) {
                                            echo "<option value=\"" . (int)$t['id_tujuan'] . "\">" . htmlspecialchars($t['nama_tujuan']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status (dikirim, tapi server akan enforce default/policy) -->
                        <div class="mb-3">
                            <label class="form-label">Status Ajuan</label>
                            <select class="form-select" name="status" required>
                                <option value="pending" selected>pending</option>
                                <option value="approved">approved</option>
                                <option value="reject">reject</option>
                            </select>
                            <div class="form-text">Status default 'pending'. Hanya admin yang dapat mengubahnya lewat verifikasi.</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark">Ajukan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS (Bootstrap + logic) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // toggle form fields between Masuk / Keluar
        function toggleForm(jenis) {
            const fieldMasuk = document.getElementById('field-masuk');
            const fieldKeluar = document.getElementById('field-keluar');
            const inputAsal = document.getElementById('inputAsal');
            const inputTujuan = document.getElementById('inputTujuan');

            if (!fieldMasuk || !fieldKeluar) return;

            if (jenis === 'masuk') {
                fieldMasuk.style.display = 'block';
                fieldKeluar.style.display = 'none';
                if (inputAsal) inputAsal.required = true;
                if (inputTujuan) {
                    inputTujuan.required = false;
                    inputTujuan.value = "";
                }
                document.getElementById('radioMasuk').checked = true;
            } else {
                fieldMasuk.style.display = 'none';
                fieldKeluar.style.display = 'block';
                if (inputAsal) inputAsal.required = false;
                if (inputTujuan) inputTujuan.required = true;
                document.getElementById('radioKeluar').checked = true;
            }
        }

        // helper: make select readonly-like
        function makeReadonly(el) {
            el.disabled = true;
            el.classList.add('opacity-75');
            el.setAttribute('aria-disabled', 'true');
        }

        function makeEditable(el) {
            el.disabled = false;
            el.classList.remove('opacity-75');
            el.removeAttribute('aria-disabled');
        }

        // init status select behavior
        (function() {
            function initStatusSelects() {
                const selects = Array.from(document.querySelectorAll('.change-status'));
                selects.forEach((sel) => {
                    const id = sel.dataset.id;
                    const jenis = sel.dataset.jenis;
                    if (!id || !jenis) return;

                    // If server-side already set non-pending, it's disabled by server HTML.
                    // But ensure if value != pending we keep it readonly.
                    if (sel.value !== 'pending') {
                        makeReadonly(sel);
                        return;
                    } else {
                        makeEditable(sel);
                    }

                    // attach change handler
                    sel.addEventListener('change', async function() {
                        const newStatus = this.value;
                        const idTrans = this.dataset.id;
                        const jenisTrans = this.dataset.jenis;

                        if (!confirm(`Ubah status transaksi #${idTrans} menjadi "${newStatus}"?`)) {
                            // revert to pending display (safest)
                            this.value = 'pending';
                            return;
                        }

                        // optimistic UI lock
                        makeReadonly(this);

                        try {
                            const resp = await fetch('update_status.php', {
                                method: 'POST',
                                credentials: 'same-origin', // ensure session cookie dikirim
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    id_transaksi: idTrans,
                                    jenis: jenisTrans,
                                    status: newStatus
                                })
                            });

                            const data = await resp.json();

                            if (!resp.ok || !data.success) {
                                alert('Gagal mengubah status: ' + (data.message || resp.statusText || 'unknown'));
                                makeEditable(this);
                                if (data.status) this.value = data.status;
                                return;
                            }

                            // Success: use server-authoritative status
                            const serverStatus = (typeof data.status === 'string') ? data.status : newStatus;

                            // Replace select with badge for clarity (prevents accidental edits)
                            let badgeHtml = '';
                            if (serverStatus === 'pending') badgeHtml = '<span class="badge bg-warning text-dark small-badge">Pending</span>';
                            else if (serverStatus === 'approved') badgeHtml = '<span class="badge bg-success small-badge">Approved</span>';
                            else badgeHtml = '<span class="badge bg-danger small-badge">Rejected</span>';

                            const wrapper = document.createElement('div');
                            wrapper.innerHTML = badgeHtml;
                            sel.parentNode.replaceChild(wrapper.firstChild, sel);

                            if (data.stok !== undefined) {
                                alert(`Status diubah menjadi "${serverStatus}". Stok sekarang: ${data.stok}.`);
                            } else {
                                alert('Status berhasil diubah.');
                            }
                        } catch (err) {
                            console.error('update_status error:', err);
                            alert('Terjadi kesalahan saat menghubungi server.');
                            makeEditable(this);
                        }
                    });
                });
            }

            // init on DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                // initial toggle based on selected radio
                const checked = document.querySelector('input[name="jenis_transaksi"]:checked');
                toggleForm(checked ? checked.value : 'masuk');

                // ensure toggle when modal opens
                const modal = document.getElementById('modalTambahTransaksi');
                if (modal) {
                    modal.addEventListener('show.bs.modal', function() {
                        const checkedInside = document.querySelector('#modalTambahTransaksi input[name="jenis_transaksi"]:checked');
                        toggleForm(checkedInside ? checkedInside.value : 'masuk');
                    });
                }

                initStatusSelects();
            });
        })();
    </script>
</body>

</html>