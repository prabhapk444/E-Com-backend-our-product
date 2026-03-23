-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 07:17 PM
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
(1, 'Prabha', '6383786437', 'sivakasi', 'nice products i like it', 5, 0, 1, NULL, '2026-03-23 23:25:51', '2026-03-23 23:44:29');

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
(7, 2, '777800', '2026-03-23 18:26:37', 1, '2026-03-23 22:51:37');

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
(2, 'Prabha', 'viperprabhakaran@gmail.com', 'sivakasi', '6383786437', '$2y$10$2.NrLJYCwUUfYsmtFLnGu..HH18.W.apkFcgaUGw4Pe6rAWEZU4Ie', NULL, 3, NULL, NULL, '2026-03-22 20:14:11', '2026-03-23 23:00:06', 1),
(3, 'prabha', 'thriveprabha@gmail.com', NULL, NULL, '$2y$10$7pCPIEgSPLkqujOhoF.myec0WbeSpGMxNCWLWXlm90vg2Z/3H.Hha', NULL, 3, NULL, NULL, '2026-03-23 13:32:24', '2026-03-23 13:32:24', 1),
(5, 'Prabhakaran', 'prabhakarans@anjaconline.org', 'Sivakasi', NULL, '$2y$10$TJ.6PIz3sUEIx0ywEs3shuxTeH3G5BVX6QRVSq71CtMiywJc7tSRW', NULL, 2, NULL, NULL, '2026-03-23 00:00:00', '2026-03-23 23:12:23', 1),
(6, 'Ganesh Krishna', 'gk3946020@gmail.com', 'Srivi', NULL, '$2y$10$w9DdfLslL228GYePxDxaA.cGh9i9E3bB3aJ0RqZAJeBvhlM8lfqx.', NULL, 2, NULL, NULL, '2026-03-23 00:00:00', '2026-03-23 23:03:31', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token` (`token`);

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
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
