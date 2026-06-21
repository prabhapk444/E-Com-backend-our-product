-- Redesign product variants for flexible shop types such as shoes, textiles, gifts, and electronics.
-- Keeps existing variant_attributes for backward compatibility and adds normalized fields for common options.

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS add_redesigned_product_variant_columns()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'product_type'
  ) THEN
    ALTER TABLE `products` ADD COLUMN `product_type` enum('simple','variable','shoes','textiles','gifts','digital','service') NOT NULL DEFAULT 'simple' AFTER `subcategory_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'variant_option_schema'
  ) THEN
    ALTER TABLE `products` ADD COLUMN `variant_option_schema` json NULL AFTER `product_type`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'product_variants' AND column_name = 'color'
  ) THEN
    ALTER TABLE `product_variants` ADD COLUMN `color` varchar(100) DEFAULT NULL AFTER `stock`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'product_variants' AND column_name = 'size'
  ) THEN
    ALTER TABLE `product_variants` ADD COLUMN `size` varchar(100) DEFAULT NULL AFTER `color`;
  END IF;
END//

CALL add_redesigned_product_variant_columns()//

DROP PROCEDURE IF EXISTS add_redesigned_product_variant_columns//

DELIMITER ;
