-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 07:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-com`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `createdby` int(11) NOT NULL,
  `updatedby` int(11) DEFAULT NULL,
  `createdat` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedat` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `is_enabled`, `createdby`, `updatedby`, `createdat`, `updatedat`) VALUES
(1, 'Electronics', 1, 5, 5, '2026-03-24 05:21:11', '2026-03-25 06:09:42');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `place` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `is_enabled` tinyint(1) DEFAULT 1,
  `createdby` int(11) DEFAULT 1,
  `updatedby` int(11) DEFAULT NULL,
  `createdat` datetime DEFAULT current_timestamp(),
  `updatedat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `name`, `phone`, `place`, `message`, `rating`, `is_enabled`, `createdby`, `updatedby`, `createdat`, `updatedat`) VALUES
(1, 'Prabhakaran', '6383786437', 'sivakasi', 'nice products i like it', 5, 1, 1, NULL, '2026-03-24 10:18:28', '2026-03-24 10:18:28');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `cgst` decimal(10,2) DEFAULT 0.00,
  `sgst` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('online','cod') NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','cod') DEFAULT 'pending',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `name`, `email`, `phone`, `address`, `city`, `state`, `pincode`, `subtotal`, `cgst`, `sgst`, `total`, `payment_method`, `payment_id`, `payment_status`, `status`, `updated_by`, `updated_at`, `created_at`) VALUES
(1, 'ORD-69CB9715783FD', 'prabha', 'viperprabhakaran@gmail.com', '6383786437', 'sedan kinatru street', 'sivakasi', 'Tamil Nadu', '626130', 4200.00, 105.00, 105.00, 4410.00, 'cod', NULL, 'cod', 'cancelled', NULL, '2026-03-31 10:14:30', '2026-03-31 09:42:45');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant_id`, `variant_name`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 2, 'Classical Smart watches', 11, 'Color: White', 1, 4200.00, '2026-03-31 09:42:45');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=Not Used, 1=Used',
  `createdat` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token`, `expires_at`, `used`, `createdat`) VALUES
(1, 2, 'f87b4a42586e28e97b34fdc22596269bcf8cc00788aac76c51ab792a6ad39b8d', '2026-03-22 18:35:54', 0, '2026-03-22 22:05:54'),
(2, 2, 'bcce8a4dff41b4dfcb7bdbdac6e99988b12c67a22841a6f9c283d3b1693d6836', '2026-03-23 04:49:45', 0, '2026-03-23 08:19:45'),
(3, 2, '77bd75ab91e6e109c1a07b73903db5d3c15cfe6f2a5d698c516f2f198735c14d', '2026-03-23 04:50:50', 0, '2026-03-23 08:20:50'),
(4, 2, '872906', '2026-03-23 04:06:45', 0, '2026-03-23 08:31:45'),
(5, 2, '705060', '2026-03-23 04:07:46', 1, '2026-03-23 08:32:46'),
(6, 2, '780958', '2026-03-23 18:21:47', 1, '2026-03-23 22:46:47'),
(7, 2, '777800', '2026-03-23 18:26:37', 1, '2026-03-23 22:51:37'),
(8, 2, '801070', '2026-03-24 05:46:16', 1, '2026-03-24 10:11:16');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `gst` varchar(50) DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `hsn_code` varchar(50) DEFAULT NULL,
  `is_active` enum('0','1') DEFAULT '1',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category_id`, `subcategory_id`, `quantity`, `price`, `image`, `rating`, `review_count`, `gst`, `weight`, `hsn_code`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `featured`) VALUES
(2, 'Classical Smart watches', 'Stylish black smart watch with modern design and essential features. It comes with fitness tracking, heart rate monitoring, and long-lasting battery life. Perfect for daily use with a comfortable strap and sleek look', 1, 1, 20, 4500.00, 'uploads/products/1774418916_watch 1.webp', 0.00, 0, '5', '200g', '5201', '1', 5, 5, '2026-03-25 06:48:43', '2026-03-26 04:16:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `Attribute` varchar(100) DEFAULT NULL,
  `Value` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `image` varchar(500) DEFAULT NULL,
  `is_active` enum('0','1') DEFAULT '1',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `Attribute`, `Value`, `price`, `stock`, `image`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(11, 2, 'SM02', 'Color', 'White', 4200.00, 10, 'uploads/products/1774494134_white.jpg', '1', 5, 5, '2026-03-26 04:02:14', '2026-03-26 04:02:14');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `createdby` int(11) NOT NULL,
  `updatedby` int(11) DEFAULT NULL,
  `createdat` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedat` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `name`, `category_id`, `is_enabled`, `createdby`, `updatedby`, `createdat`, `updatedat`) VALUES
(1, 'Smart Watches', 1, 1, 5, 5, '2026-03-24 06:22:58', '2026-03-25 06:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `place` varchar(255) DEFAULT NULL,
  `phonenumber` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` tinyint(4) NOT NULL DEFAULT 3 COMMENT '1=Super Admin, 2=Admin, 3=User',
  `createdby` int(11) DEFAULT NULL,
  `updatedby` int(11) DEFAULT NULL,
  `createdat` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `place`, `phonenumber`, `password`, `google_id`, `role`, `createdby`, `updatedby`, `createdat`, `updatedat`, `is_enabled`) VALUES
(1, 'Thrive Boost', 'thriveboosttech@gmail.com', 'sivakasi', '6383786437', '$2y$10$pTf941lweksalsmXjZhYaup5xEa/aksR0Ve13R6rZqbcFdBl.5riK', NULL, 1, 1, NULL, '2026-03-22 19:41:10', '2026-03-22 22:34:38', 1),
(2, 'Prabha', 'viperprabhakaran@gmail.com', 'sivakasi', '6383786437', '$2y$10$ixF3UcwcoSyv8SIdBQMtk.ZmfLOsr/lnxbyu1G2gGHRaZfXgMMgKm', NULL, 3, NULL, NULL, '2026-03-22 20:14:11', '2026-03-24 10:11:52', 1),
(3, 'prabha', 'thriveprabha@gmail.com', NULL, NULL, '$2y$10$7pCPIEgSPLkqujOhoF.myec0WbeSpGMxNCWLWXlm90vg2Z/3H.Hha', NULL, 3, NULL, NULL, '2026-03-23 13:32:24', '2026-03-23 13:32:24', 1),
(5, 'Prabhakaran', 'prabhakarans@anjaconline.org', 'Sivakasi', NULL, '$2y$10$TJ.6PIz3sUEIx0ywEs3shuxTeH3G5BVX6QRVSq71CtMiywJc7tSRW', NULL, 2, NULL, NULL, '2026-03-23 00:00:00', '2026-03-23 23:12:23', 1),
(6, 'Ganesh Krishna', 'gk3946020@gmail.com', 'Srivi', NULL, '$2y$10$w9DdfLslL228GYePxDxaA.cGh9i9E3bB3aJ0RqZAJeBvhlM8lfqx.', NULL, 2, NULL, NULL, '2026-03-23 00:00:00', '2026-03-23 23:03:31', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_subcategory_id` (`subcategory_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_category` (`name`,`category_id`),
  ADD KEY `category_idx` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `fk_subcat_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
