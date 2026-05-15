-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 08:42 AM
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
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `last_login`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$TBn1.J/2ysRt9RrlfsUPVeG8DDpjclwWtMCpuOLX0/oz97WTNXh9e', NULL, '2026-05-10 02:06:24');

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
(1, 2, 'noy', 7, 'Stick Broom', 10, 8, 2, 240.00, 'Completed Remittance', '2026-05-10 13:29:41');

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
(4, 3, 7, 20, 0, 30.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `dispatch_sessions`
--

CREATE TABLE `dispatch_sessions` (
  `id` int(11) NOT NULL,
  `worker_name` varchar(255) NOT NULL,
  `status` enum('Active','Completed') DEFAULT 'Active',
  `date_today` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_collected` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispatch_sessions`
--

INSERT INTO `dispatch_sessions` (`id`, `worker_name`, `status`, `date_today`, `created_at`, `total_collected`) VALUES
(1, 'ian', 'Completed', '2026-05-10', '2026-05-10 08:19:10', 450.00),
(2, 'noy', 'Completed', '2026-05-10', '2026-05-10 09:24:49', 240.00),
(3, 'Dodong', 'Active', '2026-05-13', '2026-05-13 05:39:45', 0.00);

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
(1, 7, 'Admin', 'Added', 100, 'Initial stock entry for Stick Broom', '2026-05-10 03:40:13');

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
(7, 'Brooms', 'Stick Broom', 'Stick Handle', 'Shord hanlde Stick Broom', 30.00, 40.00, 107, 100, '1778387122_Stick_Broom.jpeg', '2026-05-10 03:40:13');

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
(1, 7, 2, 80.00, '2026-05-13', '2026-05-13 06:09:48');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dispatch_items`
--
ALTER TABLE `dispatch_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dispatch_sessions`
--
ALTER TABLE `dispatch_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `retail_orders`
--
ALTER TABLE `retail_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
