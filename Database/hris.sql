-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: hris
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `activity_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_al_entity` (`entity_type`,`entity_id`),
  KEY `idx_al_user` (`user_id`),
  KEY `idx_al_created` (`created_at`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,NULL,'LOGIN','auth',3,'Login budi.santoso@company.co.id berhasil','::1','2026-09-23 13:50:09'),(2,NULL,'LOGIN','auth',3,'Login budi.santoso@company.co.id berhasil','::1','2026-09-30 13:12:14'),(3,NULL,'LOGIN','auth',8,'Login dannyindrapramana@gmail.com berhasil','::1','2026-10-04 23:02:29'),(4,NULL,'LOGOUT','auth',8,'Logout berhasil','::1','2026-10-04 23:02:42'),(5,NULL,'LOGIN','auth',8,'Login dannyindrapramana@gmail.com berhasil','::1','2026-10-04 23:03:01'),(6,NULL,'LOGOUT','auth',8,'Logout berhasil','::1','2026-10-05 00:15:04'),(7,NULL,'LOGIN','auth',10,'Login ravy@gmail.com berhasil','::1','2026-10-05 00:15:25'),(8,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-05 06:35:15'),(10,14,'LOGIN','auth',14,'Login salma@gmail.com berhasil','::1','2026-10-08 04:24:53'),(11,14,'LOGIN','auth',14,'Login salma@gmail.com berhasil','::1','2026-10-08 04:31:10'),(12,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-08 13:31:04'),(13,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-08 13:46:00'),(14,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-08 13:46:11'),(15,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-08 13:47:47'),(16,19,'LOGIN','auth',19,'Login afsaal@gmail.com berhasil','::1','2026-10-08 13:47:56'),(17,19,'LOGOUT','auth',19,'Logout berhasil','::1','2026-10-08 13:48:08'),(18,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-08 13:48:16'),(19,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-08 13:49:53'),(20,20,'LOGIN','auth',20,'Login zakie@gmail.com berhasil','::1','2026-10-08 13:49:59'),(21,20,'LOGOUT','auth',20,'Logout berhasil','::1','2026-10-08 14:03:55'),(22,19,'LOGIN','auth',19,'Login afsaal@gmail.com berhasil','::1','2026-10-08 14:04:02'),(23,19,'LOGOUT','auth',19,'Logout berhasil','::1','2026-10-08 14:04:55'),(24,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-08 14:05:06'),(25,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-08 14:13:03'),(26,19,'LOGIN','auth',19,'Login afsaal@gmail.com berhasil','::1','2026-10-08 14:13:09'),(27,19,'LOGOUT','auth',19,'Logout berhasil','::1','2026-10-08 14:56:45'),(28,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-08 14:56:57'),(29,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-11 22:16:54'),(30,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-11 22:18:32'),(31,21,'LOGIN','auth',21,'Login sabiq@gmail.com berhasil','::1','2026-10-11 22:18:42'),(32,21,'LOGOUT','auth',21,'Logout berhasil','::1','2026-10-11 22:39:27'),(33,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-11 22:39:43'),(34,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-11 22:39:46'),(35,14,'LOGIN','auth',14,'Login salma@gmail.com berhasil','::1','2026-10-11 22:39:52'),(36,14,'LOGOUT','auth',14,'Logout berhasil','::1','2026-10-11 22:40:14'),(37,18,'LOGIN','auth',18,'Login arafat@gmail.com berhasil','::1','2026-10-11 22:40:33'),(38,18,'LOGOUT','auth',18,'Logout berhasil','::1','2026-10-11 22:40:41'),(39,19,'LOGIN','auth',19,'Login afsaal@gmail.com berhasil','::1','2026-10-11 22:40:56'),(40,19,'LOGOUT','auth',19,'Logout berhasil','::1','2026-10-11 22:41:02'),(41,20,'LOGIN','auth',20,'Login zakie@gmail.com berhasil','::1','2026-10-11 22:41:18'),(42,20,'LOGOUT','auth',20,'Logout berhasil','::1','2026-10-11 22:41:22'),(43,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-11 22:41:37'),(44,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-11 22:43:22'),(45,22,'LOGIN','auth',22,'Login thio@gmail.com berhasil','::1','2026-10-11 22:43:37'),(46,22,'LOGOUT','auth',22,'Logout berhasil','::1','2026-10-11 22:51:53'),(47,21,'LOGIN','auth',21,'Login sabiq@gmail.com berhasil','::1','2026-10-11 22:52:02'),(48,21,'LOGOUT','auth',21,'Logout berhasil','::1','2026-10-11 22:57:23'),(49,21,'LOGIN','auth',21,'Login sabiq@gmail.com berhasil','::1','2026-10-11 22:57:32'),(50,21,'LOGOUT','auth',21,'Logout berhasil','::1','2026-10-11 22:57:36'),(51,22,'LOGIN','auth',22,'Login thio@gmail.com berhasil','::1','2026-10-11 22:57:45'),(52,22,'LOGOUT','auth',22,'Logout berhasil','::1','2026-10-11 22:57:47'),(53,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','::1','2026-10-11 22:57:56'),(54,9,'LOGOUT','auth',9,'Logout berhasil','::1','2026-10-11 23:03:22'),(55,21,'LOGIN','auth',21,'Login sabiq@gmail.com berhasil','::1','2026-10-11 23:03:32'),(56,21,'LOGOUT','auth',21,'Logout berhasil','::1','2026-10-11 23:03:42'),(57,19,'LOGIN','auth',19,'Login afsaal@gmail.com berhasil','::1','2026-10-13 08:18:46'),(58,19,'LOGOUT','auth',19,'Logout berhasil','::1','2026-10-13 08:18:55'),(59,21,'LOGIN','auth',21,'Login sabiq@gmail.com berhasil','::1','2026-10-13 08:19:13'),(60,21,'LOGOUT','auth',21,'Logout berhasil','::1','2026-10-13 08:19:40'),(61,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','127.0.0.1','2026-10-15 07:43:15'),(62,9,'LOGIN','auth',9,'Login yudha@gmail.com berhasil','127.0.0.1','2026-10-17 15:17:03'),(63,3,'EXPENSE_CREATED','expenses',8,'Membuat pengeluaran baru: Test Unit Pengeluaran Budi (Rp 50.000)',NULL,'2026-10-15 07:49:52'),(64,3,'REPORT_CREATED','expense_reports',6,'Membuat laporan biaya baru: Laporan Uji Otomatis Budi',NULL,'2026-10-15 07:49:52'),(65,3,'EXPENSE_CREATED','expenses',9,'Membuat pengeluaran baru: Item Laporan Uji (Rp 120.000)',NULL,'2026-10-15 07:49:52'),(66,3,'REPORT_ITEM_ADDED','expense_reports',6,'Menambahkan item EXP-2026-000001 ke laporan GTE-2026-000001',NULL,'2026-10-15 07:49:52'),(67,3,'REPORT_CREATED','expense_reports',7,'Membuat laporan biaya baru: Laporan Kedua',NULL,'2026-10-15 07:49:52'),(68,3,'REPORT_SUBMITTED','expense_reports',6,'Mengajukan laporan GTE-2026-000001 sebesar Rp 120.000',NULL,'2026-10-15 07:49:52'),(69,2,'REPORT_REFUSED','expense_reports',6,'Menolak laporan GTE-2026-000001 dengan alasan: Kuitansi tidak terbaca jelas. Mohon unggah ulang.',NULL,'2026-10-15 07:49:52'),(70,3,'REPORT_REVISED','expense_reports',6,'Mengembalikan laporan GTE-2026-000001 ke draft untuk revisi',NULL,'2026-10-15 07:49:52'),(71,2,'REPORT_APPROVED','expense_reports',6,'Menyetujui laporan GTE-2026-000001',NULL,'2026-10-15 07:49:52'),(72,5,'REPORT_POSTED','expense_reports',3,'Membukukan laporan GTE-DEMO-003',NULL,'2026-10-15 07:49:52'),(73,5,'REIMBURSEMENT_QUEUED','reimbursements',2,'Membuat antrean reimbursement untuk laporan GTE-DEMO-003',NULL,'2026-10-15 07:49:52'),(74,5,'REIMBURSEMENT_PAID','reimbursements',2,'Menyelesaikan pembayaran reimbursement laporan GTE-DEMO-003 (Rp 320.000)',NULL,'2026-10-15 07:49:52'),(75,3,'EXPENSE_CREATED','expenses',10,'Membuat pengeluaran baru: Test Unit Pengeluaran Budi (Rp 50.000)',NULL,'2026-10-17 15:24:44'),(76,3,'REPORT_CREATED','expense_reports',8,'Membuat laporan biaya baru: Laporan Uji Otomatis Budi',NULL,'2026-10-17 15:24:44'),(77,3,'EXPENSE_CREATED','expenses',11,'Membuat pengeluaran baru: Item Laporan Uji (Rp 120.000)',NULL,'2026-10-17 15:24:44'),(78,3,'REPORT_ITEM_ADDED','expense_reports',8,'Menambahkan item EXP-2026-000002 ke laporan GTE-2026-000003',NULL,'2026-10-17 15:24:44'),(79,3,'REPORT_CREATED','expense_reports',9,'Membuat laporan biaya baru: Laporan Kedua',NULL,'2026-10-17 15:24:44'),(80,3,'REPORT_SUBMITTED','expense_reports',8,'Mengajukan laporan GTE-2026-000003 sebesar Rp 120.000',NULL,'2026-10-17 15:24:44'),(81,2,'REPORT_REFUSED','expense_reports',8,'Menolak laporan GTE-2026-000003 dengan alasan: Kuitansi tidak terbaca jelas. Mohon unggah ulang.',NULL,'2026-10-17 15:24:44'),(82,3,'REPORT_REVISED','expense_reports',8,'Mengembalikan laporan GTE-2026-000003 ke draft untuk revisi',NULL,'2026-10-17 15:24:44'),(83,2,'REPORT_APPROVED','expense_reports',8,'Menyetujui laporan GTE-2026-000003',NULL,'2026-10-17 15:24:44');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned DEFAULT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module_record` (`module`,`record_id`),
  KEY `idx_audit_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,3,'UPDATE','expenses',8,'{\"amount\": \"50000.00\", \"description\": \"Test Unit Pengeluaran Budi\"}','{\"amount\": 60000, \"description\": \"Test Unit Pengeluaran Budi Diperbarui\"}',NULL,NULL,'2026-10-15 00:49:52'),(2,3,'UPDATE','expenses',10,'{\"amount\": \"50000.00\", \"description\": \"Test Unit Pengeluaran Budi\"}','{\"amount\": 60000, \"description\": \"Test Unit Pengeluaran Budi Diperbarui\"}',NULL,NULL,'2026-10-17 08:24:44');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'CAT-EL','Elektronik','Peralatan elektronik kantor, laptop, gadget, dan aksesoris komputer','2026-10-11 13:29:45'),(2,'CAT-FR','Perabot','Meja kerja, kursi ergonomis, lemari berkas, dan perabot kantor lainnya','2026-10-11 13:29:45'),(3,'CAT-AT','Alat Kantor','Kertas HVS, pulpen, binder, map dokumen, dan alat tulis harian','2026-10-11 13:29:45'),(4,'CAT-PL','Perlengkapan','Perlengkapan kebersihan gudang, packaging, lakban, dan kotak kardus','2026-10-11 13:29:45');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_customer_code` (`code`),
  UNIQUE KEY `uq_customers_customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (2,2,'CUST-0001','Gajah Duduk','CUST-0001','Gajah Duduk','duduk@company.co.id','212213214','JL. Industri Gajah Duduk','Bandung',1,'2026-10-08 07:14:21');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `department_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_code` (`department_code`),
  KEY `idx_departments_manager` (`manager_id`),
  CONSTRAINT `fk_departments_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'DEPT-IT','Teknologi Informasi',NULL,'Divisi IT & Sistem',1,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(2,'DEPT-FIN','Keuangan & Akuntansi',NULL,'Divisi Finance & Tax',1,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(3,'DEPT-OPS','Operasional & Bisnis',NULL,'Divisi Operasional',1,'2026-10-15 00:49:48','2026-10-15 00:49:48');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `divisi`
--

DROP TABLE IF EXISTS `divisi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisi` (
  `id_divisi` int NOT NULL AUTO_INCREMENT,
  `divisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_divisi`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `divisi`
--

LOCK TABLES `divisi` WRITE;
/*!40000 ALTER TABLE `divisi` DISABLE KEYS */;
INSERT INTO `divisi` VALUES (1,'HR'),(3,'Warehouse Staff'),(4,'Purchasing Staff'),(5,'Finance Manager'),(6,'Sales Marketing');
/*!40000 ALTER TABLE `divisi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id_employee` int NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned DEFAULT NULL,
  `employee_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ACTIVE',
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_user` int unsigned DEFAULT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `divisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Operasional',
  `jabatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gaji` decimal(15,2) NOT NULL,
  `tanggal_masuk` date NOT NULL,
  PRIMARY KEY (`id_employee`),
  UNIQUE KEY `uq_employees_employee_id` (`employee_id`),
  UNIQUE KEY `uq_employees_employee_number` (`employee_number`),
  UNIQUE KEY `uq_employees_email` (`email`),
  KEY `idx_employees_user` (`id_user`),
  KEY `fk_employees_department` (`department_id`),
  KEY `fk_employees_manager` (`manager_id`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,1,'EMP-0101','Rina Manajer','manager@example.test',NULL,3,NULL,'Engineering Manager','BCA','1234567890','2026-10-15','ACTIVE',NULL,'2026-10-15 00:49:48','2026-10-17 08:36:04',2,'Rina Manajer','Operasional','Engineering Manager',0.00,'2026-10-15'),(2,2,'EMP-0102','Budi Santoso','budi@example.test',NULL,3,1,'Senior Engineer','BCA','2345678901','2026-10-15','ACTIVE',NULL,'2026-10-15 00:49:48','2026-10-17 08:36:04',3,'Budi Santoso','Operasional','Senior Engineer',0.00,'2026-10-15'),(3,3,'EMP-0103','Sari Putri','sari@example.test',NULL,3,1,'Junior Analyst','Mandiri','3456789012','2026-10-15','ACTIVE',NULL,'2026-10-15 00:49:48','2026-10-17 08:36:04',4,'Sari Putri','Operasional','Junior Analyst',0.00,'2026-10-15'),(4,4,'EMP-0104','Dimas Finance','finance@example.test',NULL,2,NULL,'Finance Officer','BNI','4567890123','2026-10-15','ACTIVE',NULL,'2026-10-15 00:49:48','2026-10-17 08:36:04',5,'Dimas Finance','Operasional','Finance Officer',0.00,'2026-10-15'),(5,5,'EMP-0105','Admin Expenses','admin@example.test',NULL,1,NULL,'System Administrator','BCA','5678901234','2026-10-15','ACTIVE',NULL,'2026-10-15 00:49:48','2026-10-17 08:36:04',1,'Admin Expenses','Operasional','System Administrator',0.00,'2026-10-15'),(9,9,'EMP-HRIS-0009','Yudha Dwi',NULL,NULL,NULL,NULL,'HR Director',NULL,NULL,'2026-10-05','ACTIVE',NULL,'2026-10-17 08:35:27','2026-10-17 08:35:27',9,'Yudha Dwi','HR','HR Director',123000.00,'2026-10-05'),(11,11,'EMP-HRIS-0011','Salma Suhaimah',NULL,NULL,NULL,NULL,'Purchasing Director',NULL,NULL,'2026-10-05','ACTIVE',NULL,'2026-10-17 08:35:27','2026-10-17 08:35:27',14,'Salma Suhaimah','Purchasing Staff','Purchasing Director',123000.00,'2026-10-05'),(14,14,'EMP-HRIS-0014','Arafat Hamid',NULL,NULL,NULL,NULL,'Purchasing Manager',NULL,NULL,'2026-10-05','ACTIVE',NULL,'2026-10-17 08:35:27','2026-10-17 08:35:27',18,'Arafat Hamid','Purchasing Staff','Purchasing Manager',123000.00,'2026-10-05'),(15,15,'EMP-HRIS-0015','Afsaal Fayyaz',NULL,NULL,NULL,NULL,'Head Director',NULL,NULL,'2026-10-08','ACTIVE',NULL,'2026-10-17 08:35:27','2026-10-17 08:35:27',19,'Afsaal Fayyaz','Sales Marketing','Head Director',212000.00,'2026-10-08'),(16,16,'EMP-HRIS-0016','Zakie',NULL,NULL,NULL,NULL,'Marketing Officer',NULL,NULL,'2026-10-08','ACTIVE',NULL,'2026-10-17 08:35:27','2026-10-17 08:35:27',20,'Zakie','Sales Marketing','Marketing Officer',123000.00,'2026-10-08'),(17,17,'EMP-HRIS-0017','Halim',NULL,NULL,NULL,NULL,'Warehouse Officer',NULL,NULL,'2026-10-09','ACTIVE',NULL,'2026-10-17 08:35:27','2026-10-17 08:35:27',22,'Halim','Warehouse Staff','Warehouse Officer',123000.00,'2026-10-09');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_approvals`
--

DROP TABLE IF EXISTS `expense_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_approvals` (
  `approval_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned NOT NULL,
  `action` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_id`),
  KEY `idx_expense_approval_report` (`report_id`),
  KEY `idx_expense_approval_approver` (`approver_id`),
  CONSTRAINT `fk_expense_approval_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_approval_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_approvals`
--

LOCK TABLES `expense_approvals` WRITE;
/*!40000 ALTER TABLE `expense_approvals` DISABLE KEYS */;
INSERT INTO `expense_approvals` VALUES (1,2,4,'SUBMITTED','Laporan diajukan.','2026-10-15 00:49:48'),(2,3,2,'APPROVED','Disetujui.','2026-10-15 00:49:48'),(3,4,2,'REFUSED','Kuitansi belum resmi dari toko/merchant. Mohon lampirkan faktur atau nota resmi.','2026-10-15 00:49:48'),(4,5,2,'APPROVED','Disetujui.','2026-10-15 00:49:48'),(5,6,3,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-10-15 00:49:52'),(6,6,2,'REFUSED','Kuitansi tidak terbaca jelas. Mohon unggah ulang.','2026-10-15 00:49:52'),(7,6,3,'RETURNED_TO_DRAFT','Laporan dikembalikan ke status draft untuk revisi.','2026-10-15 00:49:52'),(8,6,3,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-10-15 00:49:52'),(9,6,2,'APPROVED','Sekarang sudah lengkap. Disetujui.','2026-10-15 00:49:52'),(10,3,5,'POSTED','Laporan telah dibukukan oleh tim Keuangan.','2026-10-15 00:49:52'),(11,3,5,'REIMBURSEMENT_PENDING','Dimasukkan ke antrean pembayaran reimbursement.','2026-10-15 00:49:52'),(12,3,5,'PAID','Reimbursement telah dibayarkan. Ref: TRF-TEST-20261011-999','2026-10-15 00:49:52'),(13,8,3,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-10-17 08:24:44'),(14,8,2,'REFUSED','Kuitansi tidak terbaca jelas. Mohon unggah ulang.','2026-10-17 08:24:44'),(15,8,3,'RETURNED_TO_DRAFT','Laporan dikembalikan ke status draft untuk revisi.','2026-10-17 08:24:44'),(16,8,3,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-10-17 08:24:44'),(17,8,2,'APPROVED','Sekarang sudah lengkap. Disetujui.','2026-10-17 08:24:44');
/*!40000 ALTER TABLE `expense_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `category_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_amount` decimal(15,2) DEFAULT NULL,
  `is_fixed_price` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'TRAVEL','Travel','Biaya perjalanan dinas',1,'2026-10-15 00:49:33','2026-10-15 00:49:33'),(2,'MEAL','Meal','Biaya makan',1,'2026-10-15 00:49:33','2026-10-15 00:49:33'),(3,'TRANSPORT','Transport','Biaya transportasi',1,'2026-10-15 00:49:33','2026-10-15 00:49:33'),(4,'OFFICE','Office','Biaya kebutuhan kantor',1,'2026-10-15 00:49:33','2026-10-15 00:49:33'),(5,'OTHER','Other','Biaya lainnya',1,'2026-10-15 00:49:33','2026-10-15 00:49:33');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_comments`
--

DROP TABLE IF EXISTS `expense_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_comments` (
  `comment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `comment_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_expense_comments_report_created` (`report_id`,`created_at`),
  KEY `fk_expense_comments_author` (`author_id`),
  CONSTRAINT `fk_expense_comments_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_comments_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_comments`
--

LOCK TABLES `expense_comments` WRITE;
/*!40000 ALTER TABLE `expense_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `expense_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_report_items`
--

DROP TABLE IF EXISTS `expense_report_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_report_items` (
  `report_item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `expense_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`report_item_id`),
  UNIQUE KEY `uq_report_expense` (`report_id`,`expense_id`),
  UNIQUE KEY `uq_expense_report_items_expense` (`expense_id`),
  CONSTRAINT `fk_report_item_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_report_item_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_report_items`
--

LOCK TABLES `expense_report_items` WRITE;
/*!40000 ALTER TABLE `expense_report_items` DISABLE KEYS */;
INSERT INTO `expense_report_items` VALUES (1,1,1),(2,1,2),(3,2,3),(4,2,4),(5,3,5),(6,4,6),(7,5,7),(8,6,9),(9,8,11);
/*!40000 ALTER TABLE `expense_report_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_reports`
--

DROP TABLE IF EXISTS `expense_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_reports` (
  `report_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REFUSED','POSTED','REIMBURSEMENT_PENDING','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `refused_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `report_number` (`report_number`),
  KEY `idx_expense_report_employee` (`employee_id`),
  KEY `idx_expense_report_status` (`status`),
  CONSTRAINT `fk_expense_report_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_reports`
--

LOCK TABLES `expense_reports` WRITE;
/*!40000 ALTER TABLE `expense_reports` DISABLE KEYS */;
INSERT INTO `expense_reports` VALUES (1,'GTE-DEMO-001',2,'Biaya Transport & Jamuan Budi',NULL,NULL,NULL,235000.00,'DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(2,'GTE-DEMO-002',3,'Kunjungan Lapangan Sari ke Bandung',NULL,NULL,NULL,625000.00,'SUBMITTED','2026-10-15 07:49:48',NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(3,'GTE-DEMO-003',2,'Pengadaan Kebutuhan Workshop Internal',NULL,NULL,NULL,320000.00,'PAID','2026-10-15 07:49:48','2026-10-15 07:49:48','2026-10-15 07:49:52','2026-10-15 07:49:52',NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:52'),(4,'GTE-DEMO-004',3,'Cinderamata Klien Sari (Ditolak)',NULL,NULL,NULL,600000.00,'REFUSED','2026-10-15 07:49:48',NULL,NULL,NULL,'2026-10-15 07:49:48','Kuitansi belum resmi dari toko/merchant. Mohon lampirkan faktur atau nota resmi.','2026-10-15 00:49:48','2026-10-15 00:49:48'),(5,'GTE-DEMO-005',2,'Operasional Transport Budi Bulan Lalu',NULL,NULL,NULL,250000.00,'PAID','2026-10-15 07:49:48','2026-10-15 07:49:48','2026-10-15 07:49:48','2026-10-15 07:49:48',NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(6,'GTE-2026-000001',2,'Laporan Uji Otomatis Budi','Uji alur submit - approve - refuse','2026-10-12','2026-10-15',120000.00,'APPROVED','2026-10-15 07:49:52','2026-10-15 07:49:52',NULL,NULL,NULL,NULL,'2026-10-15 00:49:52','2026-10-15 00:49:52'),(7,'GTE-2026-000002',2,'Laporan Kedua',NULL,NULL,NULL,0.00,'DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:52','2026-10-15 00:49:52'),(8,'GTE-2026-000003',2,'Laporan Uji Otomatis Budi','Uji alur submit - approve - refuse','2026-10-14','2026-10-17',120000.00,'APPROVED','2026-10-17 15:24:44','2026-10-17 15:24:44',NULL,NULL,NULL,NULL,'2026-10-17 08:24:44','2026-10-17 08:24:44'),(9,'GTE-2026-000004',2,'Laporan Kedua',NULL,NULL,NULL,0.00,'DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-17 08:24:44','2026-10-17 08:24:44');
/*!40000 ALTER TABLE `expense_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `expense_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `is_reimbursable` tinyint(1) NOT NULL DEFAULT '1',
  `is_reinvoiceable` tinyint(1) NOT NULL DEFAULT '0',
  `reinvoice_status` enum('NOT_INVOICED','READY_TO_INVOICE','INVOICED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_INVOICED',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REFUSED','POSTED','REIMBURSEMENT_PENDING','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `receipt_original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_stored_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_mime` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_size` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `paid_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`),
  UNIQUE KEY `expense_number` (`expense_number`),
  KEY `idx_expense_employee` (`employee_id`),
  KEY `idx_expense_category` (`category_id`),
  KEY `idx_expense_department` (`department_id`),
  KEY `idx_expense_customer` (`customer_id`),
  KEY `idx_expense_project` (`project_id`),
  KEY `idx_expense_status` (`status`),
  KEY `idx_expense_created_by` (`created_by`),
  KEY `fk_expense_updated_by` (`updated_by`),
  KEY `fk_expense_approved_by` (`approved_by`),
  KEY `fk_expense_rejected_by` (`rejected_by`),
  KEY `fk_expense_paid_by` (`paid_by`),
  CONSTRAINT `fk_expense_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'EXP-DEMO-001',2,2,'2026-10-13','Makan siang koordinasi tim',150000.00,'IDR','BANK_TRANSFER','BCA',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(2,'EXP-DEMO-002',2,3,'2026-10-14','Taksi Bluebird ke klien',85000.00,'IDR','BANK_TRANSFER','BCA',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(3,'EXP-DEMO-003',3,1,'2026-10-11','Tiket Kereta Argo Parahyangan PP',450000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','SUBMITTED',NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(4,'EXP-DEMO-004',3,2,'2026-10-12','Makan malam dinas Bandung',175000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','SUBMITTED',NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(5,'EXP-DEMO-005',2,4,'2026-10-09','Buku catatan & tinta printer workshop',320000.00,'IDR','BANK_TRANSFER','BCA',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,5,'2026-10-15 07:49:52','2026-10-15 00:49:48','2026-10-15 00:49:52'),(6,'EXP-DEMO-006',3,5,'2026-10-08','Pembelian cinderamata tanpa kuitansi resmi',600000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','REFUSED',NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(7,'EXP-DEMO-007',2,1,'2026-09-30','Bensin dinas luar kota',250000.00,'IDR','BANK_TRANSFER','BCA',NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:48','2026-10-15 00:49:48'),(9,'EXP-2026-000001',2,1,'2026-10-15','Item Laporan Uji',120000.00,'IDR','CASH',NULL,NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','APPROVED',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,2,'2026-10-15 07:49:52',NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:49:52','2026-10-15 00:49:52'),(11,'EXP-2026-000002',2,1,'2026-10-17','Item Laporan Uji',120000.00,'IDR','CASH',NULL,NULL,NULL,NULL,NULL,1,0,'NOT_INVOICED','APPROVED',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,2,'2026-10-17 15:24:44',NULL,NULL,NULL,NULL,NULL,'2026-10-17 08:24:44','2026-10-17 08:24:44');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipt_details`
--

DROP TABLE IF EXISTS `goods_receipt_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipt_details` (
  `goods_receipt_detail_id` int unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` int unsigned NOT NULL,
  `purchase_transaction_detail_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty_received` decimal(15,2) NOT NULL,
  `condition_note` enum('Good','Damaged','Shortage') NOT NULL DEFAULT 'Good',
  PRIMARY KEY (`goods_receipt_detail_id`),
  KEY `fk_grnd_grn` (`goods_receipt_id`),
  KEY `fk_grnd_po_detail` (`purchase_transaction_detail_id`),
  KEY `fk_grnd_product` (`product_id`),
  CONSTRAINT `fk_grnd_grn` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`goods_receipt_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grnd_po_detail` FOREIGN KEY (`purchase_transaction_detail_id`) REFERENCES `purchase_transaction_details` (`purchase_transaction_detail_id`),
  CONSTRAINT `fk_grnd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_details`
--

LOCK TABLES `goods_receipt_details` WRITE;
/*!40000 ALTER TABLE `goods_receipt_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `goods_receipt_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipts`
--

DROP TABLE IF EXISTS `goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipts` (
  `goods_receipt_id` int unsigned NOT NULL AUTO_INCREMENT,
  `grn_no` varchar(40) NOT NULL,
  `purchase_transaction_id` int unsigned NOT NULL,
  `received_by` int unsigned DEFAULT NULL,
  `received_date` date NOT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`goods_receipt_id`),
  UNIQUE KEY `grn_no` (`grn_no`),
  KEY `idx_grn_po` (`purchase_transaction_id`),
  KEY `fk_grn_user` (`received_by`),
  CONSTRAINT `fk_grn_po` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`purchase_transaction_id`),
  CONSTRAINT `fk_grn_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) NOT NULL,
  `order_id` int unsigned NOT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(150) NOT NULL COMMENT 'email yang dicoba login',
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_identifier_time` (`identifier`,`attempted_at`),
  KEY `idx_la_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notification_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_user_read` (`user_id`,`is_read`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` enum('cash','transfer','card') DEFAULT 'transfer',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sales_payment_invoice` (`invoice_id`),
  CONSTRAINT `fk_sales_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrolls`
--

DROP TABLE IF EXISTS `payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payrolls` (
  `id_payroll` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_employee` int NOT NULL,
  `periode` date NOT NULL,
  `total_gaji` decimal(15,2) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  PRIMARY KEY (`id_payroll`),
  KEY `fk_payroll_employee` (`id_employee`),
  CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`id_employee`) REFERENCES `employees` (`id_employee`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrolls`
--

LOCK TABLES `payrolls` WRITE;
/*!40000 ALTER TABLE `payrolls` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `permission_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','Melihat dashboard'),(2,'users.manage','Mengelola user'),(3,'employees.manage','Mengelola employee'),(4,'payrolls.manage','Mengelola payroll'),(5,'products.manage','Mengelola product'),(6,'customers.manage','Mengelola customer'),(7,'suppliers.manage','Mengelola supplier'),(8,'purchasing.manage','Mengelola purchasing'),(9,'inventory.manage','Mengelola inventory'),(10,'sales.manage','Mengelola sales'),(11,'invoices.manage','Mengelola invoice'),(12,'payments.manage','Mengelola payment'),(13,'expenses.manage','Mengelola expense'),(14,'expense.approve','Menyetujui expense'),(15,'reports.view','Melihat laporan'),(16,'settings.manage','Mengelola pengaturan'),(17,'expense.post','Mem-posting laporan expense yang telah disetujui'),(18,'expense.reimburse','Memproses reimbursement expense');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text,
  `unit` varchar(20) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `minimum_stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_name` (`product_name`),
  KEY `idx_products_stock` (`stock`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,NULL,'PRD-001','Kertas HVS A4 80gsm (rim)',NULL,'Rim',0.00,55000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(2,NULL,'PRD-002','Tinta Printer Epson 664 Black',NULL,'Botol',0.00,95000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(3,NULL,'PRD-003','Toner Cartridge HP 85A',NULL,'Unit',0.00,725000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(4,NULL,'PRD-004','Map Plastik Bening',NULL,'Pack',0.00,32000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(5,NULL,'PRD-005','Baterai AA Alkaline',NULL,'Pack',0.00,28000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(6,NULL,'PRD-006','Flashdisk 32GB',NULL,'Unit',0.00,65000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(7,NULL,'PRD-007','Amplop Coklat Folio',NULL,'Pack',0.00,21000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(8,NULL,'PRD-008','Stabilizer / UPS 600VA',NULL,'Unit',0.00,385000.00,0.000,0.000,NULL,1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(9,NULL,'SKU-001','Produk Contoh A',NULL,'pcs',50000.00,0.00,95.000,0.000,NULL,0,'2026-10-08 06:45:32','2026-10-11 22:19:17',NULL),(10,NULL,'SKU-002','Produk Contoh B',NULL,'pcs',125000.00,0.00,50.000,0.000,NULL,1,'2026-10-08 06:45:32','2026-10-08 06:45:32',NULL),(11,NULL,'SKU-003','Produk Contoh C',NULL,'pcs',75000.00,0.00,200.000,0.000,NULL,1,'2026-10-08 06:45:32','2026-10-08 06:45:32',NULL),(12,1,'EL-001','Laptop ThinkPad X1','Laptop bisnis ultra-ringan performa tinggi untuk manajerial','unit',15000000.00,12000000.00,18.000,5.000,'Rak Elektronik A1',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(13,1,'EL-002','Mouse Wireless M1','Mouse nirkabel ergonomis baterai tahan lama','pcs',180000.00,120000.00,4.000,10.000,'Rak Aksesoris A2',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(14,2,'AT-001','Meja Kerja 120 cm','Meja kerja kokoh minimalis kayu jati belanda','unit',1250000.00,850000.00,12.000,5.000,'Gudang Furnitur F1',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(15,2,'AT-002','Kursi Kantor Ergonomis','Kursi putar sandaran jaring lumbar support','unit',1600000.00,1100000.00,0.000,5.000,'Gudang Furnitur F2',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(16,3,'AK-001','Paper A4 80gsm','Kertas HVS putih bersih ukuran A4 isi 500 lembar per rim','rim',58000.00,45000.00,120.000,30.000,'Rak ATK C1',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(17,3,'AK-002','Pulpen Gel Hitam 0.5mm','Pulpen tinta gel hitam pekat anti macet 1 lusin','box',42000.00,28000.00,35.000,15.000,'Rak ATK C2',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(18,1,'EL-003','Keyboard Mechanical RGB','Keyboard mechanical switch tactile responsif dengan lampu RGB','unit',680000.00,450000.00,8.000,10.000,'Rak Elektronik A3',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(19,1,'EL-004','Monitor 24 Inch FHD','Monitor IPS 1080p 75Hz bezel-less proteksi mata','unit',1950000.00,1400000.00,15.000,5.000,'Rak Elektronik A4',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(20,4,'PK-001','Lakban Bening 2 Inch','Lakban perekat kuat serbaguna untuk packing karton','roll',18000.00,12000.00,60.000,20.000,'Gudang Packing P1',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL),(21,4,'PK-002','Kardus Box Ukuran Sedang','Kotak karton gelombang tebal ukuran 40x30x25 cm','pcs',14000.00,8500.00,200.000,50.000,'Gudang Packing P2',1,'2026-10-11 13:29:45','2026-10-11 13:29:45',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `project_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PLANNING','ACTIVE','COMPLETED','ON_HOLD','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `idx_projects_customer` (`customer_id`),
  CONSTRAINT `fk_projects_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_requisition_details`
--

DROP TABLE IF EXISTS `purchase_requisition_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_requisition_details` (
  `purchase_requisition_detail_id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_requisition_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` decimal(15,2) NOT NULL,
  `estimated_unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  PRIMARY KEY (`purchase_requisition_detail_id`),
  KEY `fk_prd_pr` (`purchase_requisition_id`),
  KEY `fk_prd_product` (`product_id`),
  CONSTRAINT `fk_prd_pr` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`purchase_requisition_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisition_details`
--

LOCK TABLES `purchase_requisition_details` WRITE;
/*!40000 ALTER TABLE `purchase_requisition_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_requisition_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_requisitions`
--

DROP TABLE IF EXISTS `purchase_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_requisitions` (
  `purchase_requisition_id` int unsigned NOT NULL AUTO_INCREMENT,
  `pr_no` varchar(40) NOT NULL,
  `requester_id` int unsigned DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `needed_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REJECTED','CONVERTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `notes` text,
  `approval_threshold_snapshot` decimal(15,2) DEFAULT NULL COMMENT 'nilai ambang batas approval matrix pada saat submit, untuk jejak audit',
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_requisition_id`),
  UNIQUE KEY `pr_no` (`pr_no`),
  KEY `idx_pr_status` (`status`),
  KEY `fk_pr_requester` (`requester_id`),
  KEY `fk_pr_approver` (`approved_by`),
  CONSTRAINT `fk_pr_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pr_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisitions`
--

LOCK TABLES `purchase_requisitions` WRITE;
/*!40000 ALTER TABLE `purchase_requisitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_requisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_returns` (
  `purchase_return_id` int unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(40) NOT NULL,
  `goods_receipt_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty_returned` decimal(15,2) NOT NULL,
  `condition` enum('Defective','Shortage','Wrong Item','Other') NOT NULL,
  `reason` text,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_return_id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `fk_pret_grn` (`goods_receipt_id`),
  KEY `fk_pret_product` (`product_id`),
  KEY `fk_pret_user` (`created_by`),
  CONSTRAINT `fk_pret_grn` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`goods_receipt_id`),
  CONSTRAINT `fk_pret_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `fk_pret_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_transaction_details`
--

DROP TABLE IF EXISTS `purchase_transaction_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_transaction_details` (
  `purchase_transaction_detail_id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_transaction_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  PRIMARY KEY (`purchase_transaction_detail_id`),
  KEY `fk_ptd_transaction` (`purchase_transaction_id`),
  KEY `fk_ptd_product` (`product_id`),
  CONSTRAINT `fk_ptd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `fk_ptd_transaction` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`purchase_transaction_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_transaction_details`
--

LOCK TABLES `purchase_transaction_details` WRITE;
/*!40000 ALTER TABLE `purchase_transaction_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_transaction_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_transactions`
--

DROP TABLE IF EXISTS `purchase_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_transactions` (
  `purchase_transaction_id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(40) NOT NULL,
  `purchase_requisition_id` int unsigned DEFAULT NULL,
  `supplier_id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SENT','CONFIRMED','PARTIALLY_RECEIVED','RECEIVED','INVOICED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_transaction_id`),
  UNIQUE KEY `transaction_no` (`transaction_no`),
  KEY `idx_pt_status` (`status`),
  KEY `idx_pt_supplier` (`supplier_id`),
  KEY `idx_pt_date` (`transaction_date`),
  KEY `fk_pt_pr` (`purchase_requisition_id`),
  KEY `fk_pt_user` (`user_id`),
  CONSTRAINT `fk_pt_pr` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`purchase_requisition_id`),
  CONSTRAINT `fk_pt_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_transactions`
--

LOCK TABLES `purchase_transactions` WRITE;
/*!40000 ALTER TABLE `purchase_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reimbursements`
--

DROP TABLE IF EXISTS `reimbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reimbursements` (
  `reimbursement_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('PENDING','PROCESSING','PAID','FAILED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `processed_by` bigint unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reimbursement_id`),
  UNIQUE KEY `uq_reimbursements_report` (`report_id`),
  KEY `idx_reimbursement_report` (`report_id`),
  KEY `idx_reimbursement_employee` (`employee_id`),
  KEY `idx_reimbursement_status` (`status`),
  KEY `fk_reimbursement_processed_by` (`processed_by`),
  CONSTRAINT `fk_reimbursement_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reimbursement_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reimbursement_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reimbursements`
--

LOCK TABLES `reimbursements` WRITE;
/*!40000 ALTER TABLE `reimbursements` DISABLE KEYS */;
INSERT INTO `reimbursements` VALUES (1,5,2,250000.00,'BANK_TRANSFER','TRF-DEMO-20261011-01','2026-10-15',NULL,'PAID',5,'2026-10-15 07:49:48','2026-10-15 00:49:48','2026-10-15 00:49:48'),(2,3,2,320000.00,'BANK_TRANSFER','TRF-TEST-20261011-999','2026-10-15','Pembayaran selesai melalui internet banking','PAID',5,'2026-10-15 07:49:52','2026-10-15 00:49:52','2026-10-15 00:49:52');
/*!40000 ALTER TABLE `reimbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (8,1),(13,1),(14,1),(15,1),(8,2),(8,3),(13,12),(8,13),(13,13),(14,13),(15,13),(14,14),(8,15),(13,15),(14,15),(15,15),(8,16),(13,17),(13,18);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_role` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned DEFAULT NULL,
  `role_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `uq_roles_name` (`role_name`),
  UNIQUE KEY `uq_roles_role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,1,'Admin_HR\r\n',NULL,'2026-10-17 08:35:27'),(2,2,'Admin_Purchase',NULL,'2026-10-17 08:35:27'),(3,3,'Purchasing_Staff',NULL,'2026-10-17 08:35:27'),(4,4,'Admin_Sales',NULL,'2026-10-17 08:35:27'),(5,5,'Marketing_Staff',NULL,'2026-10-17 08:35:27'),(6,6,'Admin_Warehouse',NULL,'2026-10-17 08:35:27'),(7,7,'Warehouse_Staff',NULL,'2026-10-17 08:35:27'),(8,8,'ADMIN','Administrator sistem','2026-10-17 08:35:27'),(9,9,'HR','Pengelola HR dan payroll','2026-10-17 08:35:27'),(10,10,'PURCHASING','Pengelola purchasing/procurement','2026-10-17 08:35:27'),(11,11,'WAREHOUSE','Pengelola gudang dan inventory','2026-10-17 08:35:27'),(12,12,'SALES','Pengelola penjualan','2026-10-17 08:35:27'),(13,13,'FINANCE','Pengelola finance dan expense','2026-10-17 08:35:27'),(14,14,'MANAGER','Approver/manajer','2026-10-17 08:35:27'),(15,15,'EMPLOYEE','Pengguna karyawan untuk modul GT Expense','2026-10-17 08:35:27');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_order_items`
--

DROP TABLE IF EXISTS `sales_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sales_item_order` (`order_id`),
  KEY `idx_sales_item_product` (`product_id`),
  CONSTRAINT `fk_sales_item_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_order_items`
--

LOCK TABLES `sales_order_items` WRITE;
/*!40000 ALTER TABLE `sales_order_items` DISABLE KEYS */;
INSERT INTO `sales_order_items` VALUES (1,1,9,5,50000.00,250000.00);
/*!40000 ALTER TABLE `sales_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('draft','confirmed','invoiced','paid','cancelled') DEFAULT 'draft',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount` decimal(15,2) DEFAULT '0.00',
  `tax` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_order_no` (`order_no`),
  KEY `idx_sales_order_customer` (`customer_id`),
  KEY `idx_sales_order_creator` (`created_by`),
  CONSTRAINT `fk_sales_order_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
/*!40000 ALTER TABLE `sales_orders` DISABLE KEYS */;
INSERT INTO `sales_orders` VALUES (1,'SO-20261008-0001',2,'2026-10-08','confirmed',250000.00,10.00,27500.00,277490.00,'',19,'2026-10-08 07:42:09','2026-10-08 07:42:12');
/*!40000 ALTER TABLE `sales_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `migration_id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migration_id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'001_gt_expense_integrity.sql','2026-10-17 08:37:40'),(2,'002_add_bank_name_to_expenses.sql','2026-10-17 08:37:40'),(3,'20261017_expenses_integration.sql','2026-10-17 08:37:40');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','ERP Company','2026-10-15 00:49:33'),(2,'currency','IDR','2026-10-15 00:49:33'),(3,'tax_rate','11','2026-10-15 00:49:33');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `stock_movement_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `type` enum('PURCHASE','PURCHASE_RETURN','SALE','SALES_RETURN','ADJUSTMENT_IN','ADJUSTMENT_OUT') NOT NULL,
  `qty` decimal(15,3) NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `supplier_id` int unsigned DEFAULT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_movement_id`),
  UNIQUE KEY `uq_sm_reference_product` (`reference_no`,`product_id`),
  KEY `idx_sm_product` (`product_id`),
  KEY `idx_sm_supplier` (`supplier_id`),
  KEY `idx_sm_customer` (`customer_id`),
  KEY `idx_sm_created_by` (`created_by`),
  KEY `idx_sm_reference` (`reference_no`),
  CONSTRAINT `fk_sm_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `fk_sm_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,9,'SALE',5.000,'SO-SO-20261008-0001-SALE',NULL,NULL,NULL,NULL,'2026-10-08 14:42:12'),(2,12,'ADJUSTMENT_IN',15.000,'INV-OPEN-EL-001',NULL,NULL,NULL,'Saldo awal sebelum riwayat transaksi Inventory','2026-09-01 00:00:00'),(3,13,'ADJUSTMENT_IN',4.000,'INV-OPEN-EL-002',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(4,14,'ADJUSTMENT_IN',12.000,'INV-OPEN-AT-001',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(5,16,'ADJUSTMENT_IN',30.000,'INV-OPEN-AK-001',NULL,NULL,NULL,'Saldo awal sebelum riwayat transaksi Inventory','2026-09-01 00:00:00'),(6,17,'ADJUSTMENT_IN',35.000,'INV-OPEN-AK-002',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(7,18,'ADJUSTMENT_IN',8.000,'INV-OPEN-EL-003',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(8,19,'ADJUSTMENT_IN',15.000,'INV-OPEN-EL-004',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(9,20,'ADJUSTMENT_IN',60.000,'INV-OPEN-PK-001',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(10,21,'ADJUSTMENT_IN',200.000,'INV-OPEN-PK-002',NULL,NULL,NULL,'Saldo awal migrasi dari ERP Inventory','2026-10-11 13:29:45'),(11,12,'PURCHASE',5.000,'PO-202609-001',NULL,NULL,NULL,'Pembelian lama ERP Inventory; supplier: PT Maju Teknologi Sejahtera','2026-09-10 12:00:00'),(12,12,'SALE',2.000,'INV-202609-001',NULL,NULL,NULL,'Penjualan lama ERP Inventory; customer: PT Sinergi Media Digital','2026-09-15 12:00:00'),(13,16,'PURCHASE',100.000,'PO-202609-002',NULL,NULL,NULL,'Pembelian lama ERP Inventory; supplier: PT Paperindo Prima Mandiri','2026-09-12 12:00:00'),(14,16,'SALE',10.000,'INV-202609-002',NULL,NULL,NULL,'Penjualan lama ERP Inventory; customer: CV Solusi Finansial Kita','2026-09-16 12:00:00');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_stock_movement_after_insert` AFTER INSERT ON `stock_movements` FOR EACH ROW UPDATE `products`
SET `stock` = `stock` + IF(
  NEW.`type` IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'),
  NEW.`qty`,
  -NEW.`qty`
)
WHERE `product_id` = NEW.`product_id` */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `supplier_id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `supplier_code` (`supplier_code`),
  KEY `idx_suppliers_name` (`supplier_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-001','PT Sumber Makmur Sejahtera','021-5551234','purchasing@sumbermakmur.co.id','Jl. Industri Raya No. 12, Jakarta Utara',1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(2,'SUP-002','CV Mitra Elektronik Nusantara','022-7778890','sales@mitraelektronik.co.id','Jl. Soekarno Hatta No. 88, Bandung',1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(3,'SUP-003','PT Anugerah Kertas Abadi','031-8889991','order@anugerahkertas.co.id','Jl. Rungkut Industri No. 5, Surabaya',1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL),(4,'SUP-004','UD Sinar Plastik Jaya','024-6667778','cs@sinarplastik.co.id','Jl. Kaligawe No. 21, Semarang',1,'2026-10-07 13:48:25','2026-10-07 13:48:25',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `role_id` int unsigned DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uq_users_user_id` (`user_id`),
  KEY `idx_users_role` (`role`),
  KEY `fk_users_role` (`role_id`),
  KEY `idx_users_employee` (`employee_id`),
  CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_role`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Gte','admin_gte','admin@example.test','$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy','ADMIN',1,'2026-10-15 07:49:48','2026-10-17 15:16:44',NULL,1,5,8,'2026-10-17 15:16:44'),(2,'Rina Manager','rina_manager','manager@example.test','$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy','MANAGER',1,'2026-10-15 07:49:48','2026-10-15 07:49:48',NULL,2,1,14,NULL),(3,'Budi Employee','budi_employee','budi@example.test','$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy','EMPLOYEE',1,'2026-10-15 07:49:48','2026-10-15 07:49:48',NULL,3,2,15,NULL),(4,'Sari Employee','sari_employee','sari@example.test','$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy','EMPLOYEE',1,'2026-10-15 07:49:48','2026-10-15 07:49:48',NULL,4,3,15,NULL),(5,'Dimas Finance','dimas_finance','finance@example.test','$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy','FINANCE',1,'2026-10-15 07:49:48','2026-10-15 07:49:48',NULL,5,4,13,NULL),(9,'Yudha Dwi','Yuraa_n','yudha@gmail.com','$2y$10$DHyunySX2O0t7Z/jt/3Jeedh9MkeY9Qpvk//.JwiUBeU4q7c5YE5u','Admin_HR',1,'2026-10-04 23:21:10','2026-10-17 15:35:27',NULL,9,9,1,'2026-10-17 15:17:03'),(14,'Salma Suhaimah','saruma','salma@gmail.com','$2y$10$PypH2eBZkk6b//vTLRRYNuz3TOIVGMEnqPs0VzKdJUbvzUpSMt4vK','Admin_Purchase',1,'2026-10-05 08:55:43','2026-10-17 15:35:27',NULL,14,11,2,'2026-10-11 22:39:52'),(18,'Arafat Hamid','alfckqih','arafat@gmail.com','$2y$10$ukUUPpFdl.uysAzap0rhkONcr/fZC7QH.MQtrTVuoFm9e4y7Ea.YW','Purchasing_Staff',1,'2026-10-08 06:47:09','2026-10-17 15:35:27',NULL,18,14,3,'2026-10-11 22:40:33'),(19,'Afsaal Fayyaz','fayyaz','afsaal@gmail.com','$2y$10$BByiUIEp9O7DvhW/6Mz20OtP9ePBRTzveTZpT8LNC1ll37wQMLWUS','Admin_Sales',1,'2026-10-08 13:46:50','2026-10-17 15:35:27',NULL,19,15,4,'2026-10-13 08:18:46'),(20,'Zakie Dinar','zakiee','zakie@gmail.com','$2y$10$mmaQw18P/Vl8PJtn.aBwuOWRjg/AbMLkZ8vpanh4f3mRI0XR1r4Ji','Marketing_Staff',1,'2026-10-08 13:49:04','2026-10-17 15:35:27',NULL,20,16,5,'2026-10-11 22:41:18'),(21,'Sabiq Hibatul','masbiku','sabiq@gmail.com','$2y$10$vmpoJuAiqumTgzV5L4yCnuul0/CkTTijsqbTetEpX2VDutsi7lqVa','Admin_Warehouse',1,'2026-10-11 22:17:34','2026-10-17 15:35:27',NULL,21,NULL,6,'2026-10-13 08:19:13'),(22,'Thio Halimawan','halimawan','thio@gmail.com','$2y$10$BZsZfZMCJ6XNcJhUMBTkpuoG2hD68k5Wl2HPFI1xTBWDFLVfhsXWW','Warehouse_Staff',1,'2026-10-11 22:43:09','2026-10-17 15:35:27',NULL,22,17,7,'2026-10-11 22:57:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_employee_summary`
--

DROP TABLE IF EXISTS `v_employee_summary`;
/*!50001 DROP VIEW IF EXISTS `v_employee_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_employee_summary` AS SELECT 
 1 AS `employee_id`,
 1 AS `employee_number`,
 1 AS `full_name`,
 1 AS `position`,
 1 AS `department_code`,
 1 AS `department_name`,
 1 AS `status`,
 1 AS `username`,
 1 AS `role_name`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_product_stock`
--

DROP TABLE IF EXISTS `v_product_stock`;
/*!50001 DROP VIEW IF EXISTS `v_product_stock`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_product_stock` AS SELECT 
 1 AS `product_id`,
 1 AS `sku`,
 1 AS `product_name`,
 1 AS `unit`,
 1 AS `stock`,
 1 AS `minimum_stock`,
 1 AS `is_low_stock`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `vendor_invoices`
--

DROP TABLE IF EXISTS `vendor_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_invoices` (
  `vendor_invoice_id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(60) NOT NULL COMMENT 'nomor invoice dari supplier',
  `purchase_transaction_id` int unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `invoice_date` date NOT NULL,
  `entered_by` int unsigned DEFAULT NULL,
  `match_status` enum('PENDING','MATCHED','MISMATCHED','EXCEPTION') NOT NULL,
  `match_notes` text COMMENT 'rincian selisih PO vs GRN vs Invoice jika mismatch',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REJECTED','PAID','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`vendor_invoice_id`),
  UNIQUE KEY `uq_vendor_invoice_po_no` (`purchase_transaction_id`,`invoice_no`),
  KEY `idx_vi_po` (`purchase_transaction_id`),
  KEY `fk_vi_entered_by` (`entered_by`),
  KEY `fk_vi_approved_by` (`approved_by`),
  CONSTRAINT `fk_vi_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vi_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vi_po` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`purchase_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_invoices`
--

LOCK TABLES `vendor_invoices` WRITE;
/*!40000 ALTER TABLE `vendor_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'hris'
--

--
-- Dumping routines for database 'hris'
--

--
-- Final view structure for view `v_employee_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_employee_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_employee_summary` AS select `e`.`employee_id` AS `employee_id`,`e`.`employee_number` AS `employee_number`,`e`.`full_name` AS `full_name`,`e`.`position` AS `position`,`d`.`department_code` AS `department_code`,`d`.`department_name` AS `department_name`,`e`.`status` AS `status`,`u`.`username` AS `username`,`r`.`role_name` AS `role_name` from (((`employees` `e` left join `departments` `d` on((`d`.`department_id` = `e`.`department_id`))) left join `users` `u` on((`u`.`employee_id` = `e`.`employee_id`))) left join `roles` `r` on((`r`.`role_id` = `u`.`role_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_product_stock`
--

/*!50001 DROP VIEW IF EXISTS `v_product_stock`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_product_stock` AS select `p`.`product_id` AS `product_id`,`p`.`sku` AS `sku`,`p`.`product_name` AS `product_name`,`p`.`unit` AS `unit`,`p`.`stock` AS `stock`,`p`.`minimum_stock` AS `minimum_stock`,(case when (`p`.`stock` <= `p`.`minimum_stock`) then 1 else 0 end) AS `is_low_stock` from `products` `p` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-17 15:42:37
