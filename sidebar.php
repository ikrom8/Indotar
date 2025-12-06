<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        <?php include "style_sidebar.css"; ?>
    </style>
</head>

<body>


    <!-- SIDEBAR -->
    <nav id="sidebar" class="" aria-label="Sidebar Navigation">
        <div class="brand">
            <div class="brand-icon" title="PT Indotar">
                <img src="image/indotar-logo.png"
                    srcset="image/ogo@2x.png 2x"
                    alt="Logo PT Indotar"
                    loading="lazy"
                    width="36" height="36"
                    onerror="this.style.display='none';">
            </div>
            <div class="brand-text">
                <div style="font-size:14px">PT Indotar</div>
                <small style="opacity:.8">Inventori</small>
            </div>
        </div>

        <ul class="nav flex-column p-2">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard"><i class="fa fa-tachometer-alt"></i> <span class="nav-label">Dashboard</span></a>
            </li>
            <li class="nav-item"><a class="nav-link" href="inventory.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Inventory"><i class="fa fa-boxes"></i> <span class="nav-label">Inventory</span></a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Transaksi"><i class="fa fa-exchange-alt"></i> <span class="nav-label">Transaksi</span></a></li>
            <li class="nav-item"><a class="nav-link" href="master_data.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Master Data"><i class="fa fa-database"></i> <span class="nav-label">Master Data</span></a></li>
            <li class="nav-item"><a class="nav-link" href="analytic.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Analitik"><i class="fa fa-chart-line"></i> <span class="nav-label">Analitik</span></a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Laporan"><i class="fa fa-file-alt"></i> <span class="nav-label">Laporan</span></a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Log Out"><i class="fa fa-sign-out-alt"></i> <span class="nav-label">Log Out</span></a></li>
        </ul>


        <div class="position-absolute bottom-0 w-100 p-2 bottom-info">
            <div class="bottom-inner d-flex align-items-center">
                <div class="user-meta d-flex align-items-center flex-grow-1">
                    <div class="user-avatar me-2" aria-hidden="true">
                        <img src="image/indotar-logo.png" alt="avatar" width="32" height="32" />
                    </div>
                    <div class="user-text">
                        <div class="small text-white-50">Signed in as</div>
                        <div class="username" style="font-weight:600"><?php echo htmlspecialchars($current['username'] ?? 'guest'); ?></div>
                        <div class="role small text-muted"><?php echo htmlspecialchars($current['role'] ?? ''); ?></div>
                    </div>
                </div>

                <div class="user-actions d-flex align-items-center gap-2">
                    <a class="btn btn-sm btn-outline-light action-icon" href="profile.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Profile" aria-label="Profile">
                        <i class="fa fa-user"></i>
                    </a>
                    <a class="btn btn-sm btn-outline-light action-icon" href="logout.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout" aria-label="Logout">
                        <i class="fa fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>

    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="sidebar_js.js" defer></script>

</body>

</html>