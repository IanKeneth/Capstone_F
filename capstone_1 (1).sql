-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2026 at 12:24 PM
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
-- Database: `capstone_1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `profile_pic`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'Birondo Ian keneth', 'admin@gmail.com', '$2y$10$k.X15YJ7OwvCe5SDbt6UeOrmoZ5emji54o7vmQOASHbU1829m3Vj2', 'admin_1_1778680797.png', '2026-05-10 02:06:24', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `worker_name` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `qty_taken` int(11) NOT NULL,
  `qty_sold` int(11) NOT NULL,
  `qty_returned` int(11) NOT NULL,
  `received_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Completed Remittance',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `session_id`, `worker_name`, `product_id`, `product_name`, `qty_taken`, `qty_sold`, `qty_returned`, `received_amount`, `status`, `created_at`) VALUES
(1, 2, 'noy', 7, 'Stick Broom', 10, 8, 2, 240.00, 'Completed Remittance', '2026-05-10 13:29:41'),
(2, 3, 'Dodong', 7, 'Stick Broom', 20, 17, 3, 510.00, 'Completed Remittance', '2026-05-13 07:13:28'),
(3, 4, 'john', 7, 'Stick Broom', 10, 8, 2, 240.00, 'Completed Remittance', '2026-05-13 07:31:55'),
(4, 5, 'renz', 7, 'Stick Broom', 30, 25, 5, 750.00, 'Completed Remittance', '2026-05-13 08:09:45'),
(5, 6, 'ian', 7, 'Stick Broom', 5, 4, 1, 120.00, 'Completed Remittance', '2026-05-13 08:18:10'),
(6, 7, 'ian', 7, 'Stick Broom', 28, 20, 8, 600.00, 'Completed Remittance', '2026-05-13 08:26:30'),
(7, 8, 'biboy', 7, 'Stick Broom', 8, 4, 4, 120.00, 'Completed Remittance', '2026-05-13 08:54:54'),
(8, 9, 'panoy', 7, 'Stick Broom', 4, 2, 2, 60.00, 'Completed Remittance', '2026-05-13 09:00:25'),
(9, 19, 'noy', 8, 'Cob Web Broom', 9, 5, 4, 375.00, 'Completed Remittance', '2026-05-14 05:22:47'),
(10, 20, 'ian', 8, 'Cob Web Broom', 1, 1, 0, 75.00, 'Completed Remittance', '2026-05-14 05:29:49'),
(11, 21, 'noy', 8, 'Cob Web Broom', 9, 5, 4, 375.00, 'Completed Remittance', '2026-05-14 05:31:14'),
(12, 22, 'noy', 8, 'Cob Web Broom', 10, 5, 5, 375.00, 'Completed Remittance', '2026-05-14 05:48:02'),
(13, 23, 'dodon', 8, 'Cob Web Broom', 2, 0, 2, 0.00, 'Completed Remittance', '2026-05-14 11:39:36'),
(14, 24, 'ian', 7, 'Stick Broom', 5, 5, 0, 450.00, 'Completed Remittance', '2026-05-14 11:45:56'),
(15, 24, 'ian', 8, 'Cob Web Broom', 4, 4, 0, 450.00, 'Completed Remittance', '2026-05-14 11:45:56');

-- --------------------------------------------------------

--
-- Table structure for table `dispatch_items`
--

CREATE TABLE `dispatch_items` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty_taken` int(11) NOT NULL,
  `qty_sold` int(11) DEFAULT 0,
  `price_at_time` decimal(10,2) DEFAULT NULL,
  `qty_returned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispatch_items`
--

INSERT INTO `dispatch_items` (`id`, `session_id`, `product_id`, `qty_taken`, `qty_sold`, `price_at_time`, `qty_returned`) VALUES
(2, 1, 7, 20, 15, 30.00, 5),
(3, 2, 7, 10, 8, 30.00, 2),
(4, 3, 7, 20, 17, 30.00, 3),
(5, 4, 7, 10, 8, 30.00, 2),
(6, 5, 7, 30, 25, 30.00, 5),
(7, 6, 7, 5, 4, 30.00, 1),
(8, 7, 7, 28, 20, 30.00, 8),
(9, 8, 7, 8, 4, 30.00, 4),
(10, 9, 7, 4, 2, 30.00, 2),
(22, 19, 8, 9, 5, 75.00, 4),
(23, 20, 8, 1, 1, 75.00, 0),
(24, 21, 8, 9, 5, 75.00, 4),
(25, 22, 8, 10, 5, 75.00, 5),
(26, 23, 8, 2, 0, 75.00, 2),
(27, 24, 8, 4, 4, 75.00, 0),
(28, 24, 7, 5, 5, 30.00, 0),
(29, 25, 8, 5, 0, 75.00, 0),
(30, 25, 7, 1, 0, 30.00, 0),
(31, 26, 8, 3, 0, 75.00, 0),
(32, 26, 7, 3, 0, 30.00, 0),
(33, 27, 8, 2, 0, 75.00, 0),
(34, 27, 7, 2, 0, 30.00, 0),
(35, 28, 8, 5, 0, 75.00, 0),
(36, 29, 8, 1, 0, 75.00, 0),
(37, 28, 7, 2, 0, 30.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `dispatch_sessions`
--

CREATE TABLE `dispatch_sessions` (
  `id` int(11) NOT NULL,
  `worker_name` varchar(255) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `status` enum('Active','Completed') DEFAULT 'Active',
  `date_today` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_collected` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispatch_sessions`
--

INSERT INTO `dispatch_sessions` (`id`, `worker_name`, `product_id`, `status`, `date_today`, `created_at`, `total_collected`) VALUES
(1, 'ian', 1, 'Completed', '2026-05-10', '2026-05-10 08:19:10', 450.00),
(2, 'noy', 7, 'Completed', '2026-05-10', '2026-05-10 09:24:49', 240.00),
(3, 'Dodong', 7, 'Completed', '2026-05-13', '2026-05-13 05:39:45', 510.00),
(4, 'john', 7, 'Completed', '2026-05-13', '2026-05-13 07:31:42', 240.00),
(5, 'renz', 7, 'Completed', '2026-05-13', '2026-05-13 07:35:55', 750.00),
(6, 'ian', 7, 'Completed', '2026-05-13', '2026-05-13 08:17:57', 120.00),
(7, 'ian', 7, 'Completed', '2026-05-13', '2026-05-13 08:26:16', 600.00),
(8, 'biboy', 7, 'Completed', '2026-05-13', '2026-05-13 08:54:05', 120.00),
(9, 'panoy', 7, 'Completed', '2026-05-13', '2026-05-13 09:00:13', 60.00),
(12, 'ian', 7, 'Active', '2026-05-13', '2026-05-13 09:51:43', 0.00),
(13, 'huy', 7, 'Active', '2026-05-13', '2026-05-13 09:54:49', 0.00),
(14, 'boknoy', NULL, 'Active', '2026-05-14', '2026-05-14 04:52:03', 0.00),
(15, 'barbro', NULL, 'Active', '2026-05-14', '2026-05-14 04:55:47', 0.00),
(16, 'ian', NULL, 'Active', '2026-05-14', '2026-05-14 05:10:55', 0.00),
(17, 'renz', NULL, 'Active', '2026-05-14', '2026-05-14 05:13:59', 0.00),
(18, 'renz', NULL, 'Active', '2026-05-14', '2026-05-14 05:16:36', 0.00),
(19, 'noy', NULL, 'Completed', '2026-05-14', '2026-05-14 05:21:43', 375.00),
(20, 'ian', NULL, 'Completed', '2026-05-14', '2026-05-14 05:29:40', 75.00),
(21, 'noy', NULL, 'Completed', '2026-05-14', '2026-05-14 05:31:04', 375.00),
(22, 'noy', NULL, 'Completed', '2026-05-14', '2026-05-14 05:37:36', 375.00),
(23, 'dodon', NULL, 'Completed', '2026-05-14', '2026-05-14 11:22:03', 0.00),
(24, 'ian', NULL, 'Completed', '2026-05-14', '2026-05-14 11:22:37', 450.00),
(25, 'noy', NULL, 'Active', '2026-05-14', '2026-05-14 11:54:12', 0.00),
(26, 'balmond', NULL, 'Active', '2026-05-15', '2026-05-15 07:01:27', 0.00),
(27, 'biknoy', NULL, 'Active', '2026-05-15', '2026-05-15 09:53:10', 0.00),
(28, 'prk', NULL, 'Active', '2026-05-15', '2026-05-15 09:53:51', 0.00),
(29, 'noy', NULL, 'Active', '2026-05-15', '2026-05-15 09:54:11', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `action` enum('Added','Removed','Updated') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `product_id`, `admin_name`, `action`, `quantity_change`, `notes`, `created_at`) VALUES
(1, 7, 'Admin', 'Added', 100, 'Initial stock entry for Stick Broom', '2026-05-10 03:40:13'),
(2, 7, 'Admin', 'Removed', 10, 'Wholesale Dispatch to john (Session #4)', '2026-05-13 07:31:42'),
(3, 7, 'Admin', 'Removed', 30, 'Wholesale Dispatch to renz (Session #5)', '2026-05-13 07:35:55'),
(4, 7, 'Admin', 'Removed', 8, 'Wholesale Dispatch to biboy (Session #8)', '2026-05-13 08:54:05'),
(5, 7, 'System', 'Added', 4, 'Returned from Session #8', '2026-05-13 08:54:54'),
(6, 7, 'Admin', 'Removed', 2, 'Wholesale Dispatch to panoy (Session #9)', '2026-05-13 09:00:13'),
(7, 7, 'System', 'Added', 2, 'Returned from Session #9', '2026-05-13 09:00:25'),
(8, 7, 'Admin', 'Removed', 1, 'Retail Sale - Date: 2026-05-13', '2026-05-13 09:36:29'),
(9, 7, 'Admin', 'Removed', 2, 'Wholesale Dispatch - Session #12', '2026-05-13 09:51:43'),
(10, 7, 'Admin', 'Removed', 1, 'Wholesale Dispatch - Session #13', '2026-05-13 09:54:49'),
(11, 8, 'Birondo Ian keneth', 'Added', 100, 'Initial stock entry for Cob Web Broom', '2026-05-14 04:50:11'),
(12, 8, 'Admin', 'Removed', 5, 'Wholesale Dispatch - Session #14', '2026-05-14 04:52:03'),
(13, 7, 'Admin', 'Removed', 5, 'Wholesale Dispatch - Session #14', '2026-05-14 04:52:03'),
(14, 8, 'Admin', '', 2, 'Wholesale Dispatch - Session #15', '2026-05-14 04:55:47'),
(15, 7, 'Admin', '', 2, 'Wholesale Dispatch - Session #15', '2026-05-14 04:55:47'),
(16, 8, 'Admin', 'Removed', 3, 'Wholesale Dispatch - Session #16', '2026-05-14 05:10:55'),
(17, 8, 'Admin', 'Removed', 2, 'Wholesale Dispatch - Session #17', '2026-05-14 05:13:59'),
(18, 8, 'Admin', 'Removed', 50, 'Wholesale Dispatch - Session #18', '2026-05-14 05:16:36'),
(19, 8, 'System', 'Removed', 5, 'Wholesale Dispatch - Session #19', '2026-05-14 05:22:47'),
(20, 8, 'Admin', 'Removed', 1, 'Wholesale Dispatch - Session #20', '2026-05-14 05:29:49'),
(21, 8, 'System', 'Removed', 5, 'Wholesale Dispatch - Session #21', '2026-05-14 05:31:14'),
(22, 8, 'System', 'Added', 5, 'Returned from Session #22', '2026-05-14 05:48:02'),
(23, 8, 'System', 'Removed', 5, 'Wholesale Dispatch - Session #22', '2026-05-14 05:48:02'),
(24, 8, 'Birondo Ian keneth', 'Added', 5, 'Manual update from 75 to 80', '2026-05-14 10:22:51'),
(25, 8, 'Birondo Ian keneth', 'Removed', 2, 'Retail Sale - Date: 2026-05-14', '2026-05-14 11:21:37'),
(27, 8, 'System', 'Added', 2, 'Returned from Session #23', '2026-05-14 11:39:36'),
(28, 7, 'System', 'Removed', 5, 'Wholesale Dispatch - Session #24', '2026-05-14 11:45:56'),
(29, 8, 'System', 'Removed', 4, 'Wholesale Dispatch - Session #24', '2026-05-14 11:45:56'),
(30, 8, 'Birondo Ian keneth', 'Removed', 1, 'Retail Sale - Date: 2026-05-15', '2026-05-15 04:52:00'),
(31, 7, 'Birondo Ian keneth', 'Removed', 2, 'Retail Sale - Date: 2026-05-15', '2026-05-15 07:07:35'),
(32, 8, 'Admin', 'Removed', 2, 'Added to session #27 (biknoy)', '2026-05-15 09:53:10'),
(33, 7, 'Admin', 'Removed', 2, 'Added to session #27 (biknoy)', '2026-05-15 09:53:10'),
(34, 8, 'Admin', 'Removed', 5, 'Added to session #28 (prk)', '2026-05-15 09:53:51'),
(35, 8, 'Admin', 'Removed', 1, 'Added to session #29 (noy)', '2026-05-15 09:54:11'),
(36, 7, 'Birondo Ian keneth', 'Removed', 1, 'Added to session #28 (prk)', '2026-05-15 09:54:32');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variation` varchar(100) DEFAULT 'Standard',
  `description` text DEFAULT NULL,
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `max_quantity` int(11) NOT NULL DEFAULT 100,
  `image_path` varchar(255) DEFAULT 'default-product.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category`, `product_name`, `variation`, `description`, `wholesale_price`, `retail_price`, `quantity`, `max_quantity`, `image_path`, `created_at`) VALUES
(7, 'Brooms', 'Stick Broom', 'Stick Handle', 'Shord hanlde Stick Broom', 30.00, 40.00, 36, 100, '1778387122_Stick_Broom.jpeg', '2026-05-10 03:40:13'),
(8, 'Brooms', 'Cob Web Broom', 'Long Handle', 'A cobweb broom is a lightweight cleaning tool with a long handle and soft bristles, designed to remove dust, cobwebs, and debris from high ceilings, corners, and hard-to-reach areas without damaging surfaces.', 75.00, 100.00, 60, 100, '1778734211_Cob_Web_Broom.jpg', '2026-05-14 04:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `retail_orders`
--

CREATE TABLE `retail_orders` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `order_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retail_orders`
--

INSERT INTO `retail_orders` (`id`, `product_id`, `qty`, `subtotal`, `order_date`, `created_at`) VALUES
(1, 7, 2, 80.00, '2026-05-13', '2026-05-13 06:09:48'),
(2, 7, 1, 40.00, '2026-05-13', '2026-05-13 09:36:29'),
(3, 8, 2, 200.00, '2026-05-14', '2026-05-14 11:21:37'),
(4, 8, 2, 200.00, '2026-05-15', '2026-05-15 04:52:00'),
(5, 7, 2, 80.00, '2026-05-15', '2026-05-15 07:07:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dispatch_items`
--
ALTER TABLE `dispatch_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `dispatch_sessions`
--
ALTER TABLE `dispatch_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_log` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `retail_orders`
--
ALTER TABLE `retail_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `dispatch_items`
--
ALTER TABLE `dispatch_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `dispatch_sessions`
--
ALTER TABLE `dispatch_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `retail_orders`
--
ALTER TABLE `retail_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dispatch_items`
--
ALTER TABLE `dispatch_items`
  ADD CONSTRAINT `dispatch_items_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `dispatch_sessions` (`id`);

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `fk_product_log` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `retail_orders`
--
ALTER TABLE `retail_orders`
  ADD CONSTRAINT `retail_orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
