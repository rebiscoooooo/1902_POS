-- 1902 POS Database Schema
CREATE DATABASE IF NOT EXISTS `1902_pos` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `1902_pos`;

-- 1. settings
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT
);

-- Default Settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', '1902 POS Crawler Toy Store'),
('store_address', '123 Main Street, Manila, Philippines'),
('store_contact', '+63 900 123 4567'),
('receipt_footer', 'Thank you for shopping! Please come again.'),
('currency', '₱'),
('low_stock_threshold', '10');

-- 2. users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('ADMIN', 'PARTNER', 'STAFF') NOT NULL DEFAULT 'STAFF',
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME NULL
);

-- Default Admin User: admin / admin123
INSERT IGNORE INTO `users` (`name`, `username`, `email`, `password`, `role`) VALUES
('System Administrator', 'admin', 'admin@1902pos.local', '$2y$10$eE03V0c/0U1iL.2.j2e8.u3oM0V0T/o0H8cZ9q5J0t6.lM2Z5kX62', 'ADMIN'),
('Jane Cashier', 'staff', 'staff@1902pos.local', '$2y$10$eE03V0c/0U1iL.2.j2e8.u3oM0V0T/o0H8cZ9q5J0t6.lM2Z5kX62', 'STAFF'),
('John Partner', 'partner', 'partner@1902pos.local', '$2y$10$eE03V0c/0U1iL.2.j2e8.u3oM0V0T/o0H8cZ9q5J0t6.lM2Z5kX62', 'PARTNER');

-- 3. categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL
);

INSERT IGNORE INTO `categories` (`name`) VALUES
('Action Figures'), ('Vehicles'), ('Building Blocks'), ('Puzzles'), ('Educational');

-- 4. products
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(200) NOT NULL,
    `category_id` INT NULL,
    `description` TEXT NULL,
    `supplier` VARCHAR(100) NULL,
    `cost_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `reorder_level` INT NOT NULL DEFAULT 10,
    `image_path` VARCHAR(255) NULL,
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

-- Seed Products
INSERT IGNORE INTO `products` (`sku`, `name`, `category_id`, `cost_price`, `selling_price`, `stock_quantity`, `reorder_level`) VALUES
('TOY-001', 'Crawler Car', 2, 220.00, 350.00, 15, 5),
('TOY-002', 'Remote Control Truck', 2, 450.00, 750.00, 5, 5),
('TOY-003', 'Mini Construction Set', 3, 150.00, 299.00, 20, 10),
('TOY-004', 'Action Figure Hero', 1, 300.00, 499.00, 12, 5),
('TOY-005', 'Building Blocks Basic', 3, 320.00, 499.00, 12, 10),
('TOY-006', 'Toy Robot', 1, 600.00, 850.00, 8, 3),
('TOY-007', 'Die-Cast Car', 2, 100.00, 180.00, 50, 20),
('TOY-008', 'Puzzle Set 500pc', 4, 200.00, 350.00, 10, 5);

-- 5. sales
CREATE TABLE IF NOT EXISTS `sales` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_no` VARCHAR(50) NOT NULL UNIQUE,
    `cashier_id` INT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `payment` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `change_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'CASH',
    `status` ENUM('COMPLETED', 'VOID', 'REFUNDED') NOT NULL DEFAULT 'COMPLETED',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 6. sale_items
CREATE TABLE IF NOT EXISTS `sale_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sale_id` INT NOT NULL,
    `product_id` INT NULL,
    `product_name` VARCHAR(200) NOT NULL, -- Keep name in case product is deleted
    `quantity` INT NOT NULL,
    `cost_price` DECIMAL(10, 2) NOT NULL,
    `selling_price` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
);

-- 7. partners
CREATE TABLE IF NOT EXISTS `partners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE NULL,
    `name` VARCHAR(100) NOT NULL,
    `contact` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `commission_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

INSERT IGNORE INTO `partners` (`user_id`, `name`, `commission_rate`) VALUES
(3, 'John Partner', 10.00);

-- 8. partner_commissions
CREATE TABLE IF NOT EXISTS `partner_commissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `partner_id` INT NOT NULL,
    `sale_id` INT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('PENDING', 'PAID') NOT NULL DEFAULT 'PENDING',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL
);

-- 9. expenses
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `purpose` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `recorded_by` INT NULL,
    `expense_date` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 10. capital_transactions
CREATE TABLE IF NOT EXISTS `capital_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('CASH_IN', 'CASH_OUT', 'PARTNER_INVESTMENT', 'PARTNER_WITHDRAWAL', 'OWNER_INVESTMENT', 'OWNER_WITHDRAWAL') NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `purpose` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `partner_id` INT NULL,
    `recorded_by` INT NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Initial Capital
INSERT IGNORE INTO `capital_transactions` (`type`, `amount`, `purpose`, `recorded_by`, `transaction_date`) VALUES
('CASH_IN', 10000.00, 'Initial Register Cash', 1, CURRENT_TIMESTAMP),
('PARTNER_INVESTMENT', 50000.00, 'Initial Partner Investment', 1, CURRENT_TIMESTAMP);

-- 11. activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `activity_type` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
