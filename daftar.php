<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar User</title>
</head>
<body>

    <!-- SIDEBAR -->
    <aside>
        <!-- logo -->
        <!-- menu navigasi -->
    </aside>

    <!-- MAIN AREA -->
    <main>

        <!-- HEADER HALAMAN -->
        <header>
            <h1>Daftar User</h1>
            <p>Kelola akun pengguna sistem</p>

            <div class="actions">
                <button>Tambah User</button>
                <button>Export CSV</button>
            </div>
        </header>

        <!-- FORM PENCARIAN -->
        <section class="search-section">
            <form>
                <input type="text" placeholder="Cari username, nama, email...">
                <button type="submit">Cari</button>
                <button type="reset">Reset</button>
            </form>
        </section>

        <!-- TABEL USER -->
        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>1</td>
                        <td>john</td>
                        <td>John Doe</td>
                        <td>john@mail.com</td>
                        <td>Admin</td>
                        <td>2024-01-01</td>
                        <td>
                            <button>Lihat</button>
                            <button>Edit</button>
                            <button>Hapus</button>
                        </td>
                    </tr>
                    <!-- repeat rows -->
                </tbody>
            </table>
        </section>

        <!-- PAGINATION -->
        <footer class="pagination">
            <div class="range-info">Menampilkan 1–15 dari 120</div>

            <div class="pages">
                <button>Prev</button>
                <button>1</button>
                <button>2</button>
                <button>3</button>
                <button>Next</button>
            </div>
        </footer>

    </main>

</body>
</html>
