-- Apply once to an existing hris database to install the Sales module schema.
-- Sales uses the shared HRIS users table and the shared Purchasing product catalog.

INSERT INTO `roles` (`role_name`)
SELECT 'Admin_Sales'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Admin_Sales');

INSERT INTO `roles` (`role_name`)
SELECT 'Marketing_Staff'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Marketing_Staff');

INSERT INTO `products` (`sku`, `product_name`, `unit`, `selling_price`, `purchase_price`, `stock`, `is_active`)
SELECT 'SKU-001', 'Produk Contoh A', 'pcs', 50000.00, 0.00, 100.000, 1
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `sku` = 'SKU-001');

INSERT INTO `products` (`sku`, `product_name`, `unit`, `selling_price`, `purchase_price`, `stock`, `is_active`)
SELECT 'SKU-002', 'Produk Contoh B', 'pcs', 125000.00, 0.00, 50.000, 1
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `sku` = 'SKU-002');

INSERT INTO `products` (`sku`, `product_name`, `unit`, `selling_price`, `purchase_price`, `stock`, `is_active`)
SELECT 'SKU-003', 'Produk Contoh C', 'pcs', 75000.00, 0.00, 200.000, 1
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `sku` = 'SKU-003');

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_customer_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sales_orders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('draft','confirmed','invoiced','paid','cancelled') DEFAULT 'draft',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount` decimal(15,2) DEFAULT '0.00',
  `tax` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_order_no` (`order_no`),
  KEY `idx_sales_order_customer` (`customer_id`),
  KEY `idx_sales_order_creator` (`created_by`),
  CONSTRAINT `fk_sales_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sales_order_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sales_order_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sales_item_order` (`order_id`),
  KEY `idx_sales_item_product` (`product_id`),
  CONSTRAINT `fk_sales_item_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_invoice_no` (`invoice_no`),
  UNIQUE KEY `uq_sales_invoice_order` (`order_id`),
  CONSTRAINT `fk_sales_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int UNSIGNED NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` enum('cash','transfer','card') DEFAULT 'transfer',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sales_payment_invoice` (`invoice_id`),
  CONSTRAINT `fk_sales_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
