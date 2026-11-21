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
-- Current Database: `hris`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `hris` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `hris`;

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
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,6,'LOGIN','auth',6,'Login Yuraa_n (HR Director) berhasil','127.0.0.1','2026-11-20 08:15:00'),(2,1,'LOGIN','auth',1,'Login admin_gte (Administrator GT Expense) berhasil','127.0.0.1','2026-11-20 08:30:00'),(3,2,'LOGIN','auth',2,'Login rina_manager (Manager Finance) berhasil','127.0.0.1','2026-11-20 08:45:00'),(4,3,'LOGIN','auth',3,'Login budi_employee (Staff IT) berhasil','127.0.0.1','2026-11-20 09:00:00'),(5,5,'LOGIN','auth',5,'Login dimas_finance (Treasury) berhasil','127.0.0.1','2026-11-20 09:15:00'),(6,7,'CREATE','purchase_requisition',1,'Membuat pengajuan PR-202611-0001 kebutuhan IT','127.0.0.1','2026-11-20 09:30:00'),(7,2,'APPROVE','expense_reports',1,'Menyetujui laporan biaya perjalanan dinas EXP-202611-0001','127.0.0.1','2026-11-20 10:00:00'),(8,5,'POST','expense_reports',1,'Membukukan laporan EXP-202611-0001 ke jurnal umum JRN-EXP-202611-0001','127.0.0.1','2026-11-20 10:30:00'),(9,5,'PAY','expense_reimbursements',1,'Menyelesaikan pembayaran reimbursement PAY-202611-0001 sebesar Rp 1.950.000','127.0.0.1','2026-11-20 11:00:00'),(10,11,'STOCK_ADJUSTMENT','products',1,'Verifikasi fisik stok persediaan barang gudang','127.0.0.1','2026-11-20 11:30:00'),(11,6,'LOGIN','auth',6,'Login yudha@gmail.com berhasil','127.0.0.1','2026-11-21 17:41:56'),(12,6,'LOGIN','auth',6,'Login yudha@gmail.com berhasil','127.0.0.1','2026-11-21 17:44:54'),(13,6,'LOGIN','auth',6,'Login yudha@gmail.com berhasil','127.0.0.1','2026-11-21 17:54:09'),(14,6,'LOGIN','auth',6,'Login yudha@gmail.com berhasil','127.0.0.1','2026-11-21 18:00:08'),(15,6,'LOGOUT','auth',6,'Logout berhasil','127.0.0.1','2026-11-21 18:00:23'),(16,7,'LOGIN','auth',7,'Login salma@gmail.com berhasil','127.0.0.1','2026-11-21 18:00:40'),(17,7,'LOGOUT','auth',7,'Logout berhasil','127.0.0.1','2026-11-21 18:01:01'),(18,11,'LOGIN','auth',11,'Login sabiq@gmail.com berhasil','127.0.0.1','2026-11-21 18:01:25'),(19,11,'LOGOUT','auth',11,'Logout berhasil','127.0.0.1','2026-11-21 18:01:44'),(20,1,'LOGIN','auth',1,'Login admin@example.test berhasil','127.0.0.1','2026-11-21 18:02:01'),(21,5,'LOGIN','auth',5,'Login finance@example.test berhasil','127.0.0.1','2026-11-21 18:02:47'),(22,9,'LOGIN','auth',9,'Login afsaal@gmail.com berhasil','127.0.0.1','2026-11-21 18:05:02'),(23,9,'LOGOUT','auth',9,'Logout berhasil','127.0.0.1','2026-11-21 18:05:17'),(24,1,'LOGIN','auth',1,'Login admin@example.test berhasil','127.0.0.1','2026-11-21 18:05:46'),(25,6,'REPORT_CREATED','expense_reports',4,'Membuat laporan biaya baru: Laporan Pengeluaran Operasional November 2026',NULL,'2026-11-21 19:11:59'),(26,6,'REPORT_CREATED','expense_reports',5,'Membuat laporan biaya baru: Laporan Uji Penolakan Manajer',NULL,'2026-11-21 19:11:59'),(27,6,'REPORT_CREATED','expense_reports',6,'Membuat laporan biaya baru: Laporan Pengeluaran Operasional November 2026',NULL,'2026-11-21 19:19:27'),(28,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',8,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(29,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',9,'Menambahkan karyawan Budi Santoso (EMP-0103) ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(30,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',10,'Menambahkan karyawan Citra Amelia (EMP-0114) ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(31,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',8,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(32,6,'EXPENSE_CREATED','expenses',11,'Membuat pengeluaran baru: Biaya Server Cloud AWS Andi (Rp 1.500.000)',NULL,'2026-11-21 19:19:27'),(33,6,'REPORT_ITEM_ADDED','expense_report_items',11,'Menambahkan transaksi EXP-2026-000001 ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(34,6,'EXPENSE_CREATED','expenses',12,'Membuat pengeluaran baru: Pelatihan Recruitment & HR Budi (Rp 2.000.000)',NULL,'2026-11-21 19:19:27'),(35,6,'REPORT_ITEM_ADDED','expense_report_items',12,'Menambahkan transaksi EXP-2026-000002 ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(36,6,'EXPENSE_CREATED','expenses',13,'Membuat pengeluaran baru: Biaya Materai dan Audit Pajak Citra (Rp 1.000.000)',NULL,'2026-11-21 19:19:27'),(37,6,'REPORT_ITEM_ADDED','expense_report_items',13,'Menambahkan transaksi EXP-2026-000003 ke laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(38,6,'REPORT_SUBMITTED','expense_reports',6,'Mengajukan laporan EXP-2026-364 sebesar Rp 4.500.000 (4 karyawan, 3 item)',NULL,'2026-11-21 19:19:27'),(39,2,'REPORT_APPROVED','expense_reports',6,'Menyetujui laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(40,5,'REPORT_POSTED','expense_reports',6,'Membukukan laporan EXP-2026-364 dengan referensi jurnal JRN-EXP-202611-0003',NULL,'2026-11-21 19:19:27'),(41,5,'REIMBURSEMENT_PAYMENT_RECORDED','reimbursements',3,'Mencatat pembayaran penuh reimbursement #3 senilai Rp 4.500.000 untuk laporan EXP-2026-364',NULL,'2026-11-21 19:19:27'),(42,6,'REPORT_CREATED','expense_reports',7,'Membuat laporan biaya baru: Laporan Uji Penolakan Manajer',NULL,'2026-11-21 19:19:27'),(43,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',12,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-REFUSE-373',NULL,'2026-11-21 19:19:27'),(44,6,'EXPENSE_CREATED','expenses',14,'Membuat pengeluaran baru: Biaya Makan Siang Meeting Operasional (Rp 150.000)',NULL,'2026-11-21 19:19:27'),(45,6,'REPORT_ITEM_ADDED','expense_report_items',14,'Menambahkan transaksi EXP-2026-000004 ke laporan EXP-REFUSE-373',NULL,'2026-11-21 19:19:27'),(46,6,'REPORT_SUBMITTED','expense_reports',7,'Mengajukan laporan EXP-REFUSE-373 sebesar Rp 150.000 (2 karyawan, 1 item)',NULL,'2026-11-21 19:19:27'),(47,2,'REPORT_REFUSED','expense_reports',7,'Menolak laporan EXP-REFUSE-373 dengan alasan: Bukti struk belum jelas, mohon diperbaiki.',NULL,'2026-11-21 19:19:27'),(48,6,'REPORT_REVISED','expense_reports',7,'Mengembalikan laporan EXP-REFUSE-373 ke draft untuk revisi',NULL,'2026-11-21 19:19:27'),(49,6,'REPORT_SUBMITTED','expense_reports',7,'Mengajukan laporan EXP-REFUSE-373 sebesar Rp 150.000 (2 karyawan, 1 item)',NULL,'2026-11-21 19:19:27'),(50,6,'REPORT_CREATED','expense_reports',8,'Membuat laporan biaya baru: Laporan Pengeluaran Operasional November 2026',NULL,'2026-11-21 19:25:34'),(51,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',13,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(52,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',14,'Menambahkan karyawan Budi Santoso (EMP-0103) ke laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(53,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',15,'Menambahkan karyawan Citra Amelia (EMP-0114) ke laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(54,6,'EXPENSE_CREATED','expenses',15,'Membuat pengeluaran baru: Biaya Server Cloud AWS Andi (Rp 1.500.000)',NULL,'2026-11-21 19:25:34'),(55,6,'REPORT_ITEM_ADDED','expense_report_items',15,'Menambahkan transaksi EXP-2026-000005 ke laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(56,6,'EXPENSE_CREATED','expenses',16,'Membuat pengeluaran baru: Pelatihan Recruitment & HR Budi (Rp 2.000.000)',NULL,'2026-11-21 19:25:34'),(57,6,'REPORT_ITEM_ADDED','expense_report_items',16,'Menambahkan transaksi EXP-2026-000006 ke laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(58,6,'EXPENSE_CREATED','expenses',17,'Membuat pengeluaran baru: Biaya Materai dan Audit Pajak Citra (Rp 1.000.000)',NULL,'2026-11-21 19:25:34'),(59,6,'REPORT_ITEM_ADDED','expense_report_items',17,'Menambahkan transaksi EXP-2026-000007 ke laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(60,6,'REPORT_SUBMITTED','expense_reports',8,'Mengajukan laporan EXP-2026-686 sebesar Rp 4.500.000 (3 karyawan, 3 item)',NULL,'2026-11-21 19:25:34'),(61,2,'REPORT_APPROVED','expense_reports',8,'Menyetujui laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(62,5,'REPORT_POSTED','expense_reports',8,'Membukukan laporan EXP-2026-686 dengan referensi jurnal JRN-EXP-202611-0004',NULL,'2026-11-21 19:25:34'),(63,5,'REIMBURSEMENT_PAYMENT_RECORDED','reimbursements',4,'Mencatat pembayaran penuh reimbursement #4 senilai Rp 4.500.000 untuk laporan EXP-2026-686',NULL,'2026-11-21 19:25:34'),(64,6,'REPORT_CREATED','expense_reports',9,'Membuat laporan biaya baru: Laporan Uji Penolakan Manajer',NULL,'2026-11-21 19:25:34'),(65,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',16,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-REFUSE-998',NULL,'2026-11-21 19:25:34'),(66,6,'EXPENSE_CREATED','expenses',18,'Membuat pengeluaran baru: Biaya Makan Siang Meeting Operasional (Rp 150.000)',NULL,'2026-11-21 19:25:34'),(67,6,'REPORT_ITEM_ADDED','expense_report_items',18,'Menambahkan transaksi EXP-2026-000008 ke laporan EXP-REFUSE-998',NULL,'2026-11-21 19:25:34'),(68,6,'REPORT_SUBMITTED','expense_reports',9,'Mengajukan laporan EXP-REFUSE-998 sebesar Rp 150.000 (1 karyawan, 1 item)',NULL,'2026-11-21 19:25:34'),(69,2,'REPORT_REFUSED','expense_reports',9,'Menolak laporan EXP-REFUSE-998 dengan alasan: Bukti struk belum jelas, mohon diperbaiki.',NULL,'2026-11-21 19:25:34'),(70,6,'REPORT_REVISED','expense_reports',9,'Mengembalikan laporan EXP-REFUSE-998 ke draft untuk revisi',NULL,'2026-11-21 19:25:34'),(71,6,'REPORT_SUBMITTED','expense_reports',9,'Mengajukan laporan EXP-REFUSE-998 sebesar Rp 150.000 (1 karyawan, 1 item)',NULL,'2026-11-21 19:25:34'),(72,6,'REPORT_CREATED','expense_reports',10,'Membuat laporan biaya baru: Laporan Pengeluaran Operasional November 2026',NULL,'2026-11-21 19:27:33'),(73,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',17,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(74,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',18,'Menambahkan karyawan Budi Santoso (EMP-0103) ke laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(75,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',19,'Menambahkan karyawan Citra Amelia (EMP-0114) ke laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(76,6,'EXPENSE_CREATED','expenses',19,'Membuat pengeluaran baru: Biaya Server Cloud AWS Andi (Rp 1.500.000)',NULL,'2026-11-21 19:27:33'),(77,6,'REPORT_ITEM_ADDED','expense_report_items',19,'Menambahkan transaksi EXP-2026-000009 ke laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(78,6,'EXPENSE_CREATED','expenses',20,'Membuat pengeluaran baru: Pelatihan Recruitment & HR Budi (Rp 2.000.000)',NULL,'2026-11-21 19:27:33'),(79,6,'REPORT_ITEM_ADDED','expense_report_items',20,'Menambahkan transaksi EXP-2026-000010 ke laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(80,6,'EXPENSE_CREATED','expenses',21,'Membuat pengeluaran baru: Biaya Materai dan Audit Pajak Citra (Rp 1.000.000)',NULL,'2026-11-21 19:27:33'),(81,6,'REPORT_ITEM_ADDED','expense_report_items',21,'Menambahkan transaksi EXP-2026-000011 ke laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(82,6,'REPORT_SUBMITTED','expense_reports',10,'Mengajukan laporan EXP-2026-445 sebesar Rp 4.500.000 (3 karyawan, 3 item)',NULL,'2026-11-21 19:27:33'),(83,2,'REPORT_APPROVED','expense_reports',10,'Menyetujui laporan EXP-2026-445',NULL,'2026-11-21 19:27:33'),(84,5,'REPORT_POSTED','expense_reports',10,'Membukukan laporan EXP-2026-445 dengan referensi jurnal JRN-EXP-202611-0005',NULL,'2026-11-21 19:27:33'),(85,6,'REPORT_CREATED','expense_reports',11,'Membuat laporan biaya baru: Laporan Uji Penolakan Manajer',NULL,'2026-11-21 19:27:33'),(86,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',20,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-REFUSE-157',NULL,'2026-11-21 19:27:33'),(87,6,'EXPENSE_CREATED','expenses',22,'Membuat pengeluaran baru: Biaya Makan Siang Meeting Operasional (Rp 150.000)',NULL,'2026-11-21 19:27:33'),(88,6,'REPORT_ITEM_ADDED','expense_report_items',22,'Menambahkan transaksi EXP-2026-000012 ke laporan EXP-REFUSE-157',NULL,'2026-11-21 19:27:33'),(89,6,'REPORT_SUBMITTED','expense_reports',11,'Mengajukan laporan EXP-REFUSE-157 sebesar Rp 150.000 (1 karyawan, 1 item)',NULL,'2026-11-21 19:27:33'),(90,2,'REPORT_REFUSED','expense_reports',11,'Menolak laporan EXP-REFUSE-157 dengan alasan: Bukti struk belum jelas, mohon diperbaiki.',NULL,'2026-11-21 19:27:33'),(91,6,'REPORT_REVISED','expense_reports',11,'Mengembalikan laporan EXP-REFUSE-157 ke draft untuk revisi',NULL,'2026-11-21 19:27:33'),(92,6,'REPORT_SUBMITTED','expense_reports',11,'Mengajukan laporan EXP-REFUSE-157 sebesar Rp 150.000 (1 karyawan, 1 item)',NULL,'2026-11-21 19:27:33'),(93,6,'REPORT_CREATED','expense_reports',12,'Membuat laporan biaya baru: Laporan Pengeluaran Operasional November 2026',NULL,'2026-11-21 19:36:35'),(94,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',21,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(95,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',22,'Menambahkan karyawan Budi Santoso (EMP-0103) ke laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(96,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',23,'Menambahkan karyawan Citra Amelia (EMP-0114) ke laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(97,6,'EXPENSE_CREATED','expenses',23,'Membuat pengeluaran baru: Biaya Server Cloud AWS Andi (Rp 1.500.000)',NULL,'2026-11-21 19:36:35'),(98,6,'REPORT_ITEM_ADDED','expense_report_items',23,'Menambahkan transaksi EXP-2026-000013 ke laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(99,6,'EXPENSE_CREATED','expenses',24,'Membuat pengeluaran baru: Pelatihan Recruitment & HR Budi (Rp 2.000.000)',NULL,'2026-11-21 19:36:35'),(100,6,'REPORT_ITEM_ADDED','expense_report_items',24,'Menambahkan transaksi EXP-2026-000014 ke laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(101,6,'EXPENSE_CREATED','expenses',25,'Membuat pengeluaran baru: Biaya Materai dan Audit Pajak Citra (Rp 1.000.000)',NULL,'2026-11-21 19:36:35'),(102,6,'REPORT_ITEM_ADDED','expense_report_items',25,'Menambahkan transaksi EXP-2026-000015 ke laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(103,6,'REPORT_SUBMITTED','expense_reports',12,'Mengajukan laporan EXP-2026-406 sebesar Rp 4.500.000 (3 karyawan, 3 item)',NULL,'2026-11-21 19:36:35'),(104,2,'REPORT_APPROVED','expense_reports',12,'Menyetujui laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(105,5,'REPORT_POSTED','expense_reports',12,'Membukukan laporan EXP-2026-406 dengan referensi jurnal JRN-EXP-202611-0006',NULL,'2026-11-21 19:36:35'),(106,5,'REIMBURSEMENT_PAYMENT_RECORDED','reimbursements',6,'Mencatat pembayaran penuh reimbursement #6 senilai Rp 4.500.000 untuk laporan EXP-2026-406',NULL,'2026-11-21 19:36:35'),(107,6,'REPORT_CREATED','expense_reports',13,'Membuat laporan biaya baru: Laporan Uji Penolakan Manajer',NULL,'2026-11-21 19:36:35'),(108,6,'REPORT_EMPLOYEE_ADDED','expense_report_employees',24,'Menambahkan karyawan Andi Pratama (EMP-0115) ke laporan EXP-REFUSE-650',NULL,'2026-11-21 19:36:35'),(109,6,'EXPENSE_CREATED','expenses',26,'Membuat pengeluaran baru: Biaya Makan Siang Meeting Operasional (Rp 150.000)',NULL,'2026-11-21 19:36:35'),(110,6,'REPORT_ITEM_ADDED','expense_report_items',26,'Menambahkan transaksi EXP-2026-000016 ke laporan EXP-REFUSE-650',NULL,'2026-11-21 19:36:35'),(111,6,'REPORT_SUBMITTED','expense_reports',13,'Mengajukan laporan EXP-REFUSE-650 sebesar Rp 150.000 (1 karyawan, 1 item)',NULL,'2026-11-21 19:36:35'),(112,2,'REPORT_REFUSED','expense_reports',13,'Menolak laporan EXP-REFUSE-650 dengan alasan: Bukti struk belum jelas, mohon diperbaiki.',NULL,'2026-11-21 19:36:35'),(113,6,'REPORT_REVISED','expense_reports',13,'Mengembalikan laporan EXP-REFUSE-650 ke draft untuk revisi',NULL,'2026-11-21 19:36:35'),(114,6,'REPORT_SUBMITTED','expense_reports',13,'Mengajukan laporan EXP-REFUSE-650 sebesar Rp 150.000 (1 karyawan, 1 item)',NULL,'2026-11-21 19:36:35');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
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
INSERT INTO `categories` VALUES (1,'CAT-ELC','Perangkat Elektronik & Komputer','Laptop, monitor, mouse, keyboard, dan perangkat TI kantor','2026-10-01 08:00:00'),(2,'CAT-ATK','Alat Tulis & Kebutuhan Kantor','Kertas HVS, pulpen gel, toner cartridge, ordner dokumen','2026-10-01 08:00:00'),(3,'CAT-FUR','Perabot & Furnitur Ergonomis','Kursi ergonomis, meja kerja kantor, partisi ruangan','2026-10-01 08:00:00'),(4,'CAT-LOG','Kemasan & Perlengkapan Gudang','Kardus packing, lakban, bubble wrap, plastik wrap','2026-10-01 08:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,1,'CUST-0001','PT Nusantara Solusindo Data','CUST-0001','PT Nusantara Solusindo Data','procurement@nusantara-solusindo.co.id','021-5790123','Menara Palma Lt. 8, Jl. H.R. Rasuna Said, Jakarta Selatan','Jakarta Selatan',1,'2026-10-01 01:00:00'),(2,2,'CUST-0002','CV Bintang Niaga Sejahtera','CUST-0002','CV Bintang Niaga Sejahtera','purchasing@bintangniaga.id','021-8890234','Ruko BSD Golden Boulevard Blok W No. 5, Tangerang Selatan','Tangerang Selatan',1,'2026-10-01 01:00:00'),(3,3,'CUST-0003','PT Digital Sinergi Kreasi','CUST-0003','PT Digital Sinergi Kreasi','finance@sinergikreasi.com','022-7234567','Dago Prima Techno Park Kav. 14, Bandung','Bandung',1,'2026-10-01 01:00:00'),(4,4,'CUST-0004','Toko Mandiri Berkah Abadi','CUST-0004','Toko Mandiri Berkah Abadi','mandiriberkah@gmail.com','021-3901234','Jl. Kramat Raya No. 102, Jakarta Pusat','Jakarta Pusat',1,'2026-10-01 01:00:00');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_customers_before_insert` BEFORE INSERT ON `customers` FOR EACH ROW SET 
  NEW.customer_code = IF(NEW.customer_code IS NULL OR NEW.customer_code = '', NEW.code, NEW.customer_code),
  NEW.code = IF(NEW.code IS NULL OR NEW.code = '', NEW.customer_code, NEW.code),
  NEW.customer_name = IF(NEW.customer_name IS NULL OR NEW.customer_name = '', NEW.name, NEW.customer_name),
  NEW.name = IF(NEW.name IS NULL OR NEW.name = '', NEW.customer_name, NEW.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_customers_before_update` BEFORE UPDATE ON `customers` FOR EACH ROW SET 
  NEW.customer_code = IF(NEW.customer_code IS NULL OR NEW.customer_code = '', NEW.code, NEW.customer_code),
  NEW.code = IF(NEW.code IS NULL OR NEW.code = '', NEW.customer_code, NEW.code),
  NEW.customer_name = IF(NEW.customer_name IS NULL OR NEW.customer_name = '', NEW.name, NEW.customer_name),
  NEW.name = IF(NEW.name IS NULL OR NEW.name = '', NEW.customer_name, NEW.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `department_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'DEPT-IT','Teknologi Informasi',1,'Divisi IT, Rekayasa Perangkat Lunak, dan Infrastruktur',1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(2,'DEPT-FIN','Keuangan & Akuntansi',2,'Divisi Keuangan, Kasir, Pajak, dan Pembukuan',1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(3,'DEPT-HR','Human Resources',6,'Divisi Sumber Daya Manusia, Rekrutmen, dan Personalia',1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(4,'DEPT-PUR','Pengadaan & Purchasing',7,'Divisi Pembelian Barang, Vendor, dan Kontrak Suplai',1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(5,'DEPT-SLS','Penjualan & Pemasaran',9,'Divisi Sales Komersial, Hubungan Klien, dan Pemasaran',1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(6,'DEPT-WRH','Gudang & Logistik',11,'Divisi Pengelolaan Stok, Pergudangan, dan Distribusi',1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(7,'DEPT-OPS','Operasional Bisnis',2,'Divisi Operasional Umum dan Tata Kelola Fasilitas',1,'2026-10-15 01:00:00','2026-11-21 06:02:51');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_departments_before_insert` BEFORE INSERT ON `departments` FOR EACH ROW SET NEW.department_code = IF(NEW.department_code IS NULL OR NEW.department_code = '', CONCAT('DEPT-', UPPER(SUBSTRING(REPLACE(REPLACE(NEW.department_name, ' ', ''), '-', ''), 1, 4))), NEW.department_code) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary view structure for view `divisi`
--

DROP TABLE IF EXISTS `divisi`;
/*!50001 DROP VIEW IF EXISTS `divisi`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `divisi` AS SELECT 
 1 AS `id_divisi`,
 1 AS `divisi`*/;
SET character_set_client = @saved_cs_client;

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,1,'EMP-0101','Hendra Wijaya','admin@example.test','08123456001',1,NULL,'IT Infrastructure Manager','BCA','1234567890','2025-01-10','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',1,'Hendra Wijaya','Teknologi Informasi','IT Infrastructure Manager',16500000.00,'2025-01-10'),(2,2,'EMP-0102','Rina Manajer','manager@example.test','08123456002',2,NULL,'Finance & Accounting Manager','Mandiri','1400012345678','2025-01-15','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',2,'Rina Manajer','Keuangan & Akuntansi','Finance & Accounting Manager',18000000.00,'2025-01-15'),(3,3,'EMP-0103','Budi Santoso','budi@example.test','08123456003',1,1,'Senior Software Engineer','BCA','2345678901','2025-03-01','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',3,'Budi Santoso','Teknologi Informasi','Senior Software Engineer',12000000.00,'2025-03-01'),(4,4,'EMP-0104','Sari Putri','sari@example.test','08123456004',2,2,'Junior Financial Analyst','BNI','0234567891','2025-04-10','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',4,'Sari Putri','Keuangan & Akuntansi','Junior Financial Analyst',7500000.00,'2025-04-10'),(5,5,'EMP-0105','Dimas Finance','finance@example.test','08123456005',2,2,'Treasury & Disbursement Officer','BCA','3456789012','2025-02-01','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',5,'Dimas Finance','Keuangan & Akuntansi','Treasury & Disbursement Officer',9500000.00,'2025-02-01'),(6,6,'EMP-0106','Yudha Dwi Pratama','yudha@gmail.com','08123456006',3,NULL,'HR Director','Mandiri','1400098765432','2024-11-01','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',6,'Yudha Dwi Pratama','Human Resources','HR Director',20000000.00,'2024-11-01'),(7,7,'EMP-0107','Salma Suhaimah','salma@gmail.com','08123456007',4,NULL,'Purchasing Manager','BCA','4567890123','2025-01-05','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',7,'Salma Suhaimah','Pengadaan & Purchasing','Purchasing Manager',14000000.00,'2025-01-05'),(8,8,'EMP-0108','Arafat Hamid','arafat@gmail.com','08123456008',4,7,'Purchasing Officer','BRI','0123010045678','2025-05-15','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',8,'Arafat Hamid','Pengadaan & Purchasing','Purchasing Officer',8000000.00,'2025-05-15'),(9,9,'EMP-0109','Afsaal Fayyaz','afsaal@gmail.com','08123456009',5,NULL,'Sales Director','BCA','5678901234','2024-10-01','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',9,'Afsaal Fayyaz','Penjualan & Pemasaran','Sales Director',19500000.00,'2024-10-01'),(10,10,'EMP-0110','Zakie Kurniawan','zakie@gmail.com','08123456010',5,9,'Senior Account Executive','Mandiri','1400055443322','2025-03-20','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',10,'Zakie Kurniawan','Penjualan & Pemasaran','Senior Account Executive',8500000.00,'2025-03-20'),(11,11,'EMP-0111','Sabiq Hibatul','sabiq@gmail.com','08123456011',6,NULL,'Warehouse Supervisor','BCA','6789012345','2025-01-20','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',11,'Sabiq Hibatul','Gudang & Logistik','Warehouse Supervisor',11000000.00,'2025-01-20'),(12,12,'EMP-0112','Halim Thio','thio@gmail.com','08123456012',6,11,'Warehouse Operator','BNI','0345678912','2025-06-01','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',12,'Halim Thio','Gudang & Logistik','Warehouse Operator',6000000.00,'2025-06-01'),(13,13,'EMP-0113','Retno Wulandari','retno@gmail.com','08123456013',3,6,'HR Recruitment Specialist','BCA','7890123456','2025-02-15','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',13,'Retno Wulandari','Human Resources','HR Recruitment Specialist',8500000.00,'2025-02-15'),(14,14,'EMP-0114','Citra Amelia','citra@example.test','08123456014',2,2,'Senior Tax & Audit Specialist','Mandiri','1400088997766','2025-02-10','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',14,'Citra Amelia','Keuangan & Akuntansi','Senior Tax & Audit Specialist',10500000.00,'2025-02-10'),(15,15,'EMP-0115','Andi Pratama','andi.pratama@example.test','08123456015',1,1,'DevOps & System Administrator','BCA','8901234567','2025-03-15','ACTIVE',NULL,'2026-11-21 06:02:51','2026-11-21 06:02:51',15,'Andi Pratama','Teknologi Informasi','DevOps & System Administrator',11500000.00,'2025-03-15');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_employees_before_insert` BEFORE INSERT ON `employees` FOR EACH ROW SET 
  NEW.full_name = IF(NEW.full_name IS NULL OR NEW.full_name = '', NEW.nama, NEW.full_name),
  NEW.nama = IF(NEW.nama IS NULL OR NEW.nama = '', NEW.full_name, NEW.nama),
  NEW.position = IF(NEW.position IS NULL OR NEW.position = '', NEW.jabatan, NEW.position),
  NEW.jabatan = IF(NEW.jabatan IS NULL OR NEW.jabatan = '', NEW.position, NEW.jabatan),
  NEW.join_date = IF(NEW.join_date IS NULL, NEW.tanggal_masuk, NEW.join_date),
  NEW.tanggal_masuk = IF(NEW.tanggal_masuk IS NULL, NEW.join_date, NEW.tanggal_masuk) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_employees_before_update` BEFORE UPDATE ON `employees` FOR EACH ROW SET 
  NEW.full_name = IF(NEW.full_name IS NULL OR NEW.full_name = '', NEW.nama, NEW.full_name),
  NEW.nama = IF(NEW.nama IS NULL OR NEW.nama = '', NEW.full_name, NEW.nama),
  NEW.position = IF(NEW.position IS NULL OR NEW.position = '', NEW.jabatan, NEW.position),
  NEW.jabatan = IF(NEW.jabatan IS NULL OR NEW.jabatan = '', NEW.position, NEW.jabatan),
  NEW.join_date = IF(NEW.join_date IS NULL, NEW.tanggal_masuk, NEW.join_date),
  NEW.tanggal_masuk = IF(NEW.tanggal_masuk IS NULL, NEW.join_date, NEW.tanggal_masuk) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_approvals`
--

LOCK TABLES `expense_approvals` WRITE;
/*!40000 ALTER TABLE `expense_approvals` DISABLE KEYS */;
INSERT INTO `expense_approvals` VALUES (1,1,2,'APPROVE','Pengeluaran dinas Bandung disetujui, bukti tiket dan kuitansi hotel lengkap','2026-11-04 07:30:00'),(2,2,2,'APPROVE','Klaim ATK dan konsumsi tim disetujui','2026-11-08 08:00:00'),(3,3,2,'APPROVE','Disetujui untuk peningkatan kompetensi tim IT dan kebutuhan software pajak','2026-11-17 04:00:00'),(4,6,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:19:27'),(5,6,2,'APPROVED','Disetujui untuk seluruh 3 karyawan.','2026-11-21 12:19:27'),(6,6,5,'POSTED','Laporan telah dibukukan oleh tim Keuangan (Ref: JRN-EXP-202611-0003).','2026-11-21 12:19:27'),(7,6,5,'PAID','Pembayaran reimbursement satu laporan penuh telah diselesaikan (LUNAS). Ref: TRF-FULL-PAY-20261121','2026-11-21 12:19:27'),(8,7,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:19:27'),(9,7,2,'REFUSED','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:19:27'),(10,7,6,'DRAFT','Laporan dikembalikan ke status Draft untuk perbaikan.','2026-11-21 12:19:27'),(11,7,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:19:27'),(12,8,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:25:34'),(13,8,2,'APPROVED','Disetujui untuk seluruh 3 karyawan.','2026-11-21 12:25:34'),(14,8,5,'POSTED','Laporan telah dibukukan oleh tim Keuangan (Ref: JRN-EXP-202611-0004).','2026-11-21 12:25:34'),(15,8,5,'PAID','Pembayaran reimbursement satu laporan penuh telah diselesaikan (LUNAS). Ref: TRF-FULL-PAY-20261121','2026-11-21 12:25:34'),(16,9,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:25:34'),(17,9,2,'REFUSED','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:25:34'),(18,9,6,'DRAFT','Laporan dikembalikan ke status Draft untuk perbaikan.','2026-11-21 12:25:34'),(19,9,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:25:34'),(20,10,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:27:33'),(21,10,2,'APPROVED','Disetujui untuk seluruh 3 karyawan.','2026-11-21 12:27:33'),(22,10,5,'POSTED','Laporan telah dibukukan oleh tim Keuangan (Ref: JRN-EXP-202611-0005).','2026-11-21 12:27:33'),(23,11,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:27:33'),(24,11,2,'REFUSED','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:27:33'),(25,11,6,'DRAFT','Laporan dikembalikan ke status Draft untuk perbaikan.','2026-11-21 12:27:33'),(26,11,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:27:33'),(27,12,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:36:35'),(28,12,2,'APPROVED','Disetujui untuk seluruh 3 karyawan.','2026-11-21 12:36:35'),(29,12,5,'POSTED','Laporan telah dibukukan oleh tim Keuangan (Ref: JRN-EXP-202611-0006).','2026-11-21 12:36:35'),(30,12,5,'PAID','Pembayaran reimbursement satu laporan penuh telah diselesaikan (LUNAS). Ref: TRF-FULL-PAY-20261121','2026-11-21 12:36:35'),(31,13,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:36:35'),(32,13,2,'REFUSED','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:36:35'),(33,13,6,'DRAFT','Laporan dikembalikan ke status Draft untuk perbaikan.','2026-11-21 12:36:35'),(34,13,6,'SUBMITTED','Laporan diajukan untuk persetujuan manajer.','2026-11-21 12:36:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'EXP-CAT-TRN','Transportasi & Perjalanan Dinas','Tiket pesawat, kereta api eksekutif, taksi bandara, dan BBM dinas',250000.00,0,1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(2,'EXP-CAT-AKM','Akomodasi & Penginapan','Biaya sewa kamar hotel dinas luar kota',850000.00,0,1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(3,'EXP-CAT-ML','Konsumsi & Jamuan Klien','Jamuan makan siang meeting klien, konsumsi rapat tim internal',150000.00,0,1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(4,'EXP-CAT-ATK','Perlengkapan & Keperluan Kantor','Pembelian ATK darurat, materai resmi, dan operasional kerja',100000.00,0,1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(5,'EXP-CAT-KOM','Komunikasi & Internet','Paket data darurat kunjungan lapangan dan pulsa koordinasi',150000.00,0,1,'2026-10-15 01:00:00','2026-11-21 06:02:51'),(6,'EXP-CAT-TRN-DEV','Pelatihan & Sertifikasi IT','Biaya keikutsertaan workshop teknis dan sertifikasi staf',1500000.00,0,1,'2026-10-15 01:00:00','2026-11-21 06:02:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_comments`
--

LOCK TABLES `expense_comments` WRITE;
/*!40000 ALTER TABLE `expense_comments` DISABLE KEYS */;
INSERT INTO `expense_comments` VALUES (1,1,3,'Mohon ditinjau laporannya Bu Rina, semua struk tiket dan hotel sudah saya lampirkan.',0,'2026-11-04 03:05:00'),(2,1,2,'Baik Mas Budi, rincian sudah sesuai kebijakan plafon dinas luar kota. Sudah saya setujui.',0,'2026-11-04 07:31:00'),(3,1,5,'Dana klaim sebesar Rp 1.950.000 telah ditransfer ke rekening BCA Mas Budi.',0,'2026-11-05 08:31:00'),(4,7,2,'Ditolak: Bukti struk belum jelas, mohon diperbaiki.',0,'2026-11-21 12:19:27'),(5,9,2,'Ditolak: Bukti struk belum jelas, mohon diperbaiki.',0,'2026-11-21 12:25:34'),(6,11,2,'Ditolak: Bukti struk belum jelas, mohon diperbaiki.',0,'2026-11-21 12:27:33'),(7,13,2,'Ditolak: Bukti struk belum jelas, mohon diperbaiki.',0,'2026-11-21 12:36:35');
/*!40000 ALTER TABLE `expense_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_postings`
--

DROP TABLE IF EXISTS `expense_postings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_postings` (
  `posting_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `journal_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `posting_date` date NOT NULL,
  `posted_by` bigint unsigned NOT NULL,
  `posted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`posting_id`),
  UNIQUE KEY `uq_expense_postings_report` (`report_id`),
  KEY `idx_expense_postings_date` (`posting_date`),
  KEY `idx_expense_postings_posted_by` (`posted_by`),
  CONSTRAINT `fk_expense_postings_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_postings_user` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_postings`
--

LOCK TABLES `expense_postings` WRITE;
/*!40000 ALTER TABLE `expense_postings` DISABLE KEYS */;
INSERT INTO `expense_postings` VALUES (1,1,'JRN-EXP-202611-0001','2026-11-05',5,'2026-11-05 02:00:00','Pembukuan beban perjalanan dinas Bandung EXP-202611-0001'),(2,2,'JRN-EXP-202611-0002','2026-11-09',5,'2026-11-09 03:00:00','Pembukuan beban operasional ATK kantor EXP-202611-0002'),(3,6,'JRN-EXP-202611-0003','2026-11-21',5,'2026-11-21 12:19:27',NULL),(4,8,'JRN-EXP-202611-0004','2026-11-21',5,'2026-11-21 12:25:34',NULL),(5,10,'JRN-EXP-202611-0005','2026-11-21',5,'2026-11-21 12:27:33',NULL),(6,12,'JRN-EXP-202611-0006','2026-11-21',5,'2026-11-21 12:36:35',NULL);
/*!40000 ALTER TABLE `expense_postings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_reimbursement_payments`
--

DROP TABLE IF EXISTS `expense_reimbursement_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_reimbursement_payments` (
  `payment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reimbursement_id` bigint unsigned NOT NULL,
  `payment_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BANK_TRANSFER',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_reference` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_by` bigint unsigned NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uq_reimb_payment_number` (`payment_number`),
  KEY `idx_reimb_payments_reimb` (`reimbursement_id`),
  KEY `idx_reimb_payments_paid_by` (`paid_by`),
  KEY `idx_reimb_payments_date` (`payment_date`),
  CONSTRAINT `fk_reimb_payments_reimb` FOREIGN KEY (`reimbursement_id`) REFERENCES `expense_reimbursements` (`reimbursement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reimb_payments_user` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_reimbursement_payments`
--

LOCK TABLES `expense_reimbursement_payments` WRITE;
/*!40000 ALTER TABLE `expense_reimbursement_payments` DISABLE KEYS */;
INSERT INTO `expense_reimbursement_payments` VALUES (1,1,'PAY-202611-0001','2026-11-05',1950000.00,'BANK_TRANSFER','BCA','TRX-BCA-9812401',NULL,5,'Transfer pencairan klaim dinas Budi Santoso ke rekening BCA','2026-11-05 08:30:00'),(2,2,'PAY-202611-0002','2026-11-09',400000.00,'BANK_TRANSFER','BNI','TRX-BNI-7712390',NULL,5,'Transfer pencairan klaim ATK Sari Putri ke rekening BNI','2026-11-09 09:00:00'),(3,3,'PAY-202611-0003','2026-11-21',4500000.00,'BANK_TRANSFER','BCA','TRF-FULL-PAY-20261121',NULL,5,'Pencairan penuh seluruh pengeluaran operasional November 2026','2026-11-21 12:19:27'),(4,4,'PAY-202611-0004','2026-11-21',4500000.00,'BANK_TRANSFER','BCA','TRF-FULL-PAY-20261121',NULL,5,'Pencairan penuh seluruh pengeluaran operasional November 2026','2026-11-21 12:25:34');
/*!40000 ALTER TABLE `expense_reimbursement_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_reimbursements`
--

DROP TABLE IF EXISTS `expense_reimbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_reimbursements` (
  `reimbursement_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `report_employee_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `approved_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('PENDING','PROCESSING','PARTIALLY_PAID','PAID','FAILED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reimbursement_id`),
  UNIQUE KEY `uq_report_employee_reimb` (`report_id`,`employee_id`),
  KEY `idx_expense_reimb_report` (`report_id`),
  KEY `idx_expense_reimb_report_emp` (`report_employee_id`),
  KEY `idx_expense_reimb_employee` (`employee_id`),
  KEY `idx_expense_reimb_status` (`status`),
  CONSTRAINT `fk_expense_reimb_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_reimb_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_reimb_report_employee` FOREIGN KEY (`report_employee_id`) REFERENCES `expense_report_employees` (`report_employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_reimbursements`
--

LOCK TABLES `expense_reimbursements` WRITE;
/*!40000 ALTER TABLE `expense_reimbursements` DISABLE KEYS */;
INSERT INTO `expense_reimbursements` VALUES (1,1,1,3,1950000.00,1950000.00,'PAID','Kewajiban klaim Budi Santoso perjalanan dinas Bandung','2026-11-05 02:00:00','2026-11-21 06:02:51'),(2,2,2,4,400000.00,400000.00,'PAID','Kewajiban klaim Sari Putri operasional kantor','2026-11-09 03:00:00','2026-11-21 06:02:51'),(3,3,3,15,4500000.00,0.00,'PENDING','Antrean reimbursement gabungan laporan EXP-2026-364','2026-11-17 04:00:00','2026-11-21 12:19:27'),(4,3,4,14,4500000.00,0.00,'PENDING','Antrean reimbursement gabungan laporan EXP-2026-686','2026-11-17 04:00:00','2026-11-21 12:25:34');
/*!40000 ALTER TABLE `expense_reimbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_report_employees`
--

DROP TABLE IF EXISTS `expense_report_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_report_employees` (
  `report_employee_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `employee_number_snapshot` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_name_snapshot` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_employee_id`),
  UNIQUE KEY `uq_report_employee` (`report_id`,`employee_id`),
  KEY `idx_rep_emp_report` (`report_id`),
  KEY `idx_rep_emp_employee` (`employee_id`),
  CONSTRAINT `fk_rep_emp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rep_emp_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_report_employees`
--

LOCK TABLES `expense_report_employees` WRITE;
/*!40000 ALTER TABLE `expense_report_employees` DISABLE KEYS */;
INSERT INTO `expense_report_employees` VALUES (1,1,3,'EMP-0103','Budi Santoso','Teknologi Informasi','2026-11-04 02:00:00'),(2,2,4,'EMP-0104','Sari Putri','Keuangan & Akuntansi','2026-11-08 03:00:00'),(3,3,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-16 04:00:00'),(4,3,14,'EMP-0114','Citra Amelia','Keuangan & Akuntansi','2026-11-16 04:00:00'),(5,4,6,'EMP-0106','Yudha Dwi Pratama','Human Resources','2026-11-21 12:11:59'),(6,5,6,'EMP-0106','Yudha Dwi Pratama','Human Resources','2026-11-21 12:11:59'),(7,6,6,'EMP-0106','Yudha Dwi Pratama','Human Resources','2026-11-21 12:19:27'),(8,6,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:19:27'),(9,6,3,'EMP-0103','Budi Santoso','Teknologi Informasi','2026-11-21 12:19:27'),(10,6,14,'EMP-0114','Citra Amelia','Keuangan & Akuntansi','2026-11-21 12:19:27'),(11,7,6,'EMP-0106','Yudha Dwi Pratama','Human Resources','2026-11-21 12:19:27'),(12,7,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:19:27'),(13,8,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:25:34'),(14,8,3,'EMP-0103','Budi Santoso','Teknologi Informasi','2026-11-21 12:25:34'),(15,8,14,'EMP-0114','Citra Amelia','Keuangan & Akuntansi','2026-11-21 12:25:34'),(16,9,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:25:34'),(17,10,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:27:33'),(18,10,3,'EMP-0103','Budi Santoso','Teknologi Informasi','2026-11-21 12:27:33'),(19,10,14,'EMP-0114','Citra Amelia','Keuangan & Akuntansi','2026-11-21 12:27:33'),(20,11,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:27:33'),(21,12,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:36:35'),(22,12,3,'EMP-0103','Budi Santoso','Teknologi Informasi','2026-11-21 12:36:35'),(23,12,14,'EMP-0114','Citra Amelia','Keuangan & Akuntansi','2026-11-21 12:36:35'),(24,13,15,'EMP-0115','Andi Pratama','Teknologi Informasi','2026-11-21 12:36:35');
/*!40000 ALTER TABLE `expense_report_employees` ENABLE KEYS */;
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
  `report_employee_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`report_item_id`),
  UNIQUE KEY `uq_report_expense` (`report_id`,`expense_id`),
  UNIQUE KEY `uq_expense_report_items_expense` (`expense_id`),
  KEY `fk_report_item_report_employee` (`report_employee_id`),
  CONSTRAINT `fk_report_item_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_report_item_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_report_item_report_employee` FOREIGN KEY (`report_employee_id`) REFERENCES `expense_report_employees` (`report_employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_report_items`
--

LOCK TABLES `expense_report_items` WRITE;
/*!40000 ALTER TABLE `expense_report_items` DISABLE KEYS */;
INSERT INTO `expense_report_items` VALUES (1,1,1,1),(2,1,2,1),(3,1,3,1),(4,1,4,1),(5,2,5,2),(6,2,6,2),(7,3,7,3),(8,3,8,3),(9,3,9,4),(10,6,11,8),(11,6,12,9),(12,6,13,10),(13,7,14,12),(14,8,15,13),(15,8,16,14),(16,8,17,15),(17,9,18,16),(18,10,19,17),(19,10,20,18),(20,10,21,19),(21,11,22,20),(22,12,23,21),(23,12,24,22),(24,12,25,23),(25,13,26,24);
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
  `employee_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
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
  KEY `fk_expense_reports_created_by` (`created_by`),
  CONSTRAINT `fk_expense_report_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_reports_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_reports`
--

LOCK TABLES `expense_reports` WRITE;
/*!40000 ALTER TABLE `expense_reports` DISABLE KEYS */;
INSERT INTO `expense_reports` VALUES (1,'EXP-202611-0001',3,'Laporan Biaya Perjalanan Dinas Implementasi Klien Bandung','Perjalanan dinas meeting teknis dan deploy sistem di PT Digital Sinergi Bandung',3,'2026-11-02','2026-11-04',1950000.00,'PAID','2026-11-04 10:00:00','2026-11-04 14:30:00','2026-11-05 09:00:00','2026-11-05 15:30:00',NULL,NULL,'2026-11-04 02:00:00','2026-11-21 06:02:51'),(2,'EXP-202611-0002',4,'Laporan Operasional Tim Keuangan & Materai Pajak','Klaim operasional pembelian materai resmi dan konsumsi rapat tim keuangan',4,'2026-11-06','2026-11-08',400000.00,'PAID','2026-11-08 11:00:00','2026-11-08 15:00:00','2026-11-09 10:00:00','2026-11-09 16:00:00',NULL,NULL,'2026-11-08 03:00:00','2026-11-21 06:02:51'),(3,'EXP-202611-0003',NULL,'Laporan Pengeluaran Gabungan Operasional Q4 (Multi-Karyawan)','Pengajuan pengeluaran gabungan sertifikasi IT Andi Pratama dan software pajak Citra Amelia',1,'2026-11-10','2026-11-16',3130000.00,'APPROVED','2026-11-16 14:00:00','2026-11-17 11:00:00',NULL,NULL,NULL,NULL,'2026-11-16 04:00:00','2026-11-21 06:02:51'),(4,'EXP-2026-804',6,'Laporan Pengeluaran Operasional November 2026','Laporan gabungan karyawan operasional IT, HR, dan Finance',6,'2026-11-01','2026-11-15',0.00,'DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-21 12:11:59','2026-11-21 12:11:59'),(5,'EXP-REFUSE-267',6,'Laporan Uji Penolakan Manajer',NULL,6,'2026-11-01','2026-11-10',0.00,'DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-21 12:11:59','2026-11-21 12:11:59'),(6,'EXP-2026-364',6,'Laporan Pengeluaran Operasional November 2026','Laporan gabungan karyawan operasional IT, HR, dan Finance',6,'2026-11-01','2026-11-15',4500000.00,'PAID','2026-11-21 19:19:27','2026-11-21 19:19:27','2026-11-21 19:19:27','2026-11-21 19:19:27',NULL,NULL,'2026-11-21 12:19:27','2026-11-21 12:19:27'),(7,'EXP-REFUSE-373',6,'Laporan Uji Penolakan Manajer',NULL,6,'2026-11-01','2026-11-10',150000.00,'SUBMITTED','2026-11-21 19:19:27',NULL,NULL,NULL,'2026-11-21 19:19:27','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:19:27','2026-11-21 12:19:27'),(8,'EXP-2026-686',NULL,'Laporan Pengeluaran Operasional November 2026','Laporan gabungan karyawan operasional IT, HR, dan Finance',6,'2026-11-01','2026-11-15',4500000.00,'PAID','2026-11-21 19:25:34','2026-11-21 19:25:34','2026-11-21 19:25:34','2026-11-21 19:25:34',NULL,NULL,'2026-11-21 12:25:34','2026-11-21 12:25:34'),(9,'EXP-REFUSE-998',NULL,'Laporan Uji Penolakan Manajer',NULL,6,'2026-11-01','2026-11-10',150000.00,'SUBMITTED','2026-11-21 19:25:34',NULL,NULL,NULL,'2026-11-21 19:25:34','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:25:34','2026-11-21 12:25:34'),(10,'EXP-2026-445',NULL,'Laporan Pengeluaran Operasional November 2026','Laporan gabungan karyawan operasional IT, HR, dan Finance',6,'2026-11-01','2026-11-15',4500000.00,'POSTED','2026-11-21 19:27:33','2026-11-21 19:27:33','2026-11-21 19:27:33',NULL,NULL,NULL,'2026-11-21 12:27:33','2026-11-21 12:27:33'),(11,'EXP-REFUSE-157',NULL,'Laporan Uji Penolakan Manajer',NULL,6,'2026-11-01','2026-11-10',150000.00,'SUBMITTED','2026-11-21 19:27:33',NULL,NULL,NULL,'2026-11-21 19:27:33','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:27:33','2026-11-21 12:27:33'),(12,'EXP-2026-406',NULL,'Laporan Pengeluaran Operasional November 2026','Laporan gabungan karyawan operasional IT, HR, dan Finance',6,'2026-11-01','2026-11-15',4500000.00,'PAID','2026-11-21 19:36:35','2026-11-21 19:36:35','2026-11-21 19:36:35','2026-11-21 19:36:35',NULL,NULL,'2026-11-21 12:36:35','2026-11-21 12:36:35'),(13,'EXP-REFUSE-650',NULL,'Laporan Uji Penolakan Manajer',NULL,6,'2026-11-01','2026-11-10',150000.00,'SUBMITTED','2026-11-21 19:36:35',NULL,NULL,NULL,'2026-11-21 19:36:35','Bukti struk belum jelas, mohon diperbaiki.','2026-11-21 12:36:35','2026-11-21 12:36:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'EXP-202611-0001',3,1,'2026-11-02','Tiket Kereta Argo Parahyangan Gambir - Bandung PP',350000.00,'IDR','BANK_TRANSFER','BCA','PT Kereta Api Indonesia',1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(2,'EXP-202611-0002',3,2,'2026-11-02','Hotel Ibis Pasteur Bandung 2 Malam Kunjungan Klien',1100000.00,'IDR','BANK_TRANSFER','BCA','Ibis Hotel Bandung',1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(3,'EXP-202611-0003',3,3,'2026-11-03','Makan Siang & Diskusi Teknis Tim PIC Digital Sinergi',380000.00,'IDR','CASH','BCA','Restoran Pagi Sore Pasteur',1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(4,'EXP-202611-0004',3,1,'2026-11-03','Grab Korporat PP Stasiun - Kantor Klien Dago',120000.00,'IDR','CASH','BCA','Grab Indonesia',1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(5,'EXP-202611-0005',4,4,'2026-11-06','Pembelian Materai Elektronik & Fisik 10.000 untuk Kontrak',160000.00,'IDR','CASH','BNI','Kantor Pos Sudirman',1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(6,'EXP-202611-0006',4,3,'2026-11-07','Konsumsi Rapat Koordinasi Rekonsiliasi Pajak Q3',240000.00,'IDR','BANK_TRANSFER','BNI','Kopi Kenangan Tower',1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(7,'EXP-202611-0007',15,6,'2026-11-10','Biaya Sertifikasi Kubernetes Administrator (CKA)',2100000.00,'IDR','BANK_TRANSFER','BCA','The Linux Foundation',1,NULL,NULL,1,0,'NOT_INVOICED','APPROVED',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(8,'EXP-202611-0008',15,1,'2026-11-11','Taksi Online Onsite Perbaikan Server Datacenter Cyber',180000.00,'IDR','CASH','BCA','Bluebird Taksi',1,NULL,NULL,1,0,'NOT_INVOICED','APPROVED',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(9,'EXP-202611-0009',14,4,'2026-11-15','Langganan Software Tax & E-Faktur Konsultan',850000.00,'IDR','BANK_TRANSFER','Mandiri','Ortax Media Solusindo',1,NULL,NULL,1,0,'NOT_INVOICED','APPROVED',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(10,'EXP-202611-0010',3,5,'2026-11-18','Paket Data Darurat Remote Support Deployment',150000.00,'IDR','CASH','BCA','Telkomsel MyEnterprise',1,NULL,NULL,1,0,'NOT_INVOICED','DRAFT',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:00:00','2026-11-21 06:02:51'),(11,'EXP-2026-000001',15,1,'2026-11-05','Biaya Server Cloud AWS Andi',1500000.00,'IDR','BANK_TRANSFER','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:19:27',NULL,NULL,NULL,5,'2026-11-21 19:19:27','2026-11-21 12:19:27','2026-11-21 12:19:27'),(12,'EXP-2026-000002',3,1,'2026-11-07','Pelatihan Recruitment & HR Budi',2000000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:19:27',NULL,NULL,NULL,5,'2026-11-21 19:19:27','2026-11-21 12:19:27','2026-11-21 12:19:27'),(13,'EXP-2026-000003',14,1,'2026-11-10','Biaya Materai dan Audit Pajak Citra',1000000.00,'IDR','BANK_TRANSFER','BCA',NULL,2,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:19:27',NULL,NULL,NULL,5,'2026-11-21 19:19:27','2026-11-21 12:19:27','2026-11-21 12:19:27'),(14,'EXP-2026-000004',15,1,'2026-11-02','Biaya Makan Siang Meeting Operasional',150000.00,'IDR','CASH','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','SUBMITTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,NULL,NULL,2,'2026-11-21 19:19:27','Bukti struk belum jelas, mohon diperbaiki.',NULL,NULL,'2026-11-21 12:19:27','2026-11-21 12:19:27'),(15,'EXP-2026-000005',15,1,'2026-11-05','Biaya Server Cloud AWS Andi',1500000.00,'IDR','BANK_TRANSFER','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:25:34',NULL,NULL,NULL,5,'2026-11-21 19:25:34','2026-11-21 12:25:34','2026-11-21 12:25:34'),(16,'EXP-2026-000006',3,1,'2026-11-07','Pelatihan Recruitment & HR Budi',2000000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:25:34',NULL,NULL,NULL,5,'2026-11-21 19:25:34','2026-11-21 12:25:34','2026-11-21 12:25:34'),(17,'EXP-2026-000007',14,1,'2026-11-10','Biaya Materai dan Audit Pajak Citra',1000000.00,'IDR','BANK_TRANSFER','BCA',NULL,2,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:25:34',NULL,NULL,NULL,5,'2026-11-21 19:25:34','2026-11-21 12:25:34','2026-11-21 12:25:34'),(18,'EXP-2026-000008',15,1,'2026-11-02','Biaya Makan Siang Meeting Operasional',150000.00,'IDR','CASH','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','SUBMITTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,NULL,NULL,2,'2026-11-21 19:25:34','Bukti struk belum jelas, mohon diperbaiki.',NULL,NULL,'2026-11-21 12:25:34','2026-11-21 12:25:34'),(19,'EXP-2026-000009',15,1,'2026-11-05','Biaya Server Cloud AWS Andi',1500000.00,'IDR','BANK_TRANSFER','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','POSTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:27:33',NULL,NULL,NULL,NULL,NULL,'2026-11-21 12:27:33','2026-11-21 12:27:33'),(20,'EXP-2026-000010',3,1,'2026-11-07','Pelatihan Recruitment & HR Budi',2000000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','POSTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:27:33',NULL,NULL,NULL,NULL,NULL,'2026-11-21 12:27:33','2026-11-21 12:27:33'),(21,'EXP-2026-000011',14,1,'2026-11-10','Biaya Materai dan Audit Pajak Citra',1000000.00,'IDR','BANK_TRANSFER','BCA',NULL,2,NULL,NULL,1,0,'NOT_INVOICED','POSTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:27:33',NULL,NULL,NULL,NULL,NULL,'2026-11-21 12:27:33','2026-11-21 12:27:33'),(22,'EXP-2026-000012',15,1,'2026-11-02','Biaya Makan Siang Meeting Operasional',150000.00,'IDR','CASH','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','SUBMITTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,NULL,NULL,2,'2026-11-21 19:27:33','Bukti struk belum jelas, mohon diperbaiki.',NULL,NULL,'2026-11-21 12:27:33','2026-11-21 12:27:33'),(23,'EXP-2026-000013',15,1,'2026-11-05','Biaya Server Cloud AWS Andi',1500000.00,'IDR','BANK_TRANSFER','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:36:35',NULL,NULL,NULL,5,'2026-11-21 19:36:35','2026-11-21 12:36:35','2026-11-21 12:36:35'),(24,'EXP-2026-000014',3,1,'2026-11-07','Pelatihan Recruitment & HR Budi',2000000.00,'IDR','BANK_TRANSFER','Mandiri',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:36:35',NULL,NULL,NULL,5,'2026-11-21 19:36:35','2026-11-21 12:36:35','2026-11-21 12:36:35'),(25,'EXP-2026-000015',14,1,'2026-11-10','Biaya Materai dan Audit Pajak Citra',1000000.00,'IDR','BANK_TRANSFER','BCA',NULL,2,NULL,NULL,1,0,'NOT_INVOICED','PAID',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,2,'2026-11-21 19:36:35',NULL,NULL,NULL,5,'2026-11-21 19:36:35','2026-11-21 12:36:35','2026-11-21 12:36:35'),(26,'EXP-2026-000016',15,1,'2026-11-02','Biaya Makan Siang Meeting Operasional',150000.00,'IDR','CASH','BCA',NULL,1,NULL,NULL,1,0,'NOT_INVOICED','SUBMITTED',NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,NULL,NULL,2,'2026-11-21 19:36:35','Bukti struk belum jelas, mohon diperbaiki.',NULL,NULL,'2026-11-21 12:36:35','2026-11-21 12:36:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_details`
--

LOCK TABLES `goods_receipt_details` WRITE;
/*!40000 ALTER TABLE `goods_receipt_details` DISABLE KEYS */;
INSERT INTO `goods_receipt_details` VALUES (1,1,1,1,2.00,'Good'),(2,1,2,2,5.00,'Good');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
INSERT INTO `goods_receipts` VALUES (1,'GRN-202611-0001',1,11,'2026-11-12','Barang tiba lengkap, segel utuh, fungsi unit normal','2026-11-12 14:30:00',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-202611-0001',1,'2026-11-10','2026-11-24',30969000.00,'paid','2026-11-10 03:00:00'),(2,'INV-202611-0002',2,'2026-11-14','2026-11-28',6438000.00,'unpaid','2026-11-14 06:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'2026-11-15',30969000.00,'transfer','Pelunasan faktur INV-202611-0001 PT Nusantara Solusindo Data (Ref: BCA-990812)','2026-11-15 04:30:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrolls`
--

LOCK TABLES `payrolls` WRITE;
/*!40000 ALTER TABLE `payrolls` DISABLE KEYS */;
INSERT INTO `payrolls` VALUES (1,1,'2026-10-01',16500000.00,'2026-10-28'),(2,2,'2026-10-01',18000000.00,'2026-10-28'),(3,3,'2026-10-01',12000000.00,'2026-10-28'),(4,4,'2026-10-01',7500000.00,'2026-10-28'),(5,5,'2026-10-01',9500000.00,'2026-10-28'),(6,6,'2026-10-01',20000000.00,'2026-10-28'),(7,7,'2026-10-01',14000000.00,'2026-10-28'),(8,8,'2026-10-01',8000000.00,'2026-10-28'),(9,9,'2026-10-01',19500000.00,'2026-10-28'),(10,10,'2026-10-01',8500000.00,'2026-10-28'),(11,11,'2026-10-01',11000000.00,'2026-10-28'),(12,12,'2026-10-01',6000000.00,'2026-10-28'),(13,13,'2026-10-01',8500000.00,'2026-10-28'),(14,14,'2026-10-01',10500000.00,'2026-10-28'),(15,15,'2026-10-01',11500000.00,'2026-10-28'),(16,1,'2026-11-01',16500000.00,'2026-11-25'),(17,2,'2026-11-01',18000000.00,'2026-11-25'),(18,3,'2026-11-01',12000000.00,'2026-11-25'),(19,4,'2026-11-01',7500000.00,'2026-11-25'),(20,5,'2026-11-01',9500000.00,'2026-11-25'),(21,6,'2026-11-01',20000000.00,'2026-11-25'),(22,7,'2026-11-01',14000000.00,'2026-11-25'),(23,8,'2026-11-01',8000000.00,'2026-11-25'),(24,9,'2026-11-01',19500000.00,'2026-11-25'),(25,10,'2026-11-01',8500000.00,'2026-11-25'),(26,11,'2026-11-01',11000000.00,'2026-11-25'),(27,12,'2026-11-01',6000000.00,'2026-11-25'),(28,13,'2026-11-01',8500000.00,'2026-11-25'),(29,14,'2026-11-01',10500000.00,'2026-11-25'),(30,15,'2026-11-01',11500000.00,'2026-11-25');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'PRD-ELC-001','Laptop Asus ExpertBook B1400','Laptop bisnis Intel i5 16GB RAM 512GB SSD','unit',11500000.00,9200000.00,15.000,5.000,'Rak IT-A1',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(2,1,'PRD-ELC-002','Monitor Dell 24 Inch IPS FHD','Monitor kantor bezel-less 1080p 75Hz Eye Care','unit',2450000.00,1900000.00,20.000,5.000,'Rak IT-A2',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(3,1,'PRD-ELC-003','Mouse Wireless Logitech M185','Mouse optik nirkabel hemat daya baterai AA','pcs',185000.00,125000.00,50.000,10.000,'Rak IT-A3',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(4,1,'PRD-ELC-004','Keyboard Mechanical Logitech K835','Keyboard mekanik switch tactile nyaman untuk mengetik','unit',790000.00,570000.00,25.000,8.000,'Rak IT-A4',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(5,2,'PRD-ATK-001','Kertas HVS PaperOne A4 80gsm','Kertas HVS putih bersih ukuran A4 isi 500 lembar (rim)','rim',62000.00,48000.00,150.000,30.000,'Rak ATK-B1',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(6,2,'PRD-ATK-002','Kertas HVS PaperOne F4 75gsm','Kertas HVS folio putih ukuran F4 isi 500 lembar (rim)','rim',66000.00,52000.00,100.000,20.000,'Rak ATK-B2',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(7,2,'PRD-ATK-003','Toner Laserjet HP 85A Black','Toner cartridge hitam pekat untuk printer HP Laserjet','unit',650000.00,480000.00,18.000,5.000,'Rak ATK-B3',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(8,2,'PRD-ATK-004','Pulpen Gel Pilot G2 0.5 Black','Pulpen tinta gel hitam pekat anti macet 1 lusin (box)','box',185000.00,140000.00,40.000,10.000,'Rak ATK-B4',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(9,2,'PRD-ATK-005','Ordner Bantex Folio 7cm Blue','Map ordner file tebal kokoh ukuran folio warna biru','pcs',42000.00,30000.00,75.000,15.000,'Rak ATK-B5',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(10,3,'PRD-FUR-001','Kursi Kerja Ergonomis Mesh Jaring','Kursi putar jaring lumbar support hidrolik','unit',1450000.00,1050000.00,12.000,4.000,'Gudang F-01',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(11,3,'PRD-FUR-002','Meja Kantor Minimalis 120x60 cm','Meja kerja rangka baja kokoh kayu MDF partisi','unit',1750000.00,1250000.00,8.000,3.000,'Gudang F-02',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(12,4,'PRD-LOG-001','Kardus Packing Double Wall 40x30x20','Kotak karton gelombang tebal untuk ekspedisi paket','pcs',18000.00,12000.00,300.000,50.000,'Gudang Log-01',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(13,4,'PRD-LOG-002','Lakban Bening Daimaru 2 Inch x 90m','Lakban perekat kuat serbaguna untuk packing karton','roll',22000.00,15000.00,180.000,40.000,'Gudang Log-02',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(14,4,'PRD-LOG-003','Bubble Wrap Roll 50m Hitam','Plastik gelembung pelindung paket barang pecah belah','roll',165000.00,115000.00,20.000,5.000,'Gudang Log-03',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL),(15,1,'PRD-ELC-005','Flashdisk SanDisk Ultra 64GB USB 3.0','Media penyimpanan data portabel kecepatan transfer tinggi','unit',120000.00,80000.00,45.000,10.000,'Rak IT-A5',1,'2026-10-10 08:00:00','2026-11-21 13:02:51',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'PRJ-2026-001','Implementasi Sistem ERP Korporat',1,'Proyek kustomisasi dan implementasi sistem ERP & HRIS multi-divisi','ACTIVE',75000000.00,'2026-10-01','2026-12-31','2026-10-01 01:00:00','2026-11-21 06:02:51'),(2,'PRJ-2026-002','Pengembangan Portal E-Procurement',3,'Pembangunan modul procurement dan katalog barang digital','ACTIVE',45000000.00,'2026-10-15','2027-01-31','2026-10-15 01:00:00','2026-11-21 06:02:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisition_details`
--

LOCK TABLES `purchase_requisition_details` WRITE;
/*!40000 ALTER TABLE `purchase_requisition_details` DISABLE KEYS */;
INSERT INTO `purchase_requisition_details` VALUES (1,1,1,2.00,9200000.00,18400000.00),(2,1,2,5.00,1900000.00,9500000.00),(3,2,5,50.00,48000.00,2400000.00),(4,2,6,25.00,52000.00,1300000.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisitions`
--

LOCK TABLES `purchase_requisitions` WRITE;
/*!40000 ALTER TABLE `purchase_requisitions` DISABLE KEYS */;
INSERT INTO `purchase_requisitions` VALUES (1,'PR-202611-0001',7,'Pengadaan & Purchasing','2026-11-15',23000000.00,2530000.00,25530000.00,'CONVERTED','Pengadaan 2 unit Laptop Asus & 5 unit Monitor Dell untuk tim baru',5000000.00,7,'2026-11-05 10:00:00',NULL,'2026-11-05 09:15:00','2026-11-21 13:02:51',NULL),(2,'PR-202611-0002',8,'Pengadaan & Purchasing','2026-11-20',3840000.00,422400.00,4262400.00,'APPROVED','Restock rutin kertas HVS A4 & F4 bulanan kantor',5000000.00,7,'2026-11-08 14:00:00',NULL,'2026-11-08 11:30:00','2026-11-21 13:02:51',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_transaction_details`
--

LOCK TABLES `purchase_transaction_details` WRITE;
/*!40000 ALTER TABLE `purchase_transaction_details` DISABLE KEYS */;
INSERT INTO `purchase_transaction_details` VALUES (1,1,1,2.00,9200000.00,18400000.00),(2,1,2,5.00,1900000.00,9500000.00),(3,2,5,50.00,48000.00,2400000.00),(4,2,6,25.00,52000.00,1300000.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_transactions`
--

LOCK TABLES `purchase_transactions` WRITE;
/*!40000 ALTER TABLE `purchase_transactions` DISABLE KEYS */;
INSERT INTO `purchase_transactions` VALUES (1,'PO-202611-0001',1,1,7,'2026-11-06','2026-11-12',23000000.00,2530000.00,25530000.00,'COMPLETED','Pesanan PO pengadaan perangkat keras IT ke PT Mitra Solusi Tekno','2026-11-06 10:30:00','2026-11-21 13:02:51','2026-11-12 15:00:00',NULL),(2,'PO-202611-0002',2,2,8,'2026-11-09','2026-11-14',3840000.00,422400.00,4262400.00,'CONFIRMED','Pesanan PO restock kertas HVS ke CV Graha Grafika Kertas','2026-11-09 13:00:00','2026-11-21 13:02:51',NULL,NULL);
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
  `employee_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `recipient_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_holder` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reimbursements`
--

LOCK TABLES `reimbursements` WRITE;
/*!40000 ALTER TABLE `reimbursements` DISABLE KEYS */;
INSERT INTO `reimbursements` VALUES (1,1,3,1950000.00,NULL,NULL,NULL,NULL,NULL,'BANK_TRANSFER','TRX-BCA-9812401','2026-11-05','Reimbursement dinas Budi Santoso','PAID',5,'2026-11-05 15:30:00','2026-11-05 02:00:00','2026-11-21 06:02:51'),(2,2,4,400000.00,NULL,NULL,NULL,NULL,NULL,'BANK_TRANSFER','TRX-BNI-7712390','2026-11-09','Reimbursement ATK Sari Putri','PAID',5,'2026-11-09 16:00:00','2026-11-09 03:00:00','2026-11-21 06:02:51'),(3,6,NULL,4500000.00,'Andi Pratama (PIC Operasional)','BCA','8820192837','Andi Pratama',NULL,'BANK_TRANSFER','TRF-FULL-PAY-20261121','2026-11-21','Pencairan penuh seluruh pengeluaran operasional November 2026','PAID',5,'2026-11-21 19:19:27','2026-11-21 12:19:27','2026-11-21 12:19:27'),(4,8,NULL,4500000.00,'Andi Pratama (PIC Operasional)','BCA','8820192837','Andi Pratama',NULL,'BANK_TRANSFER','TRF-FULL-PAY-20261121','2026-11-21','Pencairan penuh seluruh pengeluaran operasional November 2026','PAID',5,'2026-11-21 19:25:34','2026-11-21 12:25:34','2026-11-21 12:25:34'),(5,10,NULL,4500000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Antrean reimbursement gabungan laporan EXP-2026-445','PENDING',NULL,NULL,'2026-11-21 12:27:33','2026-11-21 12:27:33'),(6,12,NULL,4500000.00,'Andi Pratama (PIC Operasional)','BCA','8820192837','Andi Pratama',NULL,'BANK_TRANSFER','TRF-FULL-PAY-20261121','2026-11-21','Pencairan penuh seluruh pengeluaran operasional November 2026','PAID',5,'2026-11-21 19:36:35','2026-11-21 12:36:35','2026-11-21 12:36:35');
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
INSERT INTO `role_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(13,1),(14,1),(15,1),(1,2),(8,2),(1,3),(8,3),(1,4),(6,5),(7,5),(4,6),(5,6),(2,7),(3,7),(2,8),(3,8),(6,9),(7,9),(4,10),(5,10),(4,11),(13,11),(13,12),(1,13),(8,13),(14,14),(1,15),(2,15),(4,15),(6,15),(8,15),(13,15),(14,15),(15,15),(1,16),(8,16),(13,17),(13,18);
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
INSERT INTO `roles` VALUES (1,1,'Admin_HR','Administrator HR & Kepegawaian','2026-10-17 01:00:00'),(2,2,'Admin_Purchase','Manajer Pengadaan & Purchasing','2026-10-17 01:00:00'),(3,3,'Purchasing_Staff','Staf Pengadaan & Logistik Masuk','2026-10-17 01:00:00'),(4,4,'Admin_Sales','Manajer Penjualan & Sales ERP','2026-10-17 01:00:00'),(5,5,'Marketing_Staff','Staf Penjualan & Pemasaran','2026-10-17 01:00:00'),(6,6,'Admin_Warehouse','Kepala Gudang & Inventaris','2026-10-17 01:00:00'),(7,7,'Warehouse_Staff','Staf Gudang & Pergerakan Stok','2026-10-17 01:00:00'),(8,8,'ADMIN','Administrator Sistem GT Expense','2026-10-17 01:00:00'),(13,13,'FINANCE','Staf Keuangan & Pembukuan Pengeluaran','2026-10-17 01:00:00'),(14,14,'MANAGER','Manajer / Approver Laporan Pengeluaran','2026-10-17 01:00:00'),(15,15,'EMPLOYEE','Pengguna Karyawan Modul GT Expense','2026-10-17 01:00:00');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_roles_before_insert` BEFORE INSERT ON `roles` FOR EACH ROW SET NEW.role_id = IF(NEW.role_id IS NULL, NEW.id_role, NEW.role_id) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_roles_before_update` BEFORE UPDATE ON `roles` FOR EACH ROW SET NEW.role_id = IF(NEW.role_id IS NULL, NEW.id_role, NEW.role_id) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_order_items`
--

LOCK TABLES `sales_order_items` WRITE;
/*!40000 ALTER TABLE `sales_order_items` DISABLE KEYS */;
INSERT INTO `sales_order_items` VALUES (1,1,1,2,11500000.00,23000000.00),(2,1,2,2,2450000.00,4900000.00),(3,2,10,4,1450000.00,5800000.00),(4,3,5,20,62000.00,1240000.00),(5,3,8,10,185000.00,1850000.00),(6,3,9,15,42000.00,630000.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
/*!40000 ALTER TABLE `sales_orders` DISABLE KEYS */;
INSERT INTO `sales_orders` VALUES (1,'SO-202611-0001',1,'2026-11-10','paid',27900000.00,0.00,3069000.00,30969000.00,'Pengadaan paket workstation IT untuk PT Nusantara Solusindo Data',9,'2026-11-10 02:00:00','2026-11-21 06:02:51'),(2,'SO-202611-0002',2,'2026-11-14','confirmed',5800000.00,0.00,638000.00,6438000.00,'Pesanan 4 unit kursi ergonomis kantor untuk CV Bintang Niaga',10,'2026-11-14 04:20:00','2026-11-21 06:02:51'),(3,'SO-202611-0003',3,'2026-11-18','draft',3700000.00,0.00,407000.00,4107000.00,'Penyediaan perlengkapan ATK dan ordner arsip PT Digital Sinergi Kreasi',10,'2026-11-18 08:45:00','2026-11-21 06:02:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'001_gt_expense_integrity.sql','2026-10-17 01:00:00'),(2,'002_add_bank_name_to_expenses.sql','2026-10-17 01:00:00'),(3,'20261017_expenses_integration.sql','2026-10-17 01:00:00'),(4,'003_multi_employee_expense_management.sql','2026-11-20 01:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','PT Sinar Nusantara Mandiri','2026-11-20 01:00:00'),(2,'company_email','kontak@sinarnusantara.co.id','2026-11-20 01:00:00'),(3,'company_phone','021-5889012','2026-11-20 01:00:00'),(4,'company_address','Gedung Sinar Tower Lt. 12, Jl. Jend. Sudirman Kav. 45, Jakarta Selatan','2026-11-20 01:00:00'),(5,'currency','IDR','2026-11-20 01:00:00'),(6,'tax_rate','11','2026-11-20 01:00:00'),(7,'expense_approval_threshold','5000000','2026-11-20 01:00:00'),(8,'purchase_approval_threshold','5000000','2026-11-20 01:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,'ADJUSTMENT_IN',15.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(2,2,'ADJUSTMENT_IN',20.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(3,3,'ADJUSTMENT_IN',50.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(4,4,'ADJUSTMENT_IN',25.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(5,5,'ADJUSTMENT_IN',150.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(6,6,'ADJUSTMENT_IN',100.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(7,7,'ADJUSTMENT_IN',18.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(8,8,'ADJUSTMENT_IN',40.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(9,9,'ADJUSTMENT_IN',75.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(10,10,'ADJUSTMENT_IN',12.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(11,11,'ADJUSTMENT_IN',8.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(12,12,'ADJUSTMENT_IN',300.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(13,13,'ADJUSTMENT_IN',180.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(14,14,'ADJUSTMENT_IN',20.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00'),(15,15,'ADJUSTMENT_IN',45.000,'INIT-STOCK-2026',NULL,NULL,11,'Saldo awal persediaan fisik gudang','2026-10-10 08:30:00');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
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
INSERT INTO `suppliers` VALUES (1,'SUP-001','PT Mitra Solusi Tekno','021-5552345','sales@mitrasolusi.co.id','Kawasan Industri Pulogadung Blok B No. 12, Jakarta Timur',1,'2026-10-01 08:00:00','2026-11-21 13:02:51',NULL),(2,'SUP-002','CV Graha Grafika Kertas','021-7789012','order@grahagrafika.id','Jl. Otista Raya No. 45, Jakarta Timur',1,'2026-10-01 08:00:00','2026-11-21 13:02:51',NULL),(3,'SUP-003','PT Sumber Abadi Logistik Pack','021-8891234','halo@sumberabadi.co.id','Jl. Daan Mogot KM 14 No. 8, Jakarta Barat',1,'2026-10-01 08:00:00','2026-11-21 13:02:51',NULL),(4,'SUP-004','CV Ergonomi Sentosa Abadi','021-6678901','furniture@ergonomisentosa.com','Kawasan Industri Jababeka 1, Cikarang, Bekasi',1,'2026-10-01 08:00:00','2026-11-21 13:02:51',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Hendra Wijaya (Admin GT Expense)','admin_gte','admin@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','ADMIN',1,'2026-10-17 08:00:00','2026-11-21 18:05:46',NULL,1,1,8,'2026-11-21 18:05:46'),(2,'Rina Manajer (Manager Finance)','rina_manager','manager@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','MANAGER',1,'2026-10-17 08:00:00','2026-11-21 19:37:24',NULL,2,2,14,'2026-11-21 19:37:24'),(3,'Budi Santoso (Staff IT)','budi_employee','budi@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','EMPLOYEE',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,3,3,15,NULL),(4,'Sari Putri (Staff Finance)','sari_employee','sari@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','EMPLOYEE',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,4,4,15,NULL),(5,'Dimas Finance (Treasury)','dimas_finance','finance@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','FINANCE',1,'2026-10-17 08:00:00','2026-11-21 19:37:24',NULL,5,5,13,'2026-11-21 19:37:24'),(6,'Yudha Dwi Pratama (Admin HR)','Yuraa_n','yudha@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Admin_HR',1,'2026-10-17 08:00:00','2026-11-21 18:00:08',NULL,6,6,1,'2026-11-21 18:00:08'),(7,'Salma Suhaimah (Admin Purchase)','saruma','salma@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Admin_Purchase',1,'2026-10-17 08:00:00','2026-11-21 18:00:40',NULL,7,7,2,'2026-11-21 18:00:40'),(8,'Arafat Hamid (Purchasing Staff)','alfckqih','arafat@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Purchasing_Staff',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,8,8,3,NULL),(9,'Afsaal Fayyaz (Admin Sales)','fayyaz','afsaal@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Admin_Sales',1,'2026-10-17 08:00:00','2026-11-21 18:05:02',NULL,9,9,4,'2026-11-21 18:05:02'),(10,'Zakie Kurniawan (Marketing Staff)','zakiee','zakie@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Marketing_Staff',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,10,10,5,NULL),(11,'Sabiq Hibatul (Admin Warehouse)','masbiku','sabiq@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Admin_Warehouse',1,'2026-10-17 08:00:00','2026-11-21 18:01:25',NULL,11,11,6,'2026-11-21 18:01:25'),(12,'Halim Thio (Warehouse Staff)','halimawan','thio@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Warehouse_Staff',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,12,12,7,NULL),(13,'Retno Wulandari (HR Recruiter)','retno_hr','retno@gmail.com','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','Admin_HR',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,13,13,1,NULL),(14,'Citra Amelia (Senior Tax)','citra_finance','citra@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','FINANCE',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,14,14,13,NULL),(15,'Andi Pratama (IT Specialist)','andi_it','andi.pratama@example.test','$2y$10$8IfKnlexnABQtWbHtLfOmO7u2TTjE3w8QAz511VIZ1thIPY3EYctm','EMPLOYEE',1,'2026-10-17 08:00:00','2026-11-21 13:02:51',NULL,15,15,15,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_invoices`
--

LOCK TABLES `vendor_invoices` WRITE;
/*!40000 ALTER TABLE `vendor_invoices` DISABLE KEYS */;
INSERT INTO `vendor_invoices` VALUES (1,'INV-MST-202611-089',1,25530000.00,'2026-11-12',7,'MATCHED','Sesuai dengan PO-202611-0001 dan GRN-202611-0001 (3-way match valid)','APPROVED',2,'2026-11-13 10:00:00','Disetujui untuk pembayaran via transfer Mandiri','2026-11-12 16:00:00',NULL);
/*!40000 ALTER TABLE `vendor_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `hris`
--

USE `hris`;

--
-- Final view structure for view `divisi`
--

/*!50001 DROP VIEW IF EXISTS `divisi`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `divisi` AS select `departments`.`department_id` AS `id_divisi`,`departments`.`department_name` AS `divisi` from `departments` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

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

-- Dump completed on 2026-11-21 19:38:44
