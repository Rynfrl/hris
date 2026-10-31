-- One-time migration of Inventory-specific fields and master data into hris.
-- Purchases and sales remain owned by the existing Purchasing and Sales modules.
INSERT INTO `roles` (`role_name`)
SELECT 'Admin_Warehouse'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE TRIM(`role_name`) = 'Admin_Warehouse');

INSERT INTO `roles` (`role_name`)
SELECT 'Warehouse_Staff'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE TRIM(`role_name`) = 'Warehouse_Staff');

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `products`
  ADD COLUMN `category_id` int UNSIGNED DEFAULT NULL AFTER `product_id`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `product_name`,
  ADD COLUMN `location` varchar(100) DEFAULT NULL AFTER `minimum_stock`,
  ADD KEY `idx_products_category_id` (`category_id`),
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `stock_movements`
  ADD COLUMN `supplier_id` int UNSIGNED DEFAULT NULL AFTER `reference_no`,
  ADD COLUMN `customer_id` int UNSIGNED DEFAULT NULL AFTER `supplier_id`,
  ADD COLUMN `created_by` int UNSIGNED DEFAULT NULL AFTER `customer_id`,
  ADD COLUMN `notes` text DEFAULT NULL AFTER `created_by`,
  ADD KEY `idx_sm_supplier` (`supplier_id`),
  ADD KEY `idx_sm_customer` (`customer_id`),
  ADD KEY `idx_sm_created_by` (`created_by`),
  ADD CONSTRAINT `fk_sm_supplier` FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sm_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL;

INSERT IGNORE INTO `categories` (`id`, `code`, `name`, `description`) VALUES
(1, 'CAT-EL', 'Elektronik', 'Peralatan elektronik kantor, laptop, gadget, dan aksesoris komputer'),
(2, 'CAT-FR', 'Perabot', 'Meja kerja, kursi ergonomis, lemari berkas, dan perabot kantor lainnya'),
(3, 'CAT-AT', 'Alat Kantor', 'Kertas HVS, pulpen, binder, map dokumen, dan alat tulis harian'),
(4, 'CAT-PL', 'Perlengkapan', 'Perlengkapan kebersihan gudang, packaging, lakban, dan kotak kardus');

INSERT IGNORE INTO `products`
  (`category_id`, `sku`, `product_name`, `description`, `unit`, `selling_price`,
   `purchase_price`, `stock`, `minimum_stock`, `location`, `is_active`, `created_at`)
VALUES
(1, 'EL-001', 'Laptop ThinkPad X1', 'Laptop bisnis ultra-ringan performa tinggi untuk manajerial', 'unit', 15000000.00, 12000000.00, 0.000, 5.000, 'Rak Elektronik A1', 1, '2026-10-11 13:29:45'),
(1, 'EL-002', 'Mouse Wireless M1', 'Mouse nirkabel ergonomis baterai tahan lama', 'pcs', 180000.00, 120000.00, 0.000, 10.000, 'Rak Aksesoris A2', 1, '2026-10-11 13:29:45'),
(2, 'AT-001', 'Meja Kerja 120 cm', 'Meja kerja kokoh minimalis kayu jati belanda', 'unit', 1250000.00, 850000.00, 0.000, 5.000, 'Gudang Furnitur F1', 1, '2026-10-11 13:29:45'),
(2, 'AT-002', 'Kursi Kantor Ergonomis', 'Kursi putar sandaran jaring lumbar support', 'unit', 1600000.00, 1100000.00, 0.000, 5.000, 'Gudang Furnitur F2', 1, '2026-10-11 13:29:45'),
(3, 'AK-001', 'Paper A4 80gsm', 'Kertas HVS putih bersih ukuran A4 isi 500 lembar per rim', 'rim', 58000.00, 45000.00, 0.000, 30.000, 'Rak ATK C1', 1, '2026-10-11 13:29:45'),
(3, 'AK-002', 'Pulpen Gel Hitam 0.5mm', 'Pulpen tinta gel hitam pekat anti macet 1 lusin', 'box', 42000.00, 28000.00, 0.000, 15.000, 'Rak ATK C2', 1, '2026-10-11 13:29:45'),
(1, 'EL-003', 'Keyboard Mechanical RGB', 'Keyboard mechanical switch tactile responsif dengan lampu RGB', 'unit', 680000.00, 450000.00, 0.000, 10.000, 'Rak Elektronik A3', 1, '2026-10-11 13:29:45'),
(1, 'EL-004', 'Monitor 24 Inch FHD', 'Monitor IPS 1080p 75Hz bezel-less proteksi mata', 'unit', 1950000.00, 1400000.00, 0.000, 5.000, 'Rak Elektronik A4', 1, '2026-10-11 13:29:45'),
(4, 'PK-001', 'Lakban Bening 2 Inch', 'Lakban perekat kuat serbaguna untuk packing karton', 'roll', 18000.00, 12000.00, 0.000, 20.000, 'Gudang Packing P1', 1, '2026-10-11 13:29:45'),
(4, 'PK-002', 'Kardus Box Ukuran Sedang', 'Kotak karton gelombang tebal ukuran 40x30x25 cm', 'pcs', 14000.00, 8500.00, 0.000, 50.000, 'Gudang Packing P2', 1, '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'EL-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 15.000, 'INV-OPEN-EL-001', 'Saldo awal sebelum riwayat transaksi Inventory', '2026-09-01 00:00:00');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'EL-002';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 4.000, 'INV-OPEN-EL-002', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'AT-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 12.000, 'INV-OPEN-AT-001', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'AK-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 30.000, 'INV-OPEN-AK-001', 'Saldo awal sebelum riwayat transaksi Inventory', '2026-09-01 00:00:00');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'AK-002';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 35.000, 'INV-OPEN-AK-002', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'EL-003';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 8.000, 'INV-OPEN-EL-003', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'EL-004';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 15.000, 'INV-OPEN-EL-004', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'PK-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 60.000, 'INV-OPEN-PK-001', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'PK-002';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'ADJUSTMENT_IN', 200.000, 'INV-OPEN-PK-002', 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'EL-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'PURCHASE', 5.000, 'PO-202609-001', 'Pembelian lama ERP Inventory; supplier: PT Maju Teknologi Sejahtera', '2026-09-10 12:00:00');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'EL-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'SALE', 2.000, 'INV-202609-001', 'Penjualan lama ERP Inventory; customer: PT Sinergi Media Digital', '2026-09-15 12:00:00');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'AK-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'PURCHASE', 100.000, 'PO-202609-002', 'Pembelian lama ERP Inventory; supplier: PT Paperindo Prima Mandiri', '2026-09-12 12:00:00');

SELECT `product_id` INTO @inventory_product_id FROM `products` WHERE `sku` = 'AK-001';
INSERT IGNORE INTO `stock_movements` (`product_id`, `type`, `qty`, `reference_no`, `notes`, `created_at`)
VALUES (@inventory_product_id, 'SALE', 10.000, 'INV-202609-002', 'Penjualan lama ERP Inventory; customer: CV Solusi Finansial Kita', '2026-09-16 12:00:00');
