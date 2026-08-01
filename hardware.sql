-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2026 at 01:36 AM
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
-- Database: `hardware`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `brand_name`) VALUES
(1, 'Nippon'),
(2, 'Multilac'),
(3, 'S-lon'),
(4, 'Orange'),
(5, 'Local / Unbranded');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Paints & Accessories', '2026-07-07 04:27:07'),
(2, 'Fasteners (Nuts/Bolts)', '2026-07-07 04:27:07'),
(3, 'Plumbing', '2026-07-07 04:27:07'),
(4, 'Electrical', '2026-07-07 04:27:07'),
(5, 'gardning', '2026-07-07 05:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `customer_name`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'Sunil Perera', '0773456289', 'dewmini@gmail.com', 'Mathara', '2026-06-24 09:05:47'),
(5, 'kamal d silva', '077 6541287', 'info@asianpaints.com', 'ganegoda,dehigasovita,hakmana', '2026-06-30 21:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'Employee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employee_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'Nethmi Dewmini', 'n@gmail.com', '$2y$10$AppM5SDIkhSGkGWQ6eRYJ.CuRyWXvEqvWispGpV68qMF2uj8jSLd6', 'Employee', '2026-06-24 08:09:17'),
(4, 'nirasha', 'nirasha@gmail.com', '$2y$10$AppM5SDIkhSGkGWQ6eRYJ.CuRyWXvEqvWispGpV68qMF2uj8jSLd6', 'Employee', '2026-06-30 22:56:44'),
(5, 'Tharidu', 'Tharidu@gmail.com', '$2y$10$2o8E.pdfr6HOjGxWhIOATOhL19xA7wntXKBQ.OEwudp8bRaj9M44u', 'Admin', '2026-06-30 23:02:45'),
(6, 'Amaya', 'amaya@gmail.com', '$2y$10$w7Yva0ECrfoxkJWYsAY9Luw67HSErzJohiX.EWt/UtmQFIAQOswwG', 'Employee', '2026-07-01 05:09:45');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `expense_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `expense_type`, `amount`, `expense_date`, `description`) VALUES
(1, 'Bills (Electricity / Current)', 4500.00, '2026-07-20', '');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `main_category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `measurement_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `measurement` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `supplier_cost` decimal(10,2) DEFAULT 0.00,
  `worker_cost` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `product_id`, `main_category_id`, `sub_category_id`, `brand_id`, `measurement_id`, `item_name`, `category_id`, `quantity`, `unit`, `measurement`, `price`, `supplier_cost`, `worker_cost`, `supplier_id`, `created_at`) VALUES
(7, 1, NULL, NULL, NULL, NULL, 'cilinder', NULL, 6, 'unit', NULL, 4500.00, 0.00, 0.00, NULL, '2026-07-01 21:48:02'),
(8, NULL, NULL, 1, 1, NULL, 'wall filler', 1, 49, 'L', '4L', 2750.00, 0.00, 0.00, 3, '2026-07-01 22:09:58'),
(9, 2, NULL, NULL, 5, NULL, 'bench', 5, 2, 'unit', '', 30000.00, 0.00, 0.00, 2, '2026-07-01 22:16:45'),
(10, NULL, NULL, 5, 5, NULL, 'nail 2\'\'', 2, 4, 'kg', '2 inch', 400.00, 0.00, 0.00, 2, '2026-07-02 23:17:43'),
(11, NULL, NULL, 1, 1, NULL, 'wall filler', 1, 0, 'L', '4L', 4000.00, 0.00, 0.00, 1, '2026-07-07 07:17:24');

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_measurements`
--

CREATE TABLE `item_measurements` (
  `measurement_id` int(11) NOT NULL,
  `sub_category_id` int(11) NOT NULL,
  `measurement_value` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `main_categories`
--

CREATE TABLE `main_categories` (
  `main_category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `employee_id`, `order_date`, `total_amount`, `status`) VALUES
(1, 1, 2, '2026-06-24', 0.00, 'Pending'),
(2, 1, 2, '2026-06-24', 0.00, 'Pending'),
(3, 1, 2, '2026-06-24', 0.00, 'Pending'),
(4, 1, 2, '2026-06-24', 0.00, 'Pending'),
(5, 1, 2, '2026-06-24', 0.00, 'Completed'),
(6, 1, 2, '2026-06-28', 0.00, 'Completed'),
(7, 1, 2, '2026-06-28', 0.00, 'Completed'),
(8, NULL, 2, '2026-06-29', 0.00, 'Completed'),
(9, NULL, 4, '2026-07-02', 0.00, 'Partially Paid'),
(10, NULL, 4, '2026-07-02', 0.00, 'Pending'),
(14, 5, 5, '2026-07-03', 0.00, 'Partially Paid'),
(15, 5, 4, '2026-07-13', 0.00, 'Completed'),
(16, NULL, 4, '2026-07-13', 0.00, 'Completed'),
(17, NULL, 4, '2026-07-20', 0.00, 'Partially Paid');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_id`, `quantity`, `price`) VALUES
(1, 2, 2, 1, 150.00),
(2, 3, 2, 1, 150.00),
(3, 4, 2, 1, 150.00),
(4, 4, 1, 2, 200.00),
(5, 5, 2, 1, 150.00),
(6, 6, 2, 1, 150.00),
(7, 6, 1, 1, 200.00),
(8, 7, 2, 5, 150.00),
(9, 7, 1, 10, 200.00),
(10, 8, 2, 85, 150.00),
(11, 9, 9, 1, 30000.00),
(12, 10, 7, 3, 4500.00),
(13, 14, 7, 1, 4500.00),
(14, 15, 9, 1, 30000.00),
(15, 16, 10, 1, 400.00),
(16, 17, 8, 1, 2750.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `balance_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` varchar(20) NOT NULL,
  `handled_by` varchar(100) NOT NULL,
  `total_supplier_share` decimal(10,2) DEFAULT 0.00,
  `total_worker_share` decimal(10,2) DEFAULT 0.00,
  `business_profit_share` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `payment_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `total_amount`, `gross_amount`, `discount_amount`, `net_amount`, `paid_amount`, `balance_amount`, `change_amount`, `payment_status`, `handled_by`, `total_supplier_share`, `total_worker_share`, `business_profit_share`, `payment_method`, `payment_date`) VALUES
(1, 5, 150.00, 0.00, 0.00, 0.00, 150.00, 0.00, 0.00, '', '', 0.00, 0.00, 150.00, 'Cash', '2026-06-24 11:40:59'),
(2, 7, 2750.00, 0.00, 0.00, 0.00, 3000.00, 250.00, 0.00, '', '', 0.00, 0.00, 2750.00, 'Cash', '2026-06-28 06:55:06'),
(3, 8, 12750.00, 0.00, 0.00, 0.00, 12750.00, 0.00, 0.00, '', '', 0.00, 0.00, 12750.00, 'Cash', '2026-06-29 06:27:35'),
(4, 6, 350.00, 0.00, 0.00, 0.00, 350.00, 0.00, 0.00, '', '', 0.00, 0.00, 350.00, 'Cheque', '2026-06-30 03:53:40'),
(5, 8, 12650.00, 12750.00, 100.00, 12650.00, 20000.00, 0.00, 7350.00, 'Paid', 'Cashier 01', 0.00, 0.00, 12650.00, 'Cash', '2026-07-02 06:13:35'),
(6, 9, 27500.00, 30000.00, 2500.00, 27500.00, 27500.00, 0.00, 0.00, 'Paid', 'Cashier 01', 0.00, 0.00, 27500.00, 'Cash', '2026-07-02 08:45:08'),
(7, 14, 4500.00, 4500.00, 0.00, 4500.00, 4500.00, 0.00, 0.00, 'Paid', 'Admin', 0.00, 0.00, 4500.00, 'Cash', '2026-07-03 05:40:05'),
(8, 15, 30000.00, 30000.00, 0.00, 30000.00, 30000.00, 0.00, 0.00, 'Paid', 'Cashier 01', 0.00, 0.00, 30000.00, 'Cash', '2026-07-13 09:32:53'),
(9, 16, 400.00, 400.00, 0.00, 400.00, 400.00, 0.00, 0.00, 'Paid', 'Admin', 0.00, 0.00, 400.00, 'Cash', '2026-07-13 10:09:23'),
(10, 17, 2750.00, 2750.00, 0.00, 2750.00, 2000.00, 750.00, 0.00, 'Pending', 'Cashier 01', 0.00, 0.00, 2750.00, 'Cash', '2026-07-20 02:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT 'default_craft.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `item_name`, `category`, `description`, `price`, `quantity`, `image`, `created_at`) VALUES
(1, 'cilinder', NULL, '', 4500.00, 10, '1782942482_6a458b1271673.jfif', '2026-07-01 21:48:02'),
(2, 'bench', NULL, '', 30000.00, 4, '1782944205_6a4591cd36bb3.jfif', '2026-07-01 22:16:45');

-- --------------------------------------------------------

--
-- Table structure for table `sub_categories`
--

CREATE TABLE `sub_categories` (
  `sub_category_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `sub_category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sub_categories`
--

INSERT INTO `sub_categories` (`sub_category_id`, `category_id`, `sub_category_name`) VALUES
(2, 1, 'Floor Paint'),
(1, 1, 'Wall Filler'),
(3, 1, 'Waterproof Paint'),
(5, 2, 'Brass Nails'),
(4, 2, 'Cement Nails'),
(6, 2, 'Wood Screws'),
(8, 3, 'PVC Pipes'),
(7, 3, 'Water Taps'),
(9, 4, 'LED Bulbs'),
(10, 4, 'Switches & Sockets');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `commission_rate` decimal(5,2) DEFAULT 18.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `created_at`, `commission_rate`) VALUES
(1, 'nippon paint lanka', 'Mr.perera', '0773456289', 'nethmidewmini@gmail.com', 'hansana,1st lane,galle rd,galle', '2026-06-24 08:39:27', 18.00),
(2, 'ABC Supplier', 'Mr.Kamal', '0773456288', 'neidewmini@gmail.com', 'Galle', '2026-06-24 09:01:02', 18.00),
(3, 'Asian paints', 'Mr.Ashoka', '0715632956', 'info@asianpaints.com', 'No.412,richmand rd,Panadura.', '2026-06-30 08:34:38', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `system_notes`
--

CREATE TABLE `system_notes` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `note_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_notes`
--

INSERT INTO `system_notes` (`id`, `employee_id`, `note_text`, `created_at`) VALUES
(1, '4', 'hi', '2026-07-20 20:49:24');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `shop_name` varchar(100) DEFAULT 'FixIt Crafts',
  `shop_address` varchar(255) DEFAULT 'Weligama, Sri Lanka',
  `shop_phone` varchar(20) DEFAULT '0771234567',
  `worker_rate` decimal(5,2) DEFAULT 10.00,
  `supplier_rate` decimal(5,2) DEFAULT 18.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `shop_name`, `shop_address`, `shop_phone`, `worker_rate`, `supplier_rate`) VALUES
(1, 'Fixit Hardware', 'Nugaduwa,Weligama, Sri Lanka', '0771234567', 10.00, 18.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `fk_inventory_products` (`product_id`),
  ADD KEY `fk_inv_main_cat` (`main_category_id`),
  ADD KEY `fk_inv_measure` (`measurement_id`),
  ADD KEY `fk_inv_category` (`category_id`),
  ADD KEY `fk_inv_sub_category` (`sub_category_id`),
  ADD KEY `fk_inv_brand` (`brand_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `item_measurements`
--
ALTER TABLE `item_measurements`
  ADD PRIMARY KEY (`measurement_id`),
  ADD KEY `sub_category_id` (`sub_category_id`);

--
-- Indexes for table `main_categories`
--
ALTER TABLE `main_categories`
  ADD PRIMARY KEY (`main_category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD PRIMARY KEY (`sub_category_id`),
  ADD UNIQUE KEY `unique_sub_cat` (`category_id`,`sub_category_name`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `system_notes`
--
ALTER TABLE `system_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_measurements`
--
ALTER TABLE `item_measurements`
  MODIFY `measurement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `main_categories`
--
ALTER TABLE `main_categories`
  MODIFY `main_category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sub_categories`
--
ALTER TABLE `sub_categories`
  MODIFY `sub_category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_notes`
--
ALTER TABLE `system_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inv_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_main_cat` FOREIGN KEY (`main_category_id`) REFERENCES `main_categories` (`main_category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_measure` FOREIGN KEY (`measurement_id`) REFERENCES `item_measurements` (`measurement_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_sub_cat` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`sub_category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`sub_category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventory_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`);

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `item_measurements`
--
ALTER TABLE `item_measurements`
  ADD CONSTRAINT `item_measurements_ibfk_1` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`sub_category_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`);

--
-- Constraints for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD CONSTRAINT `sub_categories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
