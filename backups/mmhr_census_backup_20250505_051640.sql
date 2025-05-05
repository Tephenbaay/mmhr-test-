-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mmhr_census
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_notes`
--

DROP TABLE IF EXISTS `admin_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `note` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` varchar(100) DEFAULT 'admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notes`
--

LOCK TABLES `admin_notes` WRITE;
/*!40000 ALTER TABLE `admin_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leading_causes`
--

DROP TABLE IF EXISTS `leading_causes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leading_causes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `icd_10` varchar(255) DEFAULT NULL,
  `sheet_name` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `leading_causes_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `uploaded_files` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leading_causes`
--

LOCK TABLES `leading_causes` WRITE;
/*!40000 ALTER TABLE `leading_causes` DISABLE KEYS */;
/*!40000 ALTER TABLE `leading_causes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_logs`
--

DROP TABLE IF EXISTS `maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_logs`
--

LOCK TABLES `maintenance_logs` WRITE;
/*!40000 ALTER TABLE `maintenance_logs` DISABLE KEYS */;
INSERT INTO `maintenance_logs` VALUES (1,'Test','2025-05-02 08:21:06');
/*!40000 ALTER TABLE `maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_records`
--

DROP TABLE IF EXISTS `patient_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(255) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `sheet_name` varchar(255) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `discharge_date` date DEFAULT NULL,
  `member_category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `patient_records_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `uploaded_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_records`
--

LOCK TABLES `patient_records` WRITE;
/*!40000 ALTER TABLE `patient_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_records_2`
--

DROP TABLE IF EXISTS `patient_records_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_records_2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) DEFAULT NULL,
  `sheet_name_2` varchar(255) DEFAULT NULL,
  `admission_date_2` date DEFAULT NULL,
  `patient_name_2` varchar(255) DEFAULT NULL,
  `category_2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `patient_records_2_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `uploaded_files` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_records_2`
--

LOCK TABLES `patient_records_2` WRITE;
/*!40000 ALTER TABLE `patient_records_2` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_records_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_records_3`
--

DROP TABLE IF EXISTS `patient_records_3`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_records_3` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `sheet_name_3` varchar(255) NOT NULL,
  `patient_name_3` varchar(255) NOT NULL,
  `date_admitted` date NOT NULL,
  `date_discharge` date NOT NULL,
  `category` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `patient_records_3_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `uploaded_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_records_3`
--

LOCK TABLES `patient_records_3` WRITE;
/*!40000 ALTER TABLE `patient_records_3` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_records_3` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `retention_days` int(11) DEFAULT 30,
  `enable_auto_delete` tinyint(1) DEFAULT 0,
  `max_upload_files` int(11) DEFAULT 5,
  `max_file_size_mb` int(11) DEFAULT 10,
  `allowed_file_extensions` varchar(255) DEFAULT 'xls,xlsx',
  `theme_mode` varchar(20) DEFAULT 'light',
  `font_size` varchar(20) DEFAULT 'medium',
  `layout_spacing` varchar(20) DEFAULT 'comfortable',
  `admin_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,30,1,15,100,'xls,xlsx','light','medium','comfortable',NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `storage_settings`
--

DROP TABLE IF EXISTS `storage_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `storage_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quota_mb` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `storage_settings`
--

LOCK TABLES `storage_settings` WRITE;
/*!40000 ALTER TABLE `storage_settings` DISABLE KEYS */;
INSERT INTO `storage_settings` VALUES (1,200);
/*!40000 ALTER TABLE `storage_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` text NOT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,'Deleted all uploads','','2025-05-02 16:52:30'),(2,'Deleted all uploads','','2025-05-02 16:55:02'),(3,'Deleted all uploads','','2025-05-05 08:24:47'),(4,'Deleted all uploads','','2025-05-05 08:27:20'),(5,'Deleted all uploads','admin','2025-05-05 08:46:22'),(6,'Deleted all uploads','admin','2025-05-05 09:24:34'),(7,'Deleted all uploads','admin','2025-05-05 09:33:36'),(8,'Deleted all uploads','admin','2025-05-05 09:34:29'),(9,'Deleted all uploads','admin','2025-05-05 09:35:36'),(10,'Deleted all uploads','admin','2025-05-05 09:44:56'),(11,'Deleted all uploads','admin','2025-05-05 09:47:06'),(12,'Deleted all uploads','admin','2025-05-05 09:50:29'),(13,'Deleted all uploads','admin','2025-05-05 09:53:49'),(14,'System reset','admin','2025-05-05 10:50:50'),(15,'System reset','admin','2025-05-05 11:16:26');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (10,'max_upload_files','20'),(11,'max_file_size_mb','100'),(12,'allowed_file_extensions','xls,xlsx');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `updates`
--

DROP TABLE IF EXISTS `updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `updates`
--

LOCK TABLES `updates` WRITE;
/*!40000 ALTER TABLE `updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploaded_files`
--

DROP TABLE IF EXISTS `uploaded_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploaded_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploaded_files`
--

LOCK TABLES `uploaded_files` WRITE;
/*!40000 ALTER TABLE `uploaded_files` DISABLE KEYS */;
INSERT INTO `uploaded_files` VALUES (134,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 07:35:55'),(135,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 07:58:59'),(136,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:02:05'),(137,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:03:00'),(138,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:03:51'),(139,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:05:13'),(140,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:06:24'),(141,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:08:02'),(142,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:08:59'),(143,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:09:57'),(144,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:54:37'),(145,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:56:36'),(146,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:57:53'),(147,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-04-30 08:59:06'),(148,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-05-02 00:27:00'),(149,'2025 DISCHARGE_TRANSMITTAL MONITORING  (2).xlsx','2025-05-02 00:32:13');
/*!40000 ALTER TABLE `uploaded_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) DEFAULT 'user',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'steph@gmail.com','steph','$2y$10$O/T70QnHmP1l47kE2NeXlOmV.Ee.EkdZ4q2GcfSuD8EYDMYsX6Uhm','user'),(5,'test_admin@mmhr.com','test_admin','$2y$10$kkujoyCHRGTtqcFa4psv9eW9ogdd.hDsqBIOFBZe2nl5Z6vS1F1ry','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-05 11:17:03
