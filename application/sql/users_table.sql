-- Users Table for E-commerce Application
-- Role: 1 = Super Admin, 2 = Admin, 3 = User

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `place` VARCHAR(255) DEFAULT NULL,
  `phonenumber` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `google_id` VARCHAR(255) DEFAULT NULL UNIQUE,
  `role` TINYINT NOT NULL DEFAULT 3 COMMENT '1=Super Admin, 2=Admin, 3=User',
  `createdby` INT DEFAULT NULL,
  `updatedby` INT DEFAULT NULL,
  `createdat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If google_id column doesn't exist, add it
ALTER TABLE `users` ADD COLUMN `google_id` VARCHAR(255) DEFAULT NULL UNIQUE AFTER `password`;

-- Insert default super admin (password: admin123 - hashed)
-- Note: Password is hashed using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`name`, `email`, `place`, `phonenumber`, `password`, `role`, `createdby`, `updatedby`) VALUES
('Super Admin', 'superadmin@example.com', 'Admin Office', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, NULL);

-- Insert default admin (password: admin123 - hashed)
INSERT INTO `users` (`name`, `email`, `place`, `phonenumber`, `password`, `role`, `createdby`, `updatedby`) VALUES
('Admin User', 'admin@example.com', 'Admin Office', '1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 1);

-- Insert sample user (password: user123 - hashed)
INSERT INTO `users` (`name`, `email`, `place`, `phonenumber`, `password`, `role`, `createdby`, `updatedby`) VALUES
('Test User', 'user@example.com', 'User City', '1234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 1);
