-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Mar 01, 2026 at 05:28 PM
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
  `quant_instock` int NOT NULL,
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

INSERT INTO `inventory` (`id`, `ficha`, `sku`, `quant_instock`, `description`, `uom_primary`, `piece_count`, `length_inches`, `width_inches`, `height_inches`, `weight_lbs`, `assembly`, `rate`, `time_stamp`) VALUES
(1, 724, '1720813-0132', 200, 'MDF ST LX C2-- 2465X1245X05.7MM P/EF/132', 'BUNDLE', 250, 96.00, 39.00, 29.65, 3945.22, 'FALSE', 3945.22, '2026-02-22 15:42:18'),
(2, 12, '12345', 6666, 'N/A', 'UNIT', 0, 0.00, 0.00, 0.00, 0.00, 'FALSE', 0.00, '2026-02-22 15:42:24'),
(1001, 55, 'Red Oak', 80223, 'oak', 'EA', 1, 12.00, 10.00, 1.00, 0.50, '0', 15.00, '2026-02-22 15:42:30'),
(1002, 123412, '13412', 13411, 'Yes', 'Yes', 1234123, 135.00, 123.00, 123.00, 1232.00, 'Yes', 24.00, '2026-02-22 16:20:04'),
(1007, 101, 'SKU-001', 0, 'Red Chair', 'EA', 1, 24.50, 18.00, 36.00, 15.50, 'No', 99.99, '2026-03-01 03:42:56'),
(1008, 104, '99999999', 15, 'Grey Sofa', 'test', 123, 12.00, 12.00, 12.00, 12.00, 'test', 12.00, '2026-03-01 03:42:56'),
(1009, 101, 'SKU-001', 30, 'Red Chair', 'EA', 1, 24.50, 18.00, 36.00, 15.50, 'No', 99.99, '2026-03-01 04:36:07'),
(1010, 102, 'SKU-002', 20, 'Blue Table', 'EA', 1, 48.00, 24.00, 30.00, 25.00, 'No', 149.99, '2026-03-01 04:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `mpl`
--

CREATE TABLE `mpl` (
  `id` int NOT NULL,
  `order_number` int NOT NULL,
  `truck_number` int NOT NULL,
  `expected_delivery` varchar(200) NOT NULL,
  `status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mpl`
--

INSERT INTO `mpl` (`id`, `order_number`, `truck_number`, `expected_delivery`, `status`) VALUES
(2, 1341234, 1341234, '2026-02-11', 'received'),
(3, 42, 1, '2026-03-15', 'received'),
(4, 43, 2, '2026-04-01', 'received');

-- --------------------------------------------------------

--
-- Table structure for table `mpl_items`
--

CREATE TABLE `mpl_items` (
  `id` int NOT NULL,
  `mpl_id` int NOT NULL,
  `order_number` int DEFAULT NULL,
  `ficha` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `uom_primary` varchar(50) DEFAULT NULL,
  `piece_count` int DEFAULT NULL,
  `length_inches` decimal(10,2) DEFAULT NULL,
  `width_inches` decimal(10,2) DEFAULT NULL,
  `height_inches` decimal(10,2) DEFAULT NULL,
  `weight_lbs` decimal(10,2) DEFAULT NULL,
  `assembly` varchar(255) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mpl_items`
--

INSERT INTO `mpl_items` (`id`, `mpl_id`, `order_number`, `ficha`, `quantity`, `description`, `sku`, `uom_primary`, `piece_count`, `length_inches`, `width_inches`, `height_inches`, `weight_lbs`, `assembly`, `rate`) VALUES
(13, 4, NULL, 101, 20, 'Red Chair', 'SKU-001', 'EA', 1, 24.50, 18.00, 36.00, 15.50, 'No', 99.99),
(14, 4, NULL, 104, 15, 'Grey Sofa', '12345', 'test', 123, 12.00, 12.00, 12.00, 12.00, 'test', 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `reference_numb` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `ship_date` date DEFAULT NULL,
  `trailer_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `reference_numb`, `status`, `ship_date`, `trailer_name`) VALUES
(1, 42, 'shipped', '2026-03-15', 'TRK-001');

-- --------------------------------------------------------

--
-- Table structure for table `orders_items`
--

CREATE TABLE `orders_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `ficha` int DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `quantity_unit` varchar(50) DEFAULT NULL,
  `footage_quantity` int DEFAULT NULL,
  `uom_primary` varchar(50) DEFAULT NULL,
  `piece_count` int DEFAULT NULL,
  `length_inches` decimal(10,2) DEFAULT NULL,
  `width_inches` decimal(10,2) DEFAULT NULL,
  `height_inches` decimal(10,2) DEFAULT NULL,
  `weight_lbs` decimal(10,2) DEFAULT NULL,
  `assembly` varchar(255) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders_items`
--

INSERT INTO `orders_items` (`id`, `order_id`, `ficha`, `sku`, `description`, `quantity`, `quantity_unit`, `footage_quantity`, `uom_primary`, `piece_count`, `length_inches`, `width_inches`, `height_inches`, `weight_lbs`, `assembly`, `rate`) VALUES
(1, 1, 101, 'SKU-001', 'Red Chair', 10, 'EA', 5, 'EA', 1, 24.50, 18.00, 36.00, 15.50, 'No', 99.99),
(2, 1, 102, 'SKU-002', 'Blue Table', 5, 'EA', 3, 'EA', 1, 48.00, 24.00, 30.00, 25.00, 'No', 149.99);

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
(1, 'chloeto', '$2y$10$YEp4zpLBnUec4RXVxZjvneWDH934n5MDNcnTUqZRA8h1LzA0ioRju');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mpl`
--
ALTER TABLE `mpl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mpl_items`
--
ALTER TABLE `mpl_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mpl_id` (`mpl_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders_items`
--
ALTER TABLE `orders_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1011;

--
-- AUTO_INCREMENT for table `mpl`
--
ALTER TABLE `mpl`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mpl_items`
--
ALTER TABLE `mpl_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders_items`
--
ALTER TABLE `orders_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_mgmt`
--
ALTER TABLE `user_mgmt`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mpl_items`
--
ALTER TABLE `mpl_items`
  ADD CONSTRAINT `mpl_items_ibfk_1` FOREIGN KEY (`mpl_id`) REFERENCES `mpl` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders_items`
--
ALTER TABLE `orders_items`
  ADD CONSTRAINT `orders_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
