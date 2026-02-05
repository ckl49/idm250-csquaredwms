-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Feb 05, 2026 at 06:57 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `idm250`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `ficha` int NOT NULL,
  `sku` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `uom_primary` varchar(150) NOT NULL,
  `piece_count` int NOT NULL,
  `length_inches` decimal(10,2) NOT NULL,
  `width_inches` decimal(10,2) NOT NULL,
  `height_inches` decimal(10,2) NOT NULL,
  `weight_lbs` decimal(10,2) NOT NULL,
  `assembly` varchar(50) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `time_stamp` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `ficha`, `sku`, `description`, `uom_primary`, `piece_count`, `length_inches`, `width_inches`, `height_inches`, `weight_lbs`, `assembly`, `rate`, `time_stamp`) VALUES
(1, 724, '1720813-0132', 'MDF ST LX C2-- 2465X1245X05.7MM P/EF/132', 'BUNDLE', 250, 96.00, 39.00, 29.65, 3945.22, 'FALSE', 3945.22, '2026-01-21 15:14:12'),
(2, 12, '12345', 'N/A', 'UNIT', 0, 0.00, 0.00, 0.00, 0.00, 'FALSE', 0.00, '2026-02-04 16:13:51'),
(123, 55, 'Oak', 'oak', 'EA', 1, 12.00, 10.00, 1.00, 0.50, '0', 15.00, '2026-02-04 16:33:28'),
(1001, 55, 'Red Oak', 'oak', 'EA', 1, 12.00, 10.00, 1.00, 0.50, '0', 15.00, '2026-02-04 16:32:48');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `ficha` int NOT NULL,
  `description1` varchar(200) NOT NULL,
  `description2` varchar(200) NOT NULL,
  `quantity` int NOT NULL,
  `quantity_unit` varchar(200) NOT NULL,
  `footage_quantity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`ficha`, `description1`, `description2`, `quantity`, `quantity_unit`, `footage_quantity`) VALUES
(1234, 'Vanilla Oak', 'A common, versatile building material obtained from oak trees, which are found in numerous biomes like plains and forests', 1, 'Bundle', 20);

-- --------------------------------------------------------

--
-- Table structure for table `user_mgmt`
--

CREATE TABLE `user_mgmt` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_mgmt`
--

INSERT INTO `user_mgmt` (`id`, `name`, `password`) VALUES
(1, 'chloeto', 'chloeto');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_mgmt`
--
ALTER TABLE `user_mgmt`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;

--
-- AUTO_INCREMENT for table `user_mgmt`
--
ALTER TABLE `user_mgmt`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
