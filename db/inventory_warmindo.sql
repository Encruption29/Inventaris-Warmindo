-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 23, 2026 at 03:34 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_warmindo`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id_keluar` int NOT NULL,
  `tanggal_keluar` datetime NOT NULL,
  `keterangan` enum('Digunakan','Rusak','Kadaluwarsa') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barang_keluar`
--

INSERT INTO `barang_keluar` (`id_keluar`, `tanggal_keluar`, `keterangan`) VALUES
(1, '2026-05-23 07:16:00', 'Digunakan');

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id_batch` int NOT NULL,
  `id_barang` int DEFAULT NULL,
  `id_pemasok` int DEFAULT NULL,
  `jumlah_masuk` int NOT NULL,
  `stok_sisa` int NOT NULL,
  `harga_beli` decimal(10,2) NOT NULL,
  `tanggal_masuk` datetime NOT NULL,
  `tanggal_kadaluwarsa` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barang_masuk`
--

INSERT INTO `barang_masuk` (`id_batch`, `id_barang`, `id_pemasok`, `jumlah_masuk`, `stok_sisa`, `harga_beli`, `tanggal_masuk`, `tanggal_kadaluwarsa`) VALUES
(1, 27, 2, 5, 0, 0.00, '2026-05-23 07:04:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_barang_keluar`
--

CREATE TABLE `detail_barang_keluar` (
  `id_detail_keluar` int NOT NULL,
  `id_keluar` int DEFAULT NULL,
  `id_batch` int DEFAULT NULL,
  `jumlah_keluar` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_barang_keluar`
--

INSERT INTO `detail_barang_keluar` (`id_detail_keluar`, `id_keluar`, `id_batch`, `jumlah_keluar`) VALUES
(1, 1, 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `master_barang`
--

CREATE TABLE `master_barang` (
  `id_barang` int NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` enum('Mie Instan','Topping/Frozen Food','Bumbu & Sayur','Minuman','Pelengkap') NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `stok_minimal` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `master_barang`
--

INSERT INTO `master_barang` (`id_barang`, `nama_barang`, `kategori`, `satuan`, `stok_minimal`) VALUES
(1, 'Indomie Goreng', 'Mie Instan', 'Bungkus', 40),
(2, 'Indomie Soto', 'Mie Instan', 'Bungkus', 40),
(3, 'Mie Sedaap Goreng', 'Mie Instan', 'Bungkus', 30),
(4, 'Sarimi Ayam Bawang', 'Mie Instan', 'Bungkus', 20),
(5, 'Telur', 'Topping/Frozen Food', 'Butir', 50),
(6, 'Sosis', 'Topping/Frozen Food', 'Pcs', 30),
(7, 'Nugget', 'Topping/Frozen Food', 'Pcs', 30),
(8, 'Bakso', 'Topping/Frozen Food', 'Pcs', 50),
(9, 'Keju Slice', 'Topping/Frozen Food', 'Pcs', 20),
(10, 'Cabai Rawit', 'Bumbu & Sayur', 'Gram', 500),
(11, 'Bawang Merah', 'Bumbu & Sayur', 'Gram', 500),
(12, 'Bawang Putih', 'Bumbu & Sayur', 'Gram', 300),
(13, 'Daun Bawang', 'Bumbu & Sayur', 'Gram', 200),
(14, 'Kol', 'Bumbu & Sayur', 'Gram', 1000),
(15, 'Saus Sambal', 'Pelengkap', 'Botol', 5),
(16, 'Saus Tomat', 'Pelengkap', 'Botol', 3),
(17, 'Kecap Manis', 'Pelengkap', 'Botol', 5),
(18, 'Mayones', 'Pelengkap', 'Pouch', 2),
(19, 'Margarin', 'Pelengkap', 'Pouch', 3),
(20, 'Minyak Goreng', 'Pelengkap', 'Liter', 5),
(21, 'Kopi Kapal Api', 'Minuman', 'Sachet', 20),
(22, 'Good Day', 'Minuman', 'Sachet', 20),
(23, 'Teh Tarik', 'Minuman', 'Sachet', 15),
(24, 'Susu Kental Manis', 'Minuman', 'Kaleng', 4),
(25, 'Milo', 'Minuman', 'Sachet', 15),
(26, 'Nutrisari', 'Minuman', 'Sachet', 25),
(27, 'Air Mineral', 'Minuman', 'Botol', 48),
(28, 'Teh Pucuk', 'Minuman', 'Botol', 24),
(29, 'Pop Ice', 'Minuman', 'Sachet', 20),
(30, 'Es Batu', 'Minuman', 'Kantong', 5);

-- --------------------------------------------------------

--
-- Table structure for table `pemasok`
--

CREATE TABLE `pemasok` (
  `id_pemasok` int NOT NULL,
  `nama_pemasok` varchar(100) NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pemasok`
--

INSERT INTO `pemasok` (`id_pemasok`, `nama_pemasok`, `no_telp`) VALUES
(1, 'Grosir Sembako Jaya', '081234567890'),
(2, 'Agen Frozen Food Pekanbaru', '081299887766');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id_keluar`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id_batch`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_pemasok` (`id_pemasok`);

--
-- Indexes for table `detail_barang_keluar`
--
ALTER TABLE `detail_barang_keluar`
  ADD PRIMARY KEY (`id_detail_keluar`),
  ADD KEY `id_keluar` (`id_keluar`),
  ADD KEY `id_batch` (`id_batch`);

--
-- Indexes for table `master_barang`
--
ALTER TABLE `master_barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `pemasok`
--
ALTER TABLE `pemasok`
  ADD PRIMARY KEY (`id_pemasok`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id_keluar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id_batch` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `detail_barang_keluar`
--
ALTER TABLE `detail_barang_keluar`
  MODIFY `id_detail_keluar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `master_barang`
--
ALTER TABLE `master_barang`
  MODIFY `id_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `pemasok`
--
ALTER TABLE `pemasok`
  MODIFY `id_pemasok` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `master_barang` (`id_barang`),
  ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`id_pemasok`) REFERENCES `pemasok` (`id_pemasok`);

--
-- Constraints for table `detail_barang_keluar`
--
ALTER TABLE `detail_barang_keluar`
  ADD CONSTRAINT `detail_barang_keluar_ibfk_1` FOREIGN KEY (`id_keluar`) REFERENCES `barang_keluar` (`id_keluar`),
  ADD CONSTRAINT `detail_barang_keluar_ibfk_2` FOREIGN KEY (`id_batch`) REFERENCES `barang_masuk` (`id_batch`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
