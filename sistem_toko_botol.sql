-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2026 at 04:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_toko_botol`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `kode_barang` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(200) DEFAULT NULL,
  `satuan` enum('botol','dus') DEFAULT NULL,
  `harga_beli` decimal(12,2) DEFAULT NULL,
  `harga_jual` decimal(12,2) DEFAULT NULL,
  `stok_minimal` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `kode_barang`, `nama_barang`, `satuan`, `harga_beli`, `harga_jual`, `stok_minimal`, `created_at`) VALUES
(1, 'BTL-001', 'Aqua 600ml', 'botol', 3000.00, 3500.00, 10, '2026-02-08 02:33:27'),
(2, 'BTL-002', 'Le Minerale 600ml', 'botol', 2500.00, 3000.00, 10, '2026-02-08 02:33:27'),
(3, 'BTL-003', 'Vit 600ml', 'botol', 2800.00, 3300.00, 10, '2026-02-08 02:33:27'),
(4, 'BTL-004', 'Cleo 600ml', 'botol', 2700.00, 3200.00, 10, '2026-02-08 02:33:27'),
(5, 'DUS-001', 'Aqua Dus (12 pcs)', 'dus', 30000.00, 36000.00, 10, '2026-02-08 02:33:27'),
(6, 'DUS-002', 'Le Minerale Dus (12 pcs)', 'dus', 25000.00, 30000.00, 10, '2026-02-08 02:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `id_cabang` int(11) NOT NULL,
  `nama_cabang` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cabang`
--

INSERT INTO `cabang` (`id_cabang`, `nama_cabang`, `alamat`, `telepon`, `created_at`) VALUES
(1, 'Cabang Pusat', 'Jl. Pusat No. 1', '021-111111', '2026-02-08 02:33:27'),
(2, 'Cabang Timur', 'Jl. Timur No. 2', '021-222222', '2026-02-08 02:33:27'),
(3, 'Cabang Barat', 'Jl. Barat No. 3', '021-333333', '2026-02-08 02:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id_karyawan` int(11) NOT NULL,
  `nama_karyawan` varchar(100) DEFAULT NULL,
  `id_cabang` int(11) DEFAULT NULL,
  `role` enum('admin','kasir','gudang') DEFAULT 'kasir',
  `last_login` datetime DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id_karyawan`, `nama_karyawan`, `id_cabang`, `role`, `last_login`, `is_admin`, `password`) VALUES
(1, 'Admin Toko', 1, 'admin', '2026-02-08 09:54:49', 1, '$2y$10$PsLmmWHRe2j7RgYLFqkGY.E/dJrrtdAiKFwHDogfcmvht6BNDb.Ay'),
(2, 'Admin Toko', 1, 'admin', NULL, 1, '$2y$10$PsLmmWHRe2j7RgYLFqkGY.E/dJrrtdAiKFwHDogfcmvht6BNDb.Ay');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` varchar(100) NOT NULL,
  `id_karyawan` int(11) DEFAULT NULL,
  `id_cabang` int(11) DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `id_karyawan`, `id_cabang`, `login_time`, `last_activity`, `is_admin`) VALUES
('session_6987faf94546d9.80567508', 1, 1, '2026-02-08 09:54:49', '2026-02-08 09:54:49', 1);

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname`
--

CREATE TABLE `stock_opname` (
  `id_opname` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `id_cabang` int(11) DEFAULT NULL,
  `stok_fisik` int(11) DEFAULT NULL,
  `stok_sistem` int(11) DEFAULT NULL,
  `selisih` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_gudang`
--

CREATE TABLE `stok_gudang` (
  `id_stok_gudang` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `id_cabang` int(11) DEFAULT NULL,
  `stok_fisik` int(11) DEFAULT 0,
  `stok_sistem` int(11) DEFAULT 0,
  `tanggal_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stok_gudang`
--

INSERT INTO `stok_gudang` (`id_stok_gudang`, `id_barang`, `id_cabang`, `stok_fisik`, `stok_sistem`, `tanggal_update`) VALUES
(1, 1, 1, 100, 100, '2026-02-08'),
(2, 1, 2, 100, 100, '2026-02-08'),
(3, 1, 3, 100, 100, '2026-02-08'),
(4, 2, 1, 100, 100, '2026-02-08'),
(5, 2, 2, 100, 100, '2026-02-08'),
(6, 2, 3, 100, 100, '2026-02-08'),
(7, 3, 1, 100, 100, '2026-02-08'),
(8, 3, 2, 100, 100, '2026-02-08'),
(9, 3, 3, 100, 100, '2026-02-08'),
(10, 4, 1, 100, 100, '2026-02-08'),
(11, 4, 2, 100, 100, '2026-02-08'),
(12, 4, 3, 100, 100, '2026-02-08'),
(13, 5, 1, 100, 100, '2026-02-08'),
(14, 5, 2, 100, 100, '2026-02-08'),
(15, 5, 3, 100, 100, '2026-02-08'),
(16, 6, 1, 100, 100, '2026-02-08'),
(17, 6, 2, 100, 100, '2026-02-08'),
(18, 6, 3, 100, 100, '2026-02-08');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_gudang`
--

CREATE TABLE `transaksi_gudang` (
  `id_transaksi_gudang` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `id_cabang` int(11) DEFAULT NULL,
  `id_karyawan` int(11) DEFAULT NULL,
  `jenis` enum('masuk','keluar') DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `keterangan` enum('pembelian','transfer','rusak','dipakai','hilang','lainnya') DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_kasir`
--

CREATE TABLE `transaksi_kasir` (
  `id_transaksi` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `id_cabang` int(11) DEFAULT NULL,
  `id_karyawan` int(11) DEFAULT NULL,
  `jenis_pembeli` enum('pembeli','penjual') DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `harga_satuan` decimal(12,2) DEFAULT NULL,
  `total_harga` decimal(12,2) DEFAULT NULL,
  `selisih_keuntungan` decimal(12,2) DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `metode` enum('cash','transfer') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`);

--
-- Indexes for table `cabang`
--
ALTER TABLE `cabang`
  ADD PRIMARY KEY (`id_cabang`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id_karyawan`),
  ADD KEY `id_cabang` (`id_cabang`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `id_karyawan` (`id_karyawan`),
  ADD KEY `id_cabang` (`id_cabang`);

--
-- Indexes for table `stock_opname`
--
ALTER TABLE `stock_opname`
  ADD PRIMARY KEY (`id_opname`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_cabang` (`id_cabang`);

--
-- Indexes for table `stok_gudang`
--
ALTER TABLE `stok_gudang`
  ADD PRIMARY KEY (`id_stok_gudang`),
  ADD UNIQUE KEY `unique_stok` (`id_barang`,`id_cabang`,`tanggal_update`),
  ADD KEY `id_cabang` (`id_cabang`);

--
-- Indexes for table `transaksi_gudang`
--
ALTER TABLE `transaksi_gudang`
  ADD PRIMARY KEY (`id_transaksi_gudang`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_cabang` (`id_cabang`),
  ADD KEY `id_karyawan` (`id_karyawan`);

--
-- Indexes for table `transaksi_kasir`
--
ALTER TABLE `transaksi_kasir`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_cabang` (`id_cabang`),
  ADD KEY `id_karyawan` (`id_karyawan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cabang`
--
ALTER TABLE `cabang`
  MODIFY `id_cabang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id_karyawan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_opname`
--
ALTER TABLE `stock_opname`
  MODIFY `id_opname` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_gudang`
--
ALTER TABLE `stok_gudang`
  MODIFY `id_stok_gudang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `transaksi_gudang`
--
ALTER TABLE `transaksi_gudang`
  MODIFY `id_transaksi_gudang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi_kasir`
--
ALTER TABLE `transaksi_kasir`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD CONSTRAINT `karyawan_ibfk_1` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`),
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`);

--
-- Constraints for table `stock_opname`
--
ALTER TABLE `stock_opname`
  ADD CONSTRAINT `stock_opname_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `stock_opname_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`);

--
-- Constraints for table `stok_gudang`
--
ALTER TABLE `stok_gudang`
  ADD CONSTRAINT `stok_gudang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `stok_gudang_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`);

--
-- Constraints for table `transaksi_gudang`
--
ALTER TABLE `transaksi_gudang`
  ADD CONSTRAINT `transaksi_gudang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `transaksi_gudang_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`),
  ADD CONSTRAINT `transaksi_gudang_ibfk_3` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`);

--
-- Constraints for table `transaksi_kasir`
--
ALTER TABLE `transaksi_kasir`
  ADD CONSTRAINT `transaksi_kasir_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `transaksi_kasir_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`),
  ADD CONSTRAINT `transaksi_kasir_ibfk_3` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
