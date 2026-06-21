-- Coupon support for cart/checkout discounts and admin coupon management
-- MariaDB 10.4.32 compatible

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `coupon_products` (
  `coupon_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`coupon_id`, `product_id`),
  KEY `coupon_products_product_id` (`product_id`),
  CONSTRAINT `coupon_products_coupon_id_fk` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_products_product_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `coupon_categories` (
  `coupon_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`coupon_id`, `category_id`),
  KEY `coupon_categories_category_id` (`category_id`),
  CONSTRAINT `coupon_categories_coupon_id_fk` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_categories_category_id_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS add_coupon_order_columns()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'coupon_code'
  ) THEN
    ALTER TABLE `orders` ADD COLUMN `coupon_code` varchar(50) DEFAULT NULL AFTER `sgst`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'coupon_discount'
  ) THEN
    ALTER TABLE `orders` ADD COLUMN `coupon_discount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'coupon_id'
  ) THEN
    ALTER TABLE `orders` ADD COLUMN `coupon_id` int(11) DEFAULT NULL AFTER `coupon_discount`;
  END IF;
END//

CALL add_coupon_order_columns()//

DROP PROCEDURE IF EXISTS add_coupon_order_columns//

DELIMITER ;

