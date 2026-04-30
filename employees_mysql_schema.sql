-- =====================================================
-- MySQL Schema for Employees Table
-- =====================================================
-- Database: ecom
-- Table: employees
-- =====================================================

CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `role` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `salary` DECIMAL(12,2) NOT NULL,
  `joined_date` DATE NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employees_email` (`email`),
  KEY `idx_employees_department` (`department`),
  KEY `idx_employees_role` (`role`),
  KEY `idx_employees_status` (`status`),
  KEY `idx_employees_created_by` (`created_by`),
  KEY `idx_employees_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Data
-- =====================================================

INSERT INTO `employees` (`id`, `name`, `email`, `phone`, `role`, `department`, `salary`, `joined_date`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'john@luxora.com', '+1 234-567-8901', 'Store Manager', 'Operations', 65000.00, '2023-01-15', 'active', 1, NULL, '2023-01-15 09:00:00', '2023-01-15 09:00:00'),
(2, 'Sarah Johnson', 'sarah@luxora.com', '+1 234-567-8902', 'Sales Associate', 'Sales', 45000.00, '2023-03-20', 'active', 1, NULL, '2023-03-20 09:00:00', '2023-03-20 09:00:00'),
(3, 'Michael Brown', 'michael@luxora.com', '+1 234-567-8903', 'Warehouse Staff', 'Warehouse', 38000.00, '2023-05-10', 'active', 1, NULL, '2023-05-10 09:00:00', '2023-05-10 09:00:00'),
(4, 'Emily Davis', 'emily@luxora.com', '+1 234-567-8904', 'Customer Support', 'Support', 42000.00, '2023-07-01', 'active', 1, NULL, '2023-07-01 09:00:00', '2023-07-01 09:00:00'),
(5, 'David Wilson', 'david@luxora.com', '+1 234-567-8905', 'Delivery Driver', 'Logistics', 36000.00, '2023-08-15', 'active', 1, NULL, '2023-08-15 09:00:00', '2023-08-15 09:00:00'),
(6, 'Lisa Anderson', 'lisa@luxora.com', '+1 234-567-8906', 'Inventory Manager', 'Warehouse', 52000.00, '2023-09-01', 'active', 1, NULL, '2023-09-01 09:00:00', '2023-09-01 09:00:00'),
(7, 'James Taylor', 'james@luxora.com', '+1 234-567-8907', 'Cashier', 'Sales', 35000.00, '2023-10-20', 'inactive', 1, NULL, '2023-10-20 09:00:00', '2023-10-20 09:00:00'),
(8, 'Jennifer Martinez', 'jennifer@luxora.com', '+1 234-567-8908', 'HR Coordinator', 'Human Resources', 48000.00, '2024-01-10', 'active', 1, NULL, '2024-01-10 09:00:00', '2024-01-10 09:00:00'),
(9, 'Robert Garcia', 'robert@luxora.com', '+1 234-567-8909', 'Security Guard', 'Security', 32000.00, '2024-02-15', 'active', 1, NULL, '2024-02-15 09:00:00', '2024-02-15 09:00:00'),
(10, 'Amanda Lee', 'amanda@luxora.com', '+1 234-567-8910', 'Marketing Assistant', 'Marketing', 40000.00, '2024-03-01', 'active', 1, NULL, '2024-03-01 09:00:00', '2024-03-01 09:00:00');

-- =====================================================
-- API Usage Examples
-- =====================================================

-- GET all employees (with pagination, filters, search)
-- GET /employees?page=1&limit=10&search=john&department=Operations&role=Store Manager&status=active

-- GET single employee
-- GET /employees/1

-- CREATE employee
-- POST /employees
-- Body: {
--   "name": "New Employee",
--   "email": "new@luxora.com",
--   "phone": "+1 234-567-9999",
--   "role": "Sales Associate",
--   "department": "Sales",
--   "salary": 45000,
--   "joined_date": "2024-05-01",
--   "status": "active"
-- }

-- UPDATE employee
-- PUT /employees/1
-- Body: {
--   "salary": 70000,
--   "role": "Senior Store Manager"
-- }

-- TOGGLE employee status
-- PUT /employees/1/toggle-status

-- DELETE employee
-- DELETE /employees/1

-- GET employee statistics
-- GET /employees/stats
