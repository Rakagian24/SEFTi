-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 21 Jul 2025 pada 09.34
-- Versi server: 11.8.2-MariaDB
-- Versi PHP: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sefti`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `ar_partners`
--

CREATE TABLE `ar_partners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_ap` varchar(255) NOT NULL,
  `jenis_ap` varchar(255) NOT NULL,
  `alamat` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `no_telepon` varchar(255) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ar_partners`
--

INSERT INTO `ar_partners` (`id`, `nama_ap`, `jenis_ap`, `alamat`, `email`, `no_telepon`, `contact_person`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'Ahmad Rizki', 'Customer', 'Jl. Sudirman No. 123, Jakarta Pusatsad', 'ahmad.rizki@email.com', '081234567890', NULL, NULL, '2025-07-13 18:54:04', '2025-07-17 02:53:49'),
(2, 'Siti Nurhaliza', 'Customer', 'Jl. Thamrin No. 45, Jakarta Pusat', 'siti.nurhaliza@email.com', '081234567891', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(3, 'Budi Santoso', 'Karyawan', 'Jl. Gatot Subroto No. 67, Jakarta Selatan', 'budi.santoso@company.com', '081234567892', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(4, 'Dewi Sartika', 'Karyawan', 'Jl. Rasuna Said No. 89, Jakarta Selatan', 'dewi.sartika@company.com', '081234567893', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(5, 'Toko Maju Jaya', 'Penjualan Toko', 'Jl. Hayam Wuruk No. 12, Jakarta Barat', 'info@majujaya.com', '021-5550123', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(6, 'Toko Sukses Mandiri', 'Penjualan Toko', 'Jl. Gajah Mada No. 34, Jakarta Barat', 'contact@suksesmandiri.com', '021-5550456', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(7, 'Rina Melati', 'Customer', 'Jl. Kebon Jeruk No. 56, Jakarta Barat', 'rina.melati@email.com', '081234567894', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(8, 'Toko Berkah Abadi', 'Penjualan Toko', 'Jl. Mangga Dua No. 78, Jakarta Utara', 'berkahabadi@gmail.com', '021-5550789', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(9, 'Joko Widodo', 'Customer', 'Jl. Kelapa Gading No. 90, Jakarta Utara', 'joko.widodo@email.com', '081234567895', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(10, 'Sri Mulyani', 'Karyawan', 'Jl. Pluit No. 23, Jakarta Utara', 'sri.mulyani@company.com', '081234567896', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(11, 'Toko Makmur Sejahtera', 'Penjualan Toko', 'Jl. Cempaka Putih No. 45, Jakarta Pusat', 'makmursejahtera@gmail.com', '021-5550124', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(12, 'Bambang Trihatmodjo', 'Customer', 'Jl. Senayan No. 67, Jakarta Selatan', 'bambang.trihatmodjo@email.com', '081234567897', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(13, 'Megawati Soekarnoputri', 'Customer', 'Jl. Kuningan No. 89, Jakarta Selatan', 'megawati.soekarnoputri@email.com', '081234567898', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(14, 'Toko Indah Permai', 'Penjualan Toko', 'Jl. Tanah Abang No. 12, Jakarta Pusat', 'indahpermai@gmail.com', '021-5550457', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(15, 'Susilo Bambang Yudhoyono', 'Customer', 'Jl. Menteng No. 34, Jakarta Pusat', 'susilo.bambang@email.com', '081234567899', NULL, NULL, '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(16, 'Raka', 'Customer', 'Sanggar Indah Banjaran Blok K4 no.7 RT02 RW11 Desa Nagrak Kecamatan Cangkuang', 'rakagian107@gmail.com', '089508410132', 'Raka Gian Aditya Asbath', 5, '2025-07-21 01:17:57', '2025-07-21 01:17:57'),
(17, 'sdadsdasd', 'Customer', 'asdasd', 'rasndsa@hsdm.co', '415320205', 'asdasd', 5, '2025-07-21 01:21:56', '2025-07-21 01:21:56'),
(18, 'dsadsa', 'Customer', 'sdasdas', 'asdasdasd@sda.co', '05616516', 'saddasdas', 1, '2025-07-21 01:27:08', '2025-07-21 01:27:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ar_partner_logs`
--

CREATE TABLE `ar_partner_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ar_partner_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `banks`
--

CREATE TABLE `banks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_bank` varchar(255) NOT NULL,
  `nama_bank` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `singkatan` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `currency` enum('IDR','USD') NOT NULL DEFAULT 'IDR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `banks`
--

INSERT INTO `banks` (`id`, `kode_bank`, `nama_bank`, `created_at`, `updated_at`, `singkatan`, `status`, `currency`) VALUES
(1, '001', 'Bank Central Asia', '2025-07-13 18:54:02', '2025-07-16 18:31:50', 'BCA', 'active', 'IDR'),
(2, '002', 'Bank Mandiri', '2025-07-13 18:54:02', '2025-07-16 20:51:17', 'Mandiri', 'active', 'IDR'),
(3, '003', 'Bank Negara Indonesia', '2025-07-13 18:54:02', '2025-07-16 18:32:16', 'BNI', 'active', 'IDR'),
(4, '004', 'Bank Rakyat Indonesia', '2025-07-13 18:54:02', '2025-07-13 18:54:02', 'BRI', 'active', 'IDR'),
(6, '005', 'Bank Cruds', '2025-07-16 18:32:47', '2025-07-21 01:34:52', 'BCD', 'active', 'USD'),
(7, '984', 'sdasdsdsdsdas', '2025-07-21 01:34:46', '2025-07-21 01:34:46', 'sadasdsadsad', 'active', 'USD');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_pemilik` varchar(255) NOT NULL,
  `no_rekening` varchar(255) NOT NULL,
  `bank_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `nama_pemilik`, `no_rekening`, `bank_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Andi Wijaya', '1234567890', 1, 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(2, 'Siti Aminah', '9876543210', 2, 'active', '2025-07-13 18:54:04', '2025-07-14 02:44:54'),
(3, 'Budi Santoso', '1122334455', 3, 'inactive', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(4, 'Raka Gian', '4561320', 4, 'active', '2025-07-16 18:32:35', '2025-07-16 18:32:35'),
(5, 'Aditya Asbath', '48651320', 6, 'active', '2025-07-16 18:32:56', '2025-07-16 18:32:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bank_account_logs`
--

CREATE TABLE `bank_account_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bank_account_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bank_account_logs`
--

INSERT INTO `bank_account_logs` (`id`, `bank_account_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 4, 6, 'created', 'Bank Account dibuat', '10.10.25.114', '2025-07-16 18:32:35', '2025-07-16 18:32:35'),
(2, 5, 6, 'created', 'Bank Account dibuat', '10.10.25.114', '2025-07-16 18:32:56', '2025-07-16 18:32:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bank_logs`
--

CREATE TABLE `bank_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bank_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bank_logs`
--

INSERT INTO `bank_logs` (`id`, `bank_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`, `updated_at`) VALUES
(2, 6, 6, 'created', 'Bank dibuat', '10.10.25.114', '2025-07-16 18:32:47', '2025-07-16 18:32:47'),
(3, 6, 6, 'updated', 'Bank diupdate', '10.10.25.114', '2025-07-16 18:33:03', '2025-07-16 18:33:03'),
(4, 7, 6, 'created', 'Bank dibuat', '10.10.25.114', '2025-07-21 01:34:46', '2025-07-21 01:34:46'),
(5, 6, 6, 'updated', 'Bank diupdate', '10.10.25.114', '2025-07-21 01:34:52', '2025-07-21 01:34:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bank_supplier_accounts`
--

CREATE TABLE `bank_supplier_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `bank_id` bigint(20) UNSIGNED NOT NULL,
  `nama_rekening` varchar(255) NOT NULL,
  `no_rekening` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bank_supplier_accounts`
--

INSERT INTO `bank_supplier_accounts` (`id`, `supplier_id`, `bank_id`, `nama_rekening`, `no_rekening`, `created_at`, `updated_at`) VALUES
(5, 27, 1, 'sadsadas', '41533', '2025-07-20 23:09:12', '2025-07-20 23:09:12'),
(6, 27, 2, 'adasdasdas', '56113213', '2025-07-20 23:09:12', '2025-07-20 23:09:12'),
(7, 28, 1, 'sadsad', '87654', '2025-07-21 02:28:25', '2025-07-21 02:28:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bisnis_partners`
--

CREATE TABLE `bisnis_partners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_bp` varchar(255) NOT NULL,
  `jenis_bp` varchar(255) NOT NULL,
  `alamat` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `no_telepon` varchar(255) DEFAULT NULL,
  `nama_rekening` varchar(255) DEFAULT NULL,
  `no_rekening_va` varchar(255) DEFAULT NULL,
  `terms_of_payment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bank_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bisnis_partners`
--

INSERT INTO `bisnis_partners` (`id`, `nama_bp`, `jenis_bp`, `alamat`, `email`, `no_telepon`, `nama_rekening`, `no_rekening_va`, `terms_of_payment`, `created_at`, `updated_at`, `bank_id`) VALUES
(1, 'PT Maju Bersama', 'Customer', 'Jl. Sudirman No. 123, Jakarta', 'info@majubersama.com', '021-5550123', 'PT Maju Bersama', '1234567890', '30 Hari', '2025-07-13 18:54:02', '2025-07-13 18:54:02', 1),
(2, 'CV Sukses Mandiri', 'Customer', 'Jl. Thamrin No. 45, Jakarta', 'contact@suksesmandiri.co.id', '021-5550456', 'CV Sukses Mandiri', '0987654321', '15 Hari', '2025-07-13 18:54:02', '2025-07-13 18:54:02', 2),
(3, 'UD Berkah Jaya', 'Customer', 'Jl. Gatot Subroto No. 67, Jakarta', 'berkahjaya@gmail.com', '021-5550789', 'UD Berkah Jaya', '1122334455', '7 Hari', '2025-07-13 18:54:02', '2025-07-13 18:54:02', 4),
(4, 'McCullough-Boyle', 'Cabang', '85188 Denesik Roads\nPort Tyson, MA 28875-7521', 'zzulauf@example.net', '458-212-0934', 'Dr. Lamar Satterfield', '7825823', '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 3),
(5, 'Nikolaus-Schmidt', 'Cabang', '412 Shakira Inlet\nNorth Domenicoport, ME 79294-0497', 'claud.keebler@example.com', '(830) 413-9126', 'Grady Fahey', '575524923', '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(6, 'Wilderman PLC', 'Customer', '406 Madisen Neck\nReedborough, NV 09537-5341', 'treynolds@example.com', '+1 (607) 927-3112', 'Mrs. Demetris Rogahn Jr.', '9916492067409', '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(7, 'Lehner-Sawayn', 'Customer', '749 Matilda Grove\nProsaccoton, MO 22687', 'cpouros@example.org', '+1 (857) 594-3258', 'Prof. Lincoln Veum', '81727188990999', '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(8, 'Beer, Ziemann and Satterfield', 'Karyawan', '82224 Fritsch Turnpike\nBodemouth, WA 76724-2141', 'mante.erna@example.net', '281.509.1583', 'Reilly Reichert III', '280497994874', '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 4),
(9, 'Kunze, Bartoletti and Dooley', 'Customer', '366 Tyra Union Suite 543\nLake Roderickside, AK 56316-8963', 'alvis33@example.org', '+1-380-565-4376', 'Miss Rosalia Corwin', '2845588713135', '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(10, 'Ward-Moen', 'Cabang', '18246 Rogers Pines\nJusticemouth, WV 38962', 'lyric.beier@example.org', '442-816-9650', 'Trever Kertzmann', '5581886228', '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(11, 'Hermann-Wiegand', 'Karyawan', '978 Coby Dam\nNorth Constantin, WA 40198', 'mboyle@example.com', '+1 (240) 310-6396', 'Sid Krajcik', '251581211294182', '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 3),
(12, 'Mayert-Towne', 'Customer', '673 Kuhic Crossing\nPort Flossiechester, TN 38913', 'pweber@example.net', '+1.585.814.1899', 'Anastacio Koch', '613642229', '90 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 3),
(13, 'Powlowski and Sons', 'Cabang', '901 Stephania Spurs\nLake Grover, ME 73964', 'fredrick23@example.com', '+1-475-541-5039', 'Hilario Lesch', '417875253517', '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(14, 'Spinka Ltd', 'Cabang', '685 Kemmer Expressway Suite 117\nArmstrongland, VA 31555', 'summer.eichmann@example.net', '332.365.3549', 'Mrs. Sincere Olson', '518812852326', '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(15, 'Homenick, Weber and Harvey', 'Karyawan', '52970 Borer Greens Apt. 049\nHaagmouth, AR 88894', 'margot.reilly@example.org', '1-332-866-3514', 'Mr. Dereck O\'Hara I', '836095532839', '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(16, 'Rempel-Mann', 'Cabang', '415 Gleason Inlet\nKassulkehaven, NJ 80807', 'carley66@example.org', '+1 (734) 696-5206', 'Alvis Gleason', '55467845559', '90 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(17, 'Heaney LLC', 'Cabang', '39181 Teresa Haven\nHamillstad, IN 01042', 'macejkovic.salvatore@example.org', '1-606-454-1553', 'Cathrine Paucek', '91187360217', '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(18, 'Hickle-Waters', 'Customer', '23491 Kertzmann Groves Suite 132\nLake Brendonshire, WV 17557-4476', 'maxie.sanford@example.com', '+1.307.782.1390', 'Lyla Schroeder', '8601225', '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(19, 'Heidenreich, Glover and Gorczany', 'Customer', '385 Tremblay Brooks Apt. 550\nGutmannborough, AZ 02152', 'mueller.lina@example.org', '+1-732-518-0524', 'Mr. Conner Cassin', '6402399973401924', '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(20, 'Heathcote, Crooks and Mitchell', 'Customer', '977 Chasity Forges\nYostfurt, IN 36623-2890', 'ffeest@example.org', '+19193688875', 'Clint Shields II', '43755428217020', '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 4),
(21, 'Dietrich-Moen', 'Customer', '7091 Goodwin Pines Suite 662\nMitchellview, KS 26465-9212', 'bartoletti.dolly@example.net', '(907) 582-6257', 'Opal Sipes DVM', '882425184', '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 4),
(22, 'Funk-Botsford', 'Cabang', '78674 Laurence Estates Apt. 676\nLake Margaritaview, VT 00954', 'herman.savannah@example.com', '(201) 313-4371', 'Hardy Douglas', '0541993', '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(23, 'Bogan-Braun', 'Cabang', '426 Korbin Groves Suite 730\nDewaynefort, ND 62403-9603', 'brown.martin@example.com', '+1-564-565-6743', 'Alia Wisozk', '94063493', '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 3),
(24, 'Huels-Cassin', 'Cabang', '27666 Keebler Extensions Apt. 167\nKatlynnside, DC 33037-6030', 'odaniel@example.net', '515-477-8465', 'Hans Leffler', '75020937178978', '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 3),
(25, 'Koepp, Larson and Hammes', 'Cabang', '9207 Danielle Turnpike\nNew Cara, VA 98560', 'ismael17@example.org', '+1.769.208.8153', 'Jaleel Osinski', '22330771773', '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 2),
(26, 'Mayer and Sons', 'Cabang', '45224 Brekke Lock\nEfrainborough, TX 33897-6609', 'reece88@example.org', '239-268-1821', 'Mr. Dante Reichert', '48199932365', '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(27, 'Collier-Schuppe', 'Customer', '977 Lilian Skyway Apt. 698\nPort Kenna, NJ 21806-6589', 'nyasia.gleichner@example.com', '(267) 456-3102', 'Ms. Laurence Bruen', '414978727237', '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 4),
(28, 'Stiedemann LLC', 'Karyawan', '4797 Jeffrey Corners Apt. 147\nPfannerstillmouth, CT 79653', 'waelchi.michelle@example.net', '+1-808-833-8759', 'Prof. Lilian Hartmann', '03342023', '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04', 1),
(30, 'dasdasd', 'Customer', 'dsasadas', 'dassadasd@gmail.com', '54101320', 'dsadasd', '451230', '7 Hari', '2025-07-20 23:01:54', '2025-07-20 23:01:54', 1),
(31, 'ilham 2', 'Cabang', 'tegal', 'ilham@gmail.com', '13526785', 'Ilham', '98140796', '15 Hari', '2025-07-20 23:13:28', '2025-07-20 23:13:43', 1),
(32, 'sdasd', 'Karyawan', 'dsada', NULL, NULL, 'dasda', '1815', '7 Hari', '2025-07-21 01:37:06', '2025-07-21 01:37:06', 6);

-- --------------------------------------------------------

--
-- Struktur dari tabel `bisnis_partner_logs`
--

CREATE TABLE `bisnis_partner_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bisnis_partner_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bisnis_partner_logs`
--

INSERT INTO `bisnis_partner_logs` (`id`, `bisnis_partner_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`, `updated_at`) VALUES
(5, 30, 6, 'created', 'Bisnis Partner dibuat', '10.10.25.114', '2025-07-20 23:01:54', '2025-07-20 23:01:54'),
(6, 31, 11, 'created', 'Bisnis Partner dibuat', '10.10.25.137', '2025-07-20 23:13:28', '2025-07-20 23:13:28'),
(7, 31, 11, 'updated', 'Bisnis Partner diupdate', '10.10.25.137', '2025-07-20 23:13:43', '2025-07-20 23:13:43'),
(8, 32, 6, 'created', 'Bisnis Partner dibuat', '10.10.25.114', '2025-07-21 01:37:06', '2025-07-21 01:37:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_one_id` bigint(20) UNSIGNED NOT NULL,
  `user_two_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_message_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `departments`
--

INSERT INTO `departments` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SGT 1', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(2, 'SGT 2', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(3, 'SGT 3', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(4, 'Nirwana Textile Hasanudin', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(5, 'Nirwana Textile Bkr', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(6, 'Nirwana Textile Yogyakarta', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(7, 'Nirwana Textile Bali', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(8, 'Nirwana Textile Surabaya', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(9, 'Human Greatness', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30'),
(10, 'Zi&Glo', 'active', '2025-07-13 19:32:30', '2025-07-13 19:32:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `message` varchar(250) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `message_reads`
--

CREATE TABLE `message_reads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_07_08_082311_create_bisnis_partners_table', 1),
(5, '2025_07_08_082340_create_ar_partners_table', 1),
(6, '2025_07_08_085045_create_banks_table', 1),
(7, '2025_07_09_011256_add_bank_id_to_bisnis_partners_table', 1),
(8, '2025_07_10_000000_create_pengeluarans_table', 1),
(9, '2025_07_10_000001_create_pphs_table', 1),
(10, '2025_07_10_000002_create_bank_accounts_table', 1),
(11, '2025_07_10_000003_create_suppliers_table', 1),
(12, '2025_07_11_031459_update_bank_accounts_status_to_enum', 1),
(13, '2025_07_11_034313_update_suppliers_table_for_multiple_bank_accounts', 1),
(14, '2025_07_14_021207_add_phone_role_department_to_users_table', 2),
(15, '2025_07_14_022659_create_departments_table', 3),
(16, '2025_07_14_022703_create_roles_table', 3),
(17, '2025_07_14_022952_create_otp_verifications_table', 3),
(18, '2025_07_14_023105_update_users_table_role_department_to_foreign_keys', 3),
(19, '2025_07_14_071142_create_bisnis_partner_logs_table', 4),
(20, '2025_07_15_014326_add_status_to_users_table', 5),
(21, '2025_07_15_020000_create_bank_account_logs_table', 5),
(22, '2025_07_15_021000_create_bank_logs_table', 5),
(23, '2025_07_15_022000_create_pengeluaran_logs_table', 5),
(24, '2025_07_15_023000_create_pph_logs_table', 5),
(25, '2025_07_15_024000_create_supplier_logs_table', 5),
(26, '2025_07_15_040013_create_ar_partner_logs_table', 5),
(27, '2025_07_15_073839_update_alamat_to_text_on_partners_and_suppliers_table', 5),
(28, '2025_07_15_073855_update_deskripsi_to_text_on_pengeluarans_pphs_roles_table', 5),
(29, '2025_07_16_000001_add_unique_to_nama_bank_and_singkatan_on_banks_table', 5),
(30, '2025_07_16_100000_create_bank_supplier_accounts_table', 6),
(31, '2025_07_17_000000_remove_bank_columns_from_suppliers_table', 7),
(32, '2025_07_17_061307_add_photo_to_users_table', 8),
(33, '2025_07_17_100001_add_passcode_to_users_table', 9),
(34, '2025_07_17_200000_create_conversations_table', 10),
(35, '2025_07_17_200001_create_messages_table', 10),
(36, '2025_07_17_200002_create_message_reads_table', 10),
(37, '2025_07_17_200003_add_last_message_id_to_conversations_table', 10),
(38, '2025_07_21_080139_add_contact_person_and_department_id_to_ar_partners_table', 11),
(39, '2025_07_21_082838_add_currency_to_banks_table', 12),
(40, '2025_07_21_083000_add_status_to_pengeluarans_table', 13),
(41, '2025_07_21_084000_add_department_id_to_suppliers_table', 14);

-- --------------------------------------------------------

--
-- Struktur dari tabel `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(255) NOT NULL,
  `otp` varchar(4) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluarans`
--

CREATE TABLE `pengeluarans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengeluarans`
--

INSERT INTO `pengeluarans` (`id`, `nama`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Pembelian ATK', 'Pengeluaran untuk alat tulis kantor', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(2, 'Biaya Listrik', 'Pembayaran tagihan listrik bulanan', 'inactive', '2025-07-13 18:54:04', '2025-07-21 01:42:29'),
(3, 'Transportasi', 'Pengeluaran untuk transportasi operasional', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(4, 'Konsumsi Rapat', 'Pengeluaran makanan dan minuman saat rapat', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(5, 'Perawatan Gedung', 'Biaya perawatan dan kebersihan gedung', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran_logs`
--

CREATE TABLE `pengeluaran_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pengeluaran_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pphs`
--

CREATE TABLE `pphs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_pph` varchar(255) NOT NULL,
  `nama_pph` varchar(255) NOT NULL,
  `tarif_pph` bigint(20) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pphs`
--

INSERT INTO `pphs` (`id`, `kode_pph`, `nama_pph`, `tarif_pph`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(1, '21', 'PPh Pasal 21', NULL, 'Pajak atas penghasilan sehubungan dengan pekerjaan, jasa, dan kegiatan.', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(2, '22', 'PPh Pasal 22', NULL, 'Pajak atas kegiatan impor atau kegiatan usaha tertentu.', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(3, '23', 'PPh Pasal 23', NULL, 'Pajak atas penghasilan dari modal, penyerahan jasa, atau hadiah.', 'inactive', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(4, '25', 'PPh Pasal 25', NULL, 'Angsuran pajak penghasilan bagi wajib pajak tertentu.', 'active', '2025-07-13 18:54:04', '2025-07-13 18:54:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pph_logs`
--

CREATE TABLE `pph_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pph_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `permissions`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Staff Toko', 'Role ini hanya dapat mengakses menu Purchase Order, Memo Pembayaran, BPB, dan Anggaran.', '[\"purchase_order\",\"memo_pembayaran\",\"bpb\",\"anggaran\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32'),
(2, 'Kepala Toko', 'Role ini hanya dapat mengakses menu Purchase Order, Memo Pembayaran, BPB, Anggaran dan Approval.', '[\"purchase_order\",\"memo_pembayaran\",\"bpb\",\"anggaran\",\"approval\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32'),
(3, 'Staff Akunting & Finance', 'Role ini hanya dapat mengakses menu Purchase Order, Bank, Supplier, Bisnis Partner, Memo Pembayaran, BPB, Payment Voucher, Daftar List Bayar, Bank Masuk, Bank Keluar dan PO Outstanding.', '[\"purchase_order\",\"bank\",\"supplier\",\"bisnis_partner\",\"memo_pembayaran\",\"bpb\",\"payment_voucher\",\"daftar_list_bayar\",\"bank_masuk\",\"bank_keluar\",\"po_outstanding\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32'),
(4, 'Kabag', 'Role ini hanya dapat mengakses menu Purchase Order, Bank, Supplier, Bisnis Partner, Memo Pembayaran, BPB, Payment Voucher, Daftar List Bayar, Bank Masuk, Bank Keluar, PO Outstanding dan Approval.', '[\"purchase_order\",\"bank\",\"supplier\",\"bisnis_partner\",\"memo_pembayaran\",\"bpb\",\"payment_voucher\",\"daftar_list_bayar\",\"bank_masuk\",\"bank_keluar\",\"po_outstanding\",\"approval\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32'),
(5, 'Kadiv', 'Role ini hanya dapat mengakses menu Approval', '[\"approval\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32'),
(6, 'Direksi', 'Role ini hanya dapat mengakses menu Approval', '[\"approval\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32'),
(7, 'Admin', 'Role ini dapat mengakses keseluruhan menu', '[\"*\"]', 'active', '2025-07-13 19:32:32', '2025-07-13 19:32:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('8O1q1of8FazaLdFlfQnqoRp8hPhxE7Nj86eoKSba', 11, '10.10.25.137', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT0pjSGllN2pzNmdETHNGQmpiNERYRE5Na21YdkJDbks2cnVSS1ZDNyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTE7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMC4xMC4yNS4xMTQ6ODAwMC9iaXNuaXMtcGFydG5lcnMiO319', 1753084298),
('vmIl8TelUeCTNMKUoFmUeYsPKbG30xtGX6Z8ty4B', 6, '10.10.25.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTkl5RFRYVFh3cVZWY2hJOHZ1eUlmcVFZcTZ5NVRhckJ4R2JZTnVBRSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NjE6Imh0dHA6Ly8xMC4xMC4yNS4xMTQ6ODAwMC9hci1wYXJ0bmVycz9kZXBhcnRtZW50PTEmcGVyX3BhZ2U9MTAiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo2O30=', 1753090339);

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_supplier` varchar(255) NOT NULL,
  `alamat` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `no_telepon` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `terms_of_payment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `suppliers`
--

INSERT INTO `suppliers` (`id`, `nama_supplier`, `alamat`, `email`, `no_telepon`, `department_id`, `terms_of_payment`, `created_at`, `updated_at`) VALUES
(1, 'PT Maju Bersama Sejahtera', 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 12190', 'procurement@majubersama.com', '021-555-0123', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(2, 'CV Mitra Sukses Abadi', 'Jl. Thamrin No. 45, Jakarta Selatan, DKI Jakarta 12150', 'sales@mitrasukses.co.id', '021-555-0456', NULL, '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(3, 'UD Sentosa Makmur', 'Jl. Gatot Subroto No. 67, Jakarta Barat, DKI Jakarta 11470', 'info@sentosamakmur.com', '021-555-0789', NULL, '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(4, 'PT Jaya Abadi Perkasa', 'Jl. Merdeka No. 89, Bandung, Jawa Barat 40111', 'contact@jayaabadi.co.id', '022-555-0123', NULL, '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(5, 'CV Prima Sukses Mandiri', 'Jl. Diponegoro No. 234, Surabaya, Jawa Timur 60241', 'admin@primasukses.com', '031-555-0456', NULL, '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(6, 'UD Makmur Jaya', 'Jl. Ahmad Yani No. 56, Semarang, Jawa Tengah 50123', 'sales@makmurjaya.co.id', '024-555-0789', NULL, '90 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(7, 'Toko Sumber Rejeki', 'Jl. Pasar Baru No. 12, Medan, Sumatera Utara 20112', 'info@sumberrejeki.com', '061-555-0123', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(8, 'Warung Makmur Sejahtera', 'Jl. Veteran No. 78, Palembang, Sumatera Selatan 30111', 'contact@makmursejahtera.co.id', '0711-555-0456', NULL, '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(9, 'Koperasi Sejahtera Bersama', 'Jl. Sudirman No. 45, Makassar, Sulawesi Selatan 90111', 'admin@koperasisejahtera.com', '0411-555-0789', NULL, '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(10, 'PT Teknologi Maju Indonesia', 'Jl. Hayam Wuruk No. 123, Jakarta Pusat, DKI Jakarta 10120', 'tech@teknologimaju.com', '021-555-0124', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(11, 'CV Bahan Bangunan Sukses', 'Jl. Industri No. 67, Tangerang, Banten 15111', 'sales@bahanbangunan.co.id', '021-555-0457', NULL, '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(12, 'UD Makanan Sehat', 'Jl. Pasar Minggu No. 89, Jakarta Selatan, DKI Jakarta 12520', 'info@makanansehat.com', '021-555-0788', NULL, '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(13, 'PT Supplier Kalimantan', 'Jl. Ahmad Yani No. 234, Balikpapan, Kalimantan Timur 76111', 'contact@supplierkalimantan.co.id', '0542-555-0123', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(14, 'CV Nusantara Jaya', 'Jl. Veteran No. 56, Manado, Sulawesi Utara 95111', 'sales@nusantarajaya.com', '0431-555-0456', NULL, '60 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(15, 'UD Papua Maju', 'Jl. Sudirman No. 78, Jayapura, Papua 99111', 'admin@papuanmaju.co.id', '0967-555-0789', NULL, '90 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(16, 'PT Global Trading Indonesia', 'Jl. Asia Afrika No. 123, Bandung, Jawa Barat 40111', 'global@globaltrading.com', '022-555-0125', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(17, 'CV Import Export Sukses', 'Jl. Pelabuhan No. 45, Surabaya, Jawa Timur 60111', 'import@importexport.co.id', '031-555-0458', NULL, '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(18, 'PT Jasa Logistik Indonesia', 'Jl. Cikarang No. 67, Bekasi, Jawa Barat 17530', 'logistik@jasalogistik.com', '021-555-0787', NULL, '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(19, 'CV Konsultan Bisnis', 'Jl. Sudirman No. 89, Jakarta Pusat, DKI Jakarta 12190', 'konsultan@bisnis.co.id', '021-555-0126', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(20, 'UD Maintenance Service', 'Jl. Industri No. 234, Tangerang, Banten 15111', 'maintenance@service.com', '021-555-0459', NULL, '7 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(21, 'PT Retail Sukses Mandiri', 'Jl. Mall No. 56, Jakarta Selatan, DKI Jakarta 12190', 'retail@suksesmandiri.com', '021-555-0786', NULL, '15 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(22, 'CV Fashion Trend', 'Jl. Fashion No. 78, Bandung, Jawa Barat 40111', 'fashion@trend.co.id', '022-555-0127', NULL, '30 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(23, 'UD Elektronik Maju', 'Jl. Elektronik No. 90, Surabaya, Jawa Timur 60111', 'elektronik@maju.com', '031-555-0460', NULL, '45 Hari', '2025-07-13 18:54:04', '2025-07-13 18:54:04'),
(27, 'raka raka', 'rakaraka', 'raka@gsada.com', '0846133', NULL, '30 Hari', '2025-07-20 23:09:12', '2025-07-20 23:09:12'),
(28, 'dsadasd', 'sdasdas', 'sdada@sda.c', '07542542', 1, '7 Hari', '2025-07-21 02:28:25', '2025-07-21 02:28:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier_logs`
--

CREATE TABLE `supplier_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `supplier_logs`
--

INSERT INTO `supplier_logs` (`id`, `supplier_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`, `updated_at`) VALUES
(12, 27, 6, 'created', 'Supplier dibuat', '10.10.25.114', '2025-07-20 23:09:12', '2025-07-20 23:09:12'),
(13, 28, 6, 'created', 'Supplier dibuat', '10.10.25.114', '2025-07-21 02:28:25', '2025-07-21 02:28:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `passcode` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `photo`, `passcode`, `remember_token`, `created_at`, `updated_at`, `role_id`, `department_id`, `status`) VALUES
(2, 'Raka Gian Aditya Asbath', 'rakagian107@gmail.com', '089508410132', NULL, '$2y$12$WOcAgodwq9J8o7cFS1I1p.FqB518yOkv83k0dx9qDISuuVSM0f9WK', NULL, NULL, NULL, '2025-07-13 23:29:26', '2025-07-13 23:29:26', 7, 1, 'active'),
(3, 'Ilham', 'ilham@sefti.com', '084616513189', NULL, '$2y$12$PW8i9jKg7PXgQ31dxXkLluK1knSmnZb777tMKBIdXad5np1jo0Xxu', NULL, NULL, NULL, '2025-07-14 02:30:41', '2025-07-14 02:30:41', 7, 1, 'active'),
(4, 'Jefri', 'jefri@sefti.com', '0812315610320', NULL, '$2y$12$IgoGp2rYO9CCV7kOhslcP.YCQeSdYx/90LTjtpzt7VOmTiIB4RM3.', NULL, NULL, NULL, '2025-07-14 02:31:26', '2025-07-14 02:31:26', 7, 2, 'active'),
(5, 'ilham', 'ilhamaisyi1501@gmail.com', '081224188103', NULL, '$2y$12$7UlMSmSjuh5bb.2b0dRzQOSVtquRLZMuwyPa/c2oG5Rp4b8GuObMa', NULL, NULL, NULL, '2025-07-14 02:38:39', '2025-07-14 02:38:39', 7, 1, 'active'),
(6, 'Raka Gian Aditya Asbath', 'rakagian1234@gmail.com', '089508410131', NULL, '$2y$12$7TiNW/vCsXM072GwxOa3Au0GRp0f/1UmY0zmkKBTxZL6sQYyEPzAy', 'profile-photos/7F4fzAoZl3CRSZ9Y967cW4O3cldTlC4Y9hn8yOes.jpg', '$2y$12$Ki/h7y/FQxScBDet8fDXve8ey5tH9m1fiNF0J.ibj2UMswbCzOV6G', NULL, '2025-07-16 18:27:40', '2025-07-20 19:22:36', 7, 1, 'active'),
(7, 'Abi', 'abimanyu@jsioa.co', '0894651320', NULL, '$2y$12$KjxFyuglWW1LOmXp8YdrAOZZcAZbEpQshpFMQtXewrZq3Nx8fCILG', NULL, NULL, NULL, '2025-07-16 19:24:39', '2025-07-16 19:24:39', 7, 6, 'active'),
(8, 'manyu', 'manyuabi@hsion.chs', '645132062', NULL, '$2y$12$.xLz0Qj.R2ckxL/cUC/e/O2ZS3VsP50.kmE7MC3N.toh4wgqqJ0Aa', NULL, NULL, NULL, '2025-07-16 19:35:14', '2025-07-16 19:35:14', 2, 2, 'active'),
(9, 'saha sok lah', 'naonlah@halah.ah', '84651320130', NULL, '$2y$12$cN0kmw4eLA9kdW9x9MSuYO.03L4MFrqnZFU4nm0QZ8c12lcJ0.15q', NULL, NULL, NULL, '2025-07-16 21:24:05', '2025-07-16 21:24:05', 7, 10, 'active'),
(10, 'raka', 'abi@manyu.co', '451230465132', NULL, '$2y$12$wyaZbd.LGDiSN2S.FWN7V.kaNR0iVBtjMvQNPl/TegAjzlXynmJ1C', 'profile-photos/JljDKnPpPKuaNA0ILbmv1tLvQDgWczLJgUZo4Ow6.jpg', '$2y$12$enPt/ZTsnz.AYZy3k/B6teEXm.BuHiKleRWPQbaGFFkiH53TAJO6S', NULL, '2025-07-16 21:45:34', '2025-07-17 02:18:06', 2, 1, 'active'),
(11, 'ilham', 'ilhamaisyinurrizki@gmail.com', '57468697767', NULL, '$2y$12$bG7V97Gms2gdzJji.2pMguXwkIeSbpaTUNrT7QvPDg84ZZpndlzmK', NULL, NULL, NULL, '2025-07-16 23:41:37', '2025-07-16 23:41:37', 7, 4, 'active');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `ar_partners`
--
ALTER TABLE `ar_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indeks untuk tabel `ar_partner_logs`
--
ALTER TABLE `ar_partner_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ar_partner_logs_ar_partner_id_foreign` (`ar_partner_id`),
  ADD KEY `ar_partner_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `banks_kode_bank_unique` (`kode_bank`),
  ADD UNIQUE KEY `banks_nama_bank_unique` (`nama_bank`),
  ADD UNIQUE KEY `banks_singkatan_unique` (`singkatan`);

--
-- Indeks untuk tabel `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_accounts_bank_id_foreign` (`bank_id`);

--
-- Indeks untuk tabel `bank_account_logs`
--
ALTER TABLE `bank_account_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_account_logs_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `bank_account_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `bank_logs`
--
ALTER TABLE `bank_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_logs_bank_id_foreign` (`bank_id`),
  ADD KEY `bank_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `bank_supplier_accounts`
--
ALTER TABLE `bank_supplier_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bank_supplier_accounts_supplier_id_bank_id_no_rekening_unique` (`supplier_id`,`bank_id`,`no_rekening`),
  ADD KEY `bank_supplier_accounts_bank_id_foreign` (`bank_id`);

--
-- Indeks untuk tabel `bisnis_partners`
--
ALTER TABLE `bisnis_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bisnis_partners_bank_id_foreign` (`bank_id`);

--
-- Indeks untuk tabel `bisnis_partner_logs`
--
ALTER TABLE `bisnis_partner_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bisnis_partner_logs_bisnis_partner_id_foreign` (`bisnis_partner_id`),
  ADD KEY `bisnis_partner_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conversations_user_one_id_user_two_id_unique` (`user_one_id`,`user_two_id`),
  ADD KEY `conversations_user_two_id_foreign` (`user_two_id`),
  ADD KEY `conversations_last_message_id_foreign` (`last_message_id`);

--
-- Indeks untuk tabel `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indeks untuk tabel `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_conversation_id_foreign` (`conversation_id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`);

--
-- Indeks untuk tabel `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_reads_message_id_foreign` (`message_id`),
  ADD KEY `message_reads_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `pengeluarans`
--
ALTER TABLE `pengeluarans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengeluaran_logs`
--
ALTER TABLE `pengeluaran_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengeluaran_logs_pengeluaran_id_foreign` (`pengeluaran_id`),
  ADD KEY `pengeluaran_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `pphs`
--
ALTER TABLE `pphs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pph_logs`
--
ALTER TABLE `pph_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pph_logs_pph_id_foreign` (`pph_id`),
  ADD KEY `pph_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suppliers_department_id_foreign` (`department_id`);

--
-- Indeks untuk tabel `supplier_logs`
--
ALTER TABLE `supplier_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_logs_supplier_id_foreign` (`supplier_id`),
  ADD KEY `supplier_logs_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_department_id_foreign` (`department_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `ar_partners`
--
ALTER TABLE `ar_partners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `ar_partner_logs`
--
ALTER TABLE `ar_partner_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `banks`
--
ALTER TABLE `banks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `bank_account_logs`
--
ALTER TABLE `bank_account_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `bank_logs`
--
ALTER TABLE `bank_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `bank_supplier_accounts`
--
ALTER TABLE `bank_supplier_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `bisnis_partners`
--
ALTER TABLE `bisnis_partners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `bisnis_partner_logs`
--
ALTER TABLE `bisnis_partner_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `message_reads`
--
ALTER TABLE `message_reads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengeluarans`
--
ALTER TABLE `pengeluarans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran_logs`
--
ALTER TABLE `pengeluaran_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pphs`
--
ALTER TABLE `pphs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pph_logs`
--
ALTER TABLE `pph_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `supplier_logs`
--
ALTER TABLE `supplier_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `ar_partners`
--
ALTER TABLE `ar_partners`
  ADD CONSTRAINT `fk_ar_partners_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ar_partner_logs`
--
ALTER TABLE `ar_partner_logs`
  ADD CONSTRAINT `ar_partner_logs_ar_partner_id_foreign` FOREIGN KEY (`ar_partner_id`) REFERENCES `ar_partners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ar_partner_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bank_account_logs`
--
ALTER TABLE `bank_account_logs`
  ADD CONSTRAINT `bank_account_logs_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_account_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `bank_logs`
--
ALTER TABLE `bank_logs`
  ADD CONSTRAINT `bank_logs_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `bank_supplier_accounts`
--
ALTER TABLE `bank_supplier_accounts`
  ADD CONSTRAINT `bank_supplier_accounts_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_supplier_accounts_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bisnis_partners`
--
ALTER TABLE `bisnis_partners`
  ADD CONSTRAINT `bisnis_partners_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `bisnis_partner_logs`
--
ALTER TABLE `bisnis_partner_logs`
  ADD CONSTRAINT `bisnis_partner_logs_bisnis_partner_id_foreign` FOREIGN KEY (`bisnis_partner_id`) REFERENCES `bisnis_partners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bisnis_partner_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_last_message_id_foreign` FOREIGN KEY (`last_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_user_one_id_foreign` FOREIGN KEY (`user_one_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_user_two_id_foreign` FOREIGN KEY (`user_two_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `message_reads`
--
ALTER TABLE `message_reads`
  ADD CONSTRAINT `message_reads_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengeluaran_logs`
--
ALTER TABLE `pengeluaran_logs`
  ADD CONSTRAINT `pengeluaran_logs_pengeluaran_id_foreign` FOREIGN KEY (`pengeluaran_id`) REFERENCES `pengeluarans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengeluaran_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pph_logs`
--
ALTER TABLE `pph_logs`
  ADD CONSTRAINT `pph_logs_pph_id_foreign` FOREIGN KEY (`pph_id`) REFERENCES `pphs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pph_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `supplier_logs`
--
ALTER TABLE `supplier_logs`
  ADD CONSTRAINT `supplier_logs_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
