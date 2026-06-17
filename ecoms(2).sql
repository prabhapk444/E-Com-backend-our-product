USE `ecom`;

ALTER TABLE `products`
  ADD COLUMN `slug` varchar(255) DEFAULT NULL AFTER `name`,
  ADD COLUMN `images` longtext DEFAULT NULL AFTER `image`,
  ADD COLUMN `discount_price` decimal(10,2) DEFAULT NULL AFTER `price`,
  ADD COLUMN `discount_type` enum('percent','fixed') DEFAULT NULL AFTER `discount_price`;

ALTER TABLE `employees`
  ADD COLUMN `password` varchar(255) DEFAULT NULL AFTER `phone`,
  ADD COLUMN `last_login_at` datetime DEFAULT NULL AFTER `password`;

ALTER TABLE `password_reset_tokens`
  ADD COLUMN `employee_id` int(11) DEFAULT NULL AFTER `user_id`,
  ADD KEY `idx_employee_id` (`employee_id`);

CREATE TABLE `employee_access` (
  `employee_id` int(10) UNSIGNED NOT NULL,
  `module_key` varchar(100) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `can_status` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employee_id`,`module_key`),
  CONSTRAINT `fk_employee_access_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
