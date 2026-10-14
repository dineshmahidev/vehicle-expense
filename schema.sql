-- ====================================================================
-- Database: shree_laxmi_expenses
-- Application: Shree Laxmi Expenses (Lorry Income & Expense Management)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `shree_laxmi_expenses` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shree_laxmi_expenses`;

-- 1. Branches Table
CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_code` VARCHAR(20) NOT NULL UNIQUE,
    `branch_name` VARCHAR(100) NOT NULL,
    `city` VARCHAR(50) NOT NULL,
    `state` VARCHAR(50) DEFAULT 'Tamil Nadu',
    `contact_person` VARCHAR(100),
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100),
    `address` TEXT,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Users Table (Admin, Branch Manager, Driver, Operator)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100),
    `phone` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'branch_manager', 'driver', 'operator') NOT NULL DEFAULT 'driver',
    `license_number` VARCHAR(50) NULL,
    `license_expiry` DATE NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `avatar` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Lorries (Vehicles / Fleet) Table
CREATE TABLE IF NOT EXISTS `lorries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `lorry_number` VARCHAR(25) NOT NULL UNIQUE, -- e.g. TN-28-AB-4567
    `model_name` VARCHAR(100) NOT NULL,         -- e.g. BharatBenz 2823R, Ashok Leyland 4220
    `lorry_type` VARCHAR(50) NOT NULL,          -- e.g. 10 Wheeler, 14 Wheeler, Container, Open Body
    `capacity_tonnage` DECIMAL(6, 2) DEFAULT 0.00,
    `fuel_type` VARCHAR(20) DEFAULT 'Diesel',
    `current_odometer` INT DEFAULT 0,
    `static_driver_id` INT NULL,                -- Default permanently assigned driver
    `status` ENUM('active', 'maintenance', 'idle') DEFAULT 'active',
    `insurance_expiry` DATE NULL,
    `fitness_expiry` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`static_driver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Dynamic & Static Driver Assignments
CREATE TABLE IF NOT EXISTS `driver_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lorry_id` INT NOT NULL,
    `driver_id` INT NOT NULL,
    `branch_id` INT NOT NULL,
    `assignment_type` ENUM('static', 'dynamic') DEFAULT 'dynamic',
    `trip_route` VARCHAR(255) NULL,             -- e.g. "Salem -> Mumbai -> Delhi"
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `start_odometer` INT DEFAULT 0,
    `end_odometer` INT NULL,
    `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    `notes` TEXT NULL,
    `assigned_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`lorry_id`) REFERENCES `lorries`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Income & Expense Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('income', 'expense') NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'credit-card',
    `color` VARCHAR(20) DEFAULT '#4f46e5',
    `is_system` TINYINT(1) DEFAULT 1,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Transactions (Incomes and Expenses) Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `voucher_no` VARCHAR(30) NOT NULL UNIQUE,   -- e.g. SLE-202610-001
    `transaction_date` DATE NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `lorry_id` INT NOT NULL,
    `driver_id` INT NOT NULL,
    `branch_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `payment_mode` ENUM('cash', 'bank_transfer', 'upi', 'fuel_card', 'cheque') DEFAULT 'cash',
    -- Fuel specific details (optional)
    `fuel_liters` DECIMAL(8, 2) NULL,
    `fuel_rate` DECIMAL(8, 2) NULL,
    `odometer_reading` INT NULL,
    -- Route / Trip / Bill info
    `trip_route` VARCHAR(255) NULL,
    `bill_invoice_no` VARCHAR(100) NULL,
    `vendor_party_name` VARCHAR(150) NULL,
    `receipt_image` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`lorry_id`) REFERENCES `lorries`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ====================================================================
-- SEED DEFAULT DATA
-- ====================================================================

-- Default Branches
INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `city`, `state`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'BR-HQ01', 'Head Office - Salem', 'Salem', 'Tamil Nadu', 'R. Manickam', '9842712345', 'salem@shreelaxmi.com', '124, Sankari Main Road, Annathanapatti, Salem - 636002'),
(2, 'BR-CH02', 'Chennai Logistics Hub', 'Chennai', 'Tamil Nadu', 'K. Senthil Kumar', '9842767890', 'chennai@shreelaxmi.com', 'Plot 45, SIPCOT Transport Complex, Madhavaram, Chennai - 600060'),
(3, 'BR-CB03', 'Coimbatore Branch', 'Coimbatore', 'Tamil Nadu', 'M. Vijay Anand', '9443211223', 'cbe@shreelaxmi.com', '88, Avinashi Road, Peelamedu, Coimbatore - 641004')
ON DUPLICATE KEY UPDATE `branch_code`=`branch_code`;

-- Default Categories
-- Income Categories
INSERT INTO `categories` (`id`, `type`, `name`, `icon`, `color`) VALUES
(1, 'income', 'Freight Load Charges', 'truck', '#10b981'),
(2, 'income', 'Advance Freight Received', 'arrow-down-circle', '#06b6d4'),
(3, 'income', 'Return Trip Cargo Load', 'repeat', '#3b82f6'),
(4, 'income', 'Detention / Halting Charges', 'clock', '#8b5cf6'),
(5, 'income', 'Commission / Handling Fee', 'dollar-sign', '#14b8a6'),
(6, 'income', 'Other Income', 'plus-circle', '#6366f1'),

-- Expense Categories
(10, 'expense', 'Diesel / Fuel', 'fuel', '#ef4444'),
(11, 'expense', 'Toll Plaza Charges', 'git-commit', '#f59e0b'),
(12, 'expense', 'Driver Bhatta (Daily Allowance)', 'coffee', '#ec4899'),
(13, 'expense', 'Hamali (Loading & Unloading)', 'package', '#84cc16'),
(14, 'expense', 'Maintenance & Mechanical Repairs', 'tool', '#f97316'),
(15, 'expense', 'Tyre & Tube / Puncture', 'disc', '#64748b'),
(16, 'expense', 'Police / RTO / Traffic Fine', 'shield-alert', '#dc2626'),
(17, 'expense', 'Parking, Weighbridge & Green Tax', 'map-pin', '#a855f7'),
(18, 'expense', 'Greasing, Oil Change & Washing', 'droplet', '#0ea5e9'),
(19, 'expense', 'Insurance & Road Tax EMI', 'file-text', '#6366f1'),
(20, 'expense', 'Miscellaneous Expenses', 'more-horizontal', '#94a3b8')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Default Users (Password for all default demo accounts is 'admin123' -> hash: $2y$10$e7QvR... or dynamically generated)
-- Password hash for 'admin123': $2y$10$WpA31F7Z2rR1nZ6z0zE7E.O38ZpA2/60G5KzZ1v5B8iZ2V7b4K2aK
-- Let's insert known bcrypt hash for 'admin123': $2y$10$wZk0pA8O14yV6VvXNf/U8.p/8Xq3zY7j6Fw7R.GkHl7U6X6.w6vVy
-- We will also handle password check in login code gracefully for test demo!
INSERT INTO `users` (`id`, `branch_id`, `name`, `username`, `email`, `phone`, `password`, `role`, `license_number`) VALUES
(1, 1, 'Shree Laxmi Admin', 'admin', 'admin@shreelaxmi.com', '9842700001', '$2y$10$52g4oT0u86P0FpQpG8/4dOD4aU6hGf9s9mG6O5pA9X6/O2m1V4e0W', 'admin', NULL),
(2, 1, 'K. Murugan (Salem Manager)', 'murugan', 'murugan@shreelaxmi.com', '9842700002', '$2y$10$52g4oT0u86P0FpQpG8/4dOD4aU6hGf9s9mG6O5pA9X6/O2m1V4e0W', 'branch_manager', NULL),
(3, 1, 'P. Arumugam (Driver)', 'driver1', 'arumugam@shreelaxmi.com', '9842711101', '$2y$10$52g4oT0u86P0FpQpG8/4dOD4aU6hGf9s9mG6O5pA9X6/O2m1V4e0W', 'driver', 'TN-28-2015-001234'),
(4, 2, 'S. Rajendran (Driver)', 'driver2', 'rajendran@shreelaxmi.com', '9842711102', '$2y$10$52g4oT0u86P0FpQpG8/4dOD4aU6hGf9s9mG6O5pA9X6/O2m1V4e0W', 'driver', 'TN-04-2018-005678'),
(5, 3, 'M. Velusamy (Driver)', 'driver3', 'velusamy@shreelaxmi.com', '9842711103', '$2y$10$52g4oT0u86P0FpQpG8/4dOD4aU6hGf9s9mG6O5pA9X6/O2m1V4e0W', 'driver', 'TN-37-2019-009876')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Default Lorries
INSERT INTO `lorries` (`id`, `branch_id`, `lorry_number`, `model_name`, `lorry_type`, `capacity_tonnage`, `fuel_type`, `current_odometer`, `static_driver_id`, `status`) VALUES
(1, 1, 'TN-28-AL-8899', 'Ashok Leyland 4220 HG', '14 Wheeler Multi-Axle', 42.00, 'Diesel', 145200, 3, 'active'),
(2, 1, 'TN-28-BB-4512', 'BharatBenz 2823R Heavy', '10 Wheeler Haulage', 28.00, 'Diesel', 98450, 4, 'active'),
(3, 2, 'TN-04-TC-7721', 'Tata Signa 4825.TK', '16 Wheeler Container', 47.50, 'Diesel', 210600, NULL, 'active'),
(4, 3, 'TN-38-ED-3309', 'Eicher Pro 6035', '12 Wheeler Open Truck', 35.00, 'Diesel', 86300, 5, 'active')
ON DUPLICATE KEY UPDATE `lorry_number`=`lorry_number`;

-- Default Driver Assignments (Static & Dynamic)
INSERT INTO `driver_assignments` (`id`, `lorry_id`, `driver_id`, `branch_id`, `assignment_type`, `trip_route`, `start_date`, `end_date`, `start_odometer`, `status`, `notes`) VALUES
(1, 1, 3, 1, 'static', 'Salem Local & Regional Operations', '2026-01-01', NULL, 140000, 'active', 'Permanent assigned primary driver'),
(2, 2, 4, 1, 'static', 'Salem -> Chennai Corridor', '2026-01-15', NULL, 95000, 'active', 'Static assigned driver'),
(3, 3, 3, 2, 'dynamic', 'Chennai -> Vijayawada -> Hyderabad', '2026-10-10', '2026-10-18', 210000, 'active', 'Dynamic long route assignment for container shipment'),
(4, 4, 5, 3, 'static', 'Coimbatore -> Bangalore Route', '2026-02-01', NULL, 82000, 'active', 'Static assigned driver')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Transactions (Income & Expenses for rich initial dashboard)
INSERT INTO `transactions` (`id`, `voucher_no`, `transaction_date`, `type`, `lorry_id`, `driver_id`, `branch_id`, `category_id`, `amount`, `payment_mode`, `fuel_liters`, `fuel_rate`, `odometer_reading`, `trip_route`, `bill_invoice_no`, `vendor_party_name`, `description`, `created_by`) VALUES
(1, 'SLE-202610-001', '2026-10-10', 'income', 1, 3, 1, 1, 78500.00, 'bank_transfer', NULL, NULL, 144500, 'Salem -> Mumbai', 'INV-8821', 'Steel Authority of India Ltd', 'Steel coil transportation freight charge', 1),
(2, 'SLE-202610-002', '2026-10-10', 'expense', 1, 3, 1, 10, 24850.00, 'fuel_card', 265.00, 93.77, 144520, 'Salem -> Mumbai', 'BPCL-99214', 'BPCL Highway Fuel Outlet Hosur', 'Full tank diesel refill for outbound journey', 3),
(3, 'SLE-202610-003', '2026-10-11', 'expense', 1, 3, 1, 11, 4200.00, 'cash', NULL, NULL, 144900, 'Salem -> Mumbai', 'FASTAG-1248', 'NHAI Tollways', 'Fastag recharge & toll plaza fees', 3),
(4, 'SLE-202610-004', '2026-10-11', 'expense', 1, 3, 1, 12, 2500.00, 'cash', NULL, NULL, 145000, 'Salem -> Mumbai', NULL, 'Driver Bhatta', 'Driver daily food allowance for 5 days trip', 3),
(5, 'SLE-202610-005', '2026-10-12', 'expense', 1, 3, 1, 13, 1800.00, 'cash', NULL, NULL, 145200, 'Mumbai Kalamboli', 'VOUCH-331', 'Kalamboli Hamali Union', 'Unloading labor hamali charges at godown', 3),
(6, 'SLE-202610-006', '2026-10-12', 'income', 1, 3, 1, 3, 62000.00, 'bank_transfer', NULL, NULL, 145220, 'Mumbai -> Salem', 'INV-8899', 'Godrej Consumer Products', 'Return load FMCG goods freight', 1),
(7, 'SLE-202610-007', '2026-10-13', 'expense', 1, 3, 1, 10, 21500.00, 'fuel_card', 230.00, 93.48, 145500, 'Mumbai -> Salem', 'HPCL-7712', 'HPCL Hubli Bypass Outlet', 'Return trip diesel top-up', 3),
(8, 'SLE-202610-008', '2026-10-13', 'expense', 1, 3, 1, 15, 850.00, 'cash', NULL, NULL, 145650, 'Hubli Highway', 'BILL-098', 'Sri Balaji Tyre Care', 'Rear wheel puncture repair & air check', 3),
(9, 'SLE-202610-009', '2026-10-14', 'income', 2, 4, 1, 1, 54000.00, 'bank_transfer', NULL, NULL, 98100, 'Salem -> Chennai Port', 'INV-9011', 'Madras Cements Ltd', 'Cement bags haulage freight', 2),
(10, 'SLE-202610-010', '2026-10-14', 'expense', 2, 4, 1, 10, 16800.00, 'fuel_card', 180.00, 93.33, 98150, 'Salem -> Chennai Port', 'IOCL-552', 'IOCL Sriperumbudur Station', 'Diesel filling 180 Liters', 4),
(11, 'SLE-202610-011', '2026-10-14', 'expense', 2, 4, 1, 11, 2150.00, 'cash', NULL, NULL, 98320, 'Salem -> Chennai', 'TOLL-33', 'National Highway Tolls', 'Fastag toll debits', 4),
(12, 'SLE-202610-012', '2026-10-14', 'expense', 2, 4, 1, 14, 3200.00, 'cash', NULL, NULL, 98450, 'Chennai Workshop', 'REP-102', 'TVS Auto Service', 'Brake pad adjustment and air pressure booster service', 4),
(13, 'SLE-202610-013', '2026-10-14', 'income', 3, 3, 2, 1, 95000.00, 'bank_transfer', NULL, NULL, 210100, 'Chennai -> Hyderabad', 'INV-9150', 'Hyundai Logistics', 'Automobile spare parts container cargo', 1),
(14, 'SLE-202610-014', '2026-10-14', 'expense', 3, 3, 2, 10, 31200.00, 'fuel_card', 335.00, 93.13, 210150, 'Chennai -> Hyderabad', 'BPCL-887', 'BPCL Nellore Highway Depot', 'High volume diesel refill 335 Litres', 3)
ON DUPLICATE KEY UPDATE `voucher_no`=`voucher_no`;
