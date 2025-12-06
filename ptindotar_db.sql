-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2025 at 07:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ptindotar_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `analytic_results`
--

CREATE TABLE `analytic_results` (
  `id_results` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `computed_by` int(11) DEFAULT NULL,
  `computed_at` datetime DEFAULT current_timestamp(),
  `max_stock` int(50) DEFAULT 0,
  `min_stock` int(50) DEFAULT 0,
  `safety_stock` int(50) DEFAULT 0,
  `reorder_point` int(50) DEFAULT 0,
  `eoq` int(50) DEFAULT 0,
  `total_biaya_ops` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analytic_results`
--

INSERT INTO `analytic_results` (`id_results`, `id_barang`, `computed_by`, `computed_at`, `max_stock`, `min_stock`, `safety_stock`, `reorder_point`, `eoq`, `total_biaya_ops`) VALUES
(1, 3, NULL, '2025-12-06 08:45:08', 279, 11, -4, 11, 268, 13416),
(3, 3, 1, '2025-12-06 08:56:47', 279, 11, -4, 11, 268, 13416),
(4, 3, 1, '2025-12-06 09:17:46', 279, 11, -4, 11, 268, 13416),
(5, 3, 1, '2025-12-06 09:20:05', 279, 11, -4, 11, 268, 13416);

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `satuan` varchar(10) DEFAULT NULL,
  `harga_beli` decimal(18,2) DEFAULT 0.00,
  `harga_jual` decimal(18,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stok` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `id_kategori`, `id_supplier`, `nama_barang`, `satuan`, `harga_beli`, `harga_jual`, `created_at`, `stok`) VALUES
(1, 2, 4, 'HVS 7gr', 'Unit', 45000.00, 50000.00, '2025-12-04 02:40:10', 100),
(2, 2, 5, 'SPIDOL', 'Pcs', 2000.00, 5000.00, '2025-12-05 12:00:03', 150),
(3, 3, 6, 'Pensil 2B', 'Pcs', 5000.00, 7000.00, '2025-12-05 15:30:23', 70);

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id_keluar` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `requested_by` varchar(255) DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `jumlah_keluar` bigint(20) NOT NULL DEFAULT 0,
  `tanggal_keluar` date DEFAULT current_timestamp(),
  `id_tujuan` int(10) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `petugas` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','reject') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_keluar`
--

INSERT INTO `barang_keluar` (`id_keluar`, `id_barang`, `id_user`, `requested_by`, `requested_at`, `jumlah_keluar`, `tanggal_keluar`, `id_tujuan`, `verified_by`, `verified_at`, `id_supplier`, `petugas`, `created_at`, `status`) VALUES
(3, 2, 1, NULL, '2025-12-05 20:21:35', 10, '2025-12-05', 1, NULL, NULL, NULL, NULL, '2025-12-05 13:21:35', 'approved'),
(4, 2, 1, NULL, '2025-12-05 21:07:24', 5, '2025-12-05', 1, NULL, NULL, NULL, NULL, '2025-12-05 14:07:24', 'approved'),
(5, 2, 1, NULL, '2025-12-06 03:41:10', 10, '2025-12-05', 3, NULL, NULL, NULL, NULL, '2025-12-05 20:41:10', 'approved'),
(6, 1, 1, NULL, '2025-12-06 05:34:18', 5, '2025-12-05', 3, NULL, NULL, NULL, NULL, '2025-12-05 22:34:18', 'approved'),
(7, 2, 1, NULL, '2025-12-06 10:01:14', 20, '2025-12-06', 3, NULL, NULL, NULL, NULL, '2025-12-06 03:01:14', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id_masuk` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `received_by` varchar(255) DEFAULT NULL,
  `jumlah_masuk` int(10) NOT NULL DEFAULT 0,
  `tanggal_masuk` date DEFAULT current_timestamp(),
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') DEFAULT NULL,
  `asal` enum('Retur','Pembelian') DEFAULT NULL,
  `verified_by` varchar(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('pending','approved','reject') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_masuk`
--

INSERT INTO `barang_masuk` (`id_masuk`, `id_barang`, `id_supplier`, `id_user`, `received_by`, `jumlah_masuk`, `tanggal_masuk`, `kondisi`, `asal`, `verified_by`, `verified_at`, `created_at`, `Status`) VALUES
(10, 1, NULL, 1, NULL, 10, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 11:02:37', 'reject'),
(11, 2, NULL, 1, NULL, 100, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 12:42:09', 'reject'),
(12, 1, NULL, 1, NULL, 50, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 12:43:35', 'approved'),
(13, 3, NULL, 1, NULL, 10, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 15:30:55', 'approved'),
(14, 3, NULL, 1, NULL, 50, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 20:28:45', 'reject'),
(15, 3, NULL, 1, NULL, 50, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 20:44:27', 'approved'),
(16, 3, NULL, 1, NULL, 10, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 22:13:13', 'approved'),
(17, 1, NULL, 1, NULL, 10, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 22:14:08', 'reject'),
(18, 1, NULL, 1, NULL, 10, '2025-12-05', NULL, 'Pembelian', NULL, NULL, '2025-12-05 22:20:06', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(2, 'Slow Moving'),
(3, 'Medium Moving'),
(4, 'Slow Moving');

-- --------------------------------------------------------

--
-- Table structure for table `parameter_produk`
--

CREATE TABLE `parameter_produk` (
  `id_parameter` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `service_level` decimal(5,4) DEFAULT 0.9500,
  `standar_deviasi` decimal(18,6) DEFAULT 0.000000,
  `lead_time` int(11) DEFAULT 0,
  `permintaan_harian` decimal(18,4) DEFAULT 0.0000,
  `biaya_pemesanan` decimal(18,2) DEFAULT 0.00,
  `jml_hari_aktif` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parameter_produk`
--

INSERT INTO `parameter_produk` (`id_parameter`, `id_barang`, `service_level`, `standar_deviasi`, `lead_time`, `permintaan_harian`, `biaya_pemesanan`, `jml_hari_aktif`) VALUES
(2, 3, 0.9000, 1.000000, 3, 5.0000, 1000.00, 360);

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(255) NOT NULL,
  `alamat_supplier` text DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `alamat_supplier`, `no_telp`, `created_at`) VALUES
(1, 'SIDU', 'jakarta', '0800', '2025-11-28 15:17:35'),
(4, 'Kiky', 'Indramayu', '700', '2025-12-03 23:40:59'),
(5, 'snowman', 'yogya', '0801', '2025-12-05 11:59:34'),
(6, 'Faber Caster', 'semarang', '083100', '2025-12-05 15:29:27');

-- --------------------------------------------------------

--
-- Table structure for table `tujuan`
--

CREATE TABLE `tujuan` (
  `id_tujuan` int(11) NOT NULL,
  `nama_tujuan` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tujuan`
--

INSERT INTO `tujuan` (`id_tujuan`, `nama_tujuan`, `alamat`, `created_at`) VALUES
(1, 'SMAN 1 Kuningan', 'Kuningan', '2025-11-28 22:40:42'),
(3, 'MIN 2 Kuningan', 'Jalaksana', '2025-12-05 15:29:51');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('staff','admin','owner') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$bHF33RXj1PVfyOBQPUECFuXmtNmo3y4E3Ujvr4r9SFCUX4dudt0z.', NULL, 'admin', '2025-11-27 16:35:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analytic_results`
--
ALTER TABLE `analytic_results`
  ADD PRIMARY KEY (`id_results`),
  ADD KEY `fk_analytic_barang` (`id_barang`),
  ADD KEY `fk_analytic_user` (`computed_by`);

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`),
  ADD KEY `fk_barang_kategori` (`id_kategori`),
  ADD KEY `fk_barang_supplier` (`id_supplier`);

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id_keluar`),
  ADD KEY `idx_keluar_idbarang` (`id_barang`),
  ADD KEY `tujuan_fk` (`id_tujuan`),
  ADD KEY `fk_keluar_user` (`id_user`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id_masuk`),
  ADD KEY `fk_masuk_supplier` (`id_supplier`),
  ADD KEY `idx_masuk_idbarang` (`id_barang`),
  ADD KEY `fk_masuk_user` (`id_user`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `parameter_produk`
--
ALTER TABLE `parameter_produk`
  ADD PRIMARY KEY (`id_parameter`),
  ADD KEY `idx_param_idbarang` (`id_barang`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `tujuan`
--
ALTER TABLE `tujuan`
  ADD PRIMARY KEY (`id_tujuan`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analytic_results`
--
ALTER TABLE `analytic_results`
  MODIFY `id_results` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id_keluar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `parameter_produk`
--
ALTER TABLE `parameter_produk`
  MODIFY `id_parameter` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tujuan`
--
ALTER TABLE `tujuan`
  MODIFY `id_tujuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analytic_results`
--
ALTER TABLE `analytic_results`
  ADD CONSTRAINT `fk_analytic_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_analytic_user` FOREIGN KEY (`computed_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_barang_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `fk_keluar_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_keluar_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tujuan_fk` FOREIGN KEY (`id_tujuan`) REFERENCES `tujuan` (`id_tujuan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `fk_masuk_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_masuk_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_masuk_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `parameter_produk`
--
ALTER TABLE `parameter_produk`
  ADD CONSTRAINT `fk_param_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
