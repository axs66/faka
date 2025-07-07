-- MySQL dump 10.13  Distrib 5.7.40, for Linux (x86_64)
--
-- Host: localhost    Database: wechatfaka
-- ------------------------------------------------------
-- Server version	5.7.40-log

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
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password_hash` varchar(255) NOT NULL COMMENT '密码哈希',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `last_login` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_attempts` int(11) DEFAULT '0' COMMENT '登录尝试次数',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT '锁定到期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_last_login` (`last_login`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='管理员用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$YLSM4/zM3MVL2BQ92fPBj.nbQHY2CoFGeZZS5/.739K/VzG3qApaq','apibug6@gmail.com','2025-07-05 00:55:42',0,NULL,'2025-07-04 22:48:33','2025-07-05 00:55:42');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_logs`
--

DROP TABLE IF EXISTS `auth_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wxid` varchar(255) NOT NULL COMMENT '微信ID',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `request_data` text COMMENT '请求数据',
  `response_data` text COMMENT '响应数据',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_wxid` (`wxid`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COMMENT='验证日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_logs`
--

LOCK TABLES `auth_logs` WRITE;
/*!40000 ALTER TABLE `auth_logs` DISABLE KEYS */;
INSERT INTO `auth_logs` VALUES (1,'unknown','error','104.23.175.169','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"verfy\"}','{\"error\":\"Invalid JSON data\"}','2025-07-04 23:22:26'),(2,'zhengyadong949547132','verify_failed','104.23.175.169','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"data\":\"+ikEdmxOSYxe\\/Yxy9U7WyVfbxy\\/gmMvrwXw4NAPrItifJQXebXIsNZ8PHnreOsL4JbiortHYELlh97CKskDJFw==\"}','{\"error\":\"Invalid data format\"}','2025-07-04 23:22:52'),(3,'unknown','error','104.23.175.169','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"verfy\"}','{\"error\":\"Invalid request data\"}','2025-07-04 23:22:52'),(4,'zhengyadong949547132','verify','108.162.226.202','curl/8.1.2','{\"data\":\"+ikEdmxOSYxe\\/Yxy9U7WyVfbxy\\/gmMvrwXw4NAPrItifJQXebXIsNZ8PHnreOsL4JbiortHYELlh97CKskDJFw==\"}','{\"authorized\":false,\"expire_timestamp\":0,\"current_timestamp\":1751672234,\"wxid\":\"zhengyadong949547132\"}','2025-07-04 23:37:14'),(5,'zhengyadong949547132','verify','104.23.175.184','curl/8.1.2','{\"data\":\"Oj4DgpG7IjIQPwbdTQ0XLCSjCzZyaVaDcY6YIAn0RliRhceZtY\\/4uPEvI8mzLIiFxWq9B87x6Os+qp6q\\/qtGuA==\"}','{\"authorized\":false,\"expire_timestamp\":0,\"current_timestamp\":1751672988,\"wxid\":\"zhengyadong949547132\"}','2025-07-04 23:49:48'),(6,'zhengyadong949547132','verify','172.68.164.96','curl/8.1.2','{\"data\":\"Oj4DgpG7IjIQPwbdTQ0XLCSjCzZyaVaDcY6YIAn0RliRhceZtY\\/4uPEvI8mzLIiFxWq9B87x6Os+qp6q\\/qtGuA==\"}','{\"authorized\":false,\"expire_timestamp\":0,\"current_timestamp\":1751673075,\"wxid\":\"zhengyadong949547132\"}','2025-07-04 23:51:15'),(7,'zhengyadong949547132','verify','162.158.108.168','curl/8.1.2','{\"data\":\"Oj4DgpG7IjIQPwbdTQ0XLCSjCzZyaVaDcY6YIAn0RliRhceZtY\\/4uPEvI8mzLIiFxWq9B87x6Os+qp6q\\/qtGuA==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-04 23:51:36'),(8,'zhengyadong949547132','verify','172.70.142.148','curl/8.1.2','{\"data\":\"Oj4DgpG7IjIQPwbdTQ0XLCSjCzZyaVaDcY6YIAn0RliRhceZtY\\/4uPEvI8mzLIiFxWq9B87x6Os+qp6q\\/qtGuA==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-04 23:53:52'),(9,'zhengyadong949547132','verify_expired','172.71.124.134','curl/8.1.2','{\"data\":\"Oj4DgpG7IjIQPwbdTQ0XLCSjCzZyaVaDcY6YIAn0RliRhceZtY\\/4uPEvI8mzLIiFxWq9B87x6Os+qp6q\\/qtGuA==\"}','{\"authorized\":false,\"message\":\"\\u8bf7\\u6c42\\u5df2\\u8fc7\\u671f\"}','2025-07-04 23:56:03'),(10,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"LhtwnO15QhUWMNiyDZ2ZYafUjcmR9DD14ZOQSm4KtOVA66r1vNPEB67O9\\/0wdKukxOwWstGUaTvs\\/2qPN40n3A==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 00:55:04'),(12,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"m8mKw0SnezA\\/BTljE7cFZBsESDXCpBFxvuAYb6HNeE\\/zY93d1sBj1\\/pWox7j9NCaLiqL6OL+qGlSpbhW41LT9Q==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 00:55:22'),(13,'wxid_ud1cgcq0sk8412','redeem_success','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"card_code\":\"Apibug_EA4B491CBD468DAB\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"success\":true,\"duration_days\":3,\"expire_timestamp\":1751936155}','2025-07-05 00:55:55'),(14,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"Ksx0FSpJbuLsVRTMu+pzBOcnCyyCneS03thztjqmqj6mlACTA5Yy7dUy\\/3a1UCjChmA2nE4zVzwuL\\/CifdlQXQ==\"}','{\"authorized\":true}','2025-07-05 00:56:52'),(15,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"EZ4N4kh\\/tqZkCFulTC0452oUITjRafzLFY1ALTp7pvAKn6PPYVfelSMQWzFOQ04F2BwvmqT4wWxI82s0ZjYIXg==\"}','{\"authorized\":true}','2025-07-05 00:56:55'),(16,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"+zPtBR\\/1d78hybx3+RtxIT3nBaN+aYkVtSnd\\/WDztGbrQIicZXYxYgIAzL1YoMI83SLpxbpmm0MEFCMhVLA4dQ==\"}','{\"authorized\":true}','2025-07-05 00:57:13'),(17,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"FfhyPzeWp6ivCHRro+3EY76xWlH+dKX2BpSCxXr3RrijT2UH6F2t9A3pVdU9jLId0yXZcWXF86pgBzGH7+oy0Q==\"}','{\"authorized\":true}','2025-07-05 00:57:15'),(18,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"6fFSXyZmvMKH7oJXBOLd1HMxYl8hUgMKohwVj1T7b4Hyo0JW44NYIDW60Z466xKHc70pWRQ1OwowHDiuiZco+g==\"}','{\"authorized\":true}','2025-07-05 00:57:22'),(19,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"zLjapM+ZCz5YBndeBjuJE\\/LAESUPXVt+2YX71t66O6EtYre6XAktEK0lIEqCOrQGfNpWd66Ok10uGLe\\/lpJniA==\"}','{\"authorized\":true}','2025-07-05 00:57:50'),(20,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"lkeQiYafAx+sqrG53bDKXTjXu8chBXIbB6O53wZcSP8FCasrsYAzlw42b3wv1\\/tJybHGfAmUXNl3DnfgoAfVnw==\"}','{\"authorized\":true}','2025-07-05 00:58:16'),(21,'wxid_ud1cgcq0sk8412','verify','172.71.210.160','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"CPIq8k9qmcUoQzAqqEay+8xXqWcX4scgQ8EX4PI4BZje27RTxCFDs3XageA\\/hhF9PLV9jhLilN4orV4A3vYzgg==\"}','{\"authorized\":true}','2025-07-05 00:58:19'),(22,'wxid_ud1cgcq0sk8412','verify','162.158.166.247','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"WShiYZoSOQo+DmPLxZpRoVlQ3JqsZKEninC31+fo47NBq1miQMwigIU3moPdOiQNRdlZh+L+LDjRByjAGQDIoQ==\"}','{\"authorized\":true}','2025-07-05 00:58:29'),(23,'wxid_ud1cgcq0sk8412','verify','162.158.166.247','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"ImMunDkNZ6P\\/CRYa\\/Me\\/3v3q4CazmmqaMlfpnyrz2lJvdRZlQJ1CJUdZrESoW\\/eCd9EmlTcPtdj6eFDhvXQslQ==\"}','{\"authorized\":true}','2025-07-05 00:58:30'),(24,'wxid_ud1cgcq0sk8412','verify','162.158.166.247','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"dFRSmMuIiI+6KTLOfT4he30hbhMi98v93yNr8SSXI4mKv6tuPShZVwjDg+e1l6s8HJVKq5KNSTNhkmS\\/mVVZTg==\"}','{\"authorized\":true}','2025-07-05 00:58:47'),(25,'wxid_ud1cgcq0sk8412','verify','162.158.166.247','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"IE14x8r4uMYP09UoyrHRYmMO5oqxm8GlgtGPQQpde+eMV9nqzL2n\\/vPNC0HR6jIcpOMRZgdz\\/z3mpF1h9bqU9g==\"}','{\"authorized\":true}','2025-07-05 00:58:48'),(26,'wxid_ud1cgcq0sk8412','verify','162.158.166.247','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"NO6QPLr5Yl7XfmeHW0DrblyoTyERJPVUldZbJtZEvRKhZTZXqgP7V24H8DCiCf46OOzICwvjUcnAvELpddA6BA==\"}','{\"authorized\":true}','2025-07-05 00:58:50'),(27,'wxid_ud1cgcq0sk8412','verify','162.158.166.247','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"CdczHUr0lBuvtNFjuUQqmUSjng4SCXlUou4EQ+Qy3sP3bgbyebrh7aYMaReUIHyFVFam\\/l5EH1NA+tsPZRF2xg==\"}','{\"authorized\":true}','2025-07-05 00:58:51'),(28,'wxid_ud1cgcq0sk8412','verify','172.71.210.38','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"EyD4CtLp1Qgz99yjzYFmjCktpktQP5EatlfE3iFGAKLKyniZhvDt+XYq9OU28pzkuFJRp9P2SBV8v0jrcmQoow==\"}','{\"authorized\":true}','2025-07-05 01:07:00'),(29,'wxid_ud1cgcq0sk8412','redeem_success','172.71.124.146','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"card_code\":\"Apibug_1B536EAD0B3EAAC7\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"success\":true,\"duration_days\":30,\"expire_timestamp\":1754528155}','2025-07-05 01:15:29'),(30,'wxid_ud1cgcq0sk8412','query_auth','172.71.124.146','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"query_auth\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"success\":true,\"authorized\":true,\"expire_date\":\"2025-08-07 08:55:55\",\"expired\":false,\"message\":\"\\u6388\\u6743\\u6709\\u6548\"}','2025-07-05 01:15:34'),(31,'wxid_ud1cgcq0sk8412','verify','172.71.219.103','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"byC0FnfwzWuuEDxvlyfeyMqLDfcIbBO6x6VA9Llu8YPFA2130UQsZBMA7Z2CD2x0cTskr04bPbaa2w+LaNObUw==\"}','{\"authorized\":true}','2025-07-05 01:15:45'),(32,'wxid_ud1cgcq0sk8412','verify','162.158.42.25','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"BZZWGaSYeo+QV42yLUdHyT1uKAcxkUbhU7EZJiWKQnBwn+9N09gW1Fdw8RwlBxb39kgUFKoqKUaawYzMoP5o8w==\"}','{\"authorized\":true}','2025-07-05 01:16:24'),(33,'wxid_ud1cgcq0sk8412','verify','162.158.179.188','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"prCAYXdTU6ZH6hW3yerCPYpmU+Eb06tVLSAqLKgb5bCKch+P7PidQGhdhZR0HVxY5K6IH8alMOTWoU3gFjvDQQ==\"}','{\"authorized\":true}','2025-07-05 01:22:45'),(34,'wxid_ud1cgcq0sk8412','verify','162.158.178.147','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"SmzPecZ+FuuXKKSMFyvfq3UZ3uYS7a1s3rAj8HTsjL2+CLo+zpY+8xChWYUfpAmuLQ7rTZAzhQZBclZS3+L9wA==\"}','{\"authorized\":true}','2025-07-05 01:22:45'),(35,'wxid_ud1cgcq0sk8412','verify','162.158.179.188','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"mO8Tl+kgM2KUnJizIXY1sGHyG8DneWEtsQ\\/z6HObXqCEPiIcukUus7aEQtzYVmZs3E06nXxxaLQDaIytElPS0w==\"}','{\"authorized\":true}','2025-07-05 01:22:46'),(36,'wxid_ud1cgcq0sk8412','verify','162.158.179.188','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"gWQfGq30Sr98yNWs3PnswZNN3F7i\\/29iSK0fIkiQSxBwvqLQjNlZrah\\/2NxTbSdhsynGMoyC4nJ++3J7VejGiA==\"}','{\"authorized\":true}','2025-07-05 01:22:55'),(37,'wxid_ud1cgcq0sk8412','verify','162.158.179.188','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"dn\\/SXG9G1UVPNIRxUpSUXWtGZbb6j4JD8BEWHpU9+0NEgHnUEGMBQr4eyThhUCLzDiOqcK1Tb4m7tn+wlxH4vA==\"}','{\"authorized\":true}','2025-07-05 01:22:58'),(38,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"UoIi6Y0ZUdSWnF9c6O6BM2vma3T5H77tQl2C0etyTt0MdV6H2VGIYqHj6agRX008oWZSEXrolknj+EiC98zEdg==\"}','{\"authorized\":true}','2025-07-05 01:23:49'),(39,'wxid_ud1cgcq0sk8412','verify','172.71.215.112','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"eIQ+KL9V7HkQUbKGRfqXCHfZBewbAHGq+OExJ2D6py4HRrgAdEzlOAFm8\\/NjikMPuChVzquAUGVZ5KG3Uj5rLg==\"}','{\"authorized\":true}','2025-07-05 01:23:51'),(40,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"2bSShjLqGaJjztokMMRTwZAlXlBL4a5n5vZWzkMYW55otNZGklpYAxfCju2YqB5oleZfMJo9MNf90CnphuuUSg==\"}','{\"authorized\":true}','2025-07-05 01:23:51'),(41,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"3ennkQDKCoU9+zo1brN2fBrKpLC0RYK2oRSUFV7t\\/7yZOnxQ52ZTioC3U8u4HIsSYqxKeVlzL4dy0lUDyiPDfA==\"}','{\"authorized\":true}','2025-07-05 01:23:53'),(42,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"KjZ2dl2x4Tr42fRRh8FnZ2lbyv3xTPiFXSkdjswKx1Rmv+VHF19qFFCyOfruYnNRDmf4BFD1T7N3yvU0baP9lw==\"}','{\"authorized\":true}','2025-07-05 01:23:54'),(43,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"aRBFiq\\/cFbrNc\\/jO0p9zebFNO8rMskTJ6WVbfruhsIuZCT\\/QPNAAHgTB6hbQmov4okS\\/u0Sr0VSMQ7QyhBKQ\\/A==\"}','{\"authorized\":true}','2025-07-05 01:23:58'),(44,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"ThshLnQemlwG5\\/ezA8FWsZFaYEtgOkMPTuGZH7KuylVBnh5PzS588mecOw+f1RfZpy4bpnUnjcL2rNJOSD4UoQ==\"}','{\"authorized\":true}','2025-07-05 01:24:00'),(45,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"tFIYQp1RWtToztQBORTzK5Be\\/sRFuUtJfwmGnhfaWWW2P4sRpJ3w57Qh4PxhYqUBv2cIHhd\\/Ii8sZ6bJ5hc2rA==\"}','{\"authorized\":true}','2025-07-05 01:24:01'),(46,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"3xCdtvNCzm+rAsSw6NIN1XDt5+A2dktoHMokY2eX4xCR4ySmuWWbJmdLrrBEqgShnLGep3nYCfDo+M00HIwcfg==\"}','{\"authorized\":true}','2025-07-05 01:24:03'),(47,'wxid_ud1cgcq0sk8412','verify','172.71.215.112','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"+wWzoKDXHJQxdieJRc7LSdkJLJQc5mneDPWvEcqwe2kdSYcuxWnKm5UEZx\\/yqfI\\/+ZQNw\\/NlNC+2ieLQH5IbiA==\"}','{\"authorized\":true}','2025-07-05 01:24:04'),(48,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"+U2sExOMgRlCHL+rS0Yb9u6QmqPOJawr4q1uvWB46qtOnctI47NxC\\/wrizBjSZuXtrmD7GTrRlKz\\/1P\\/KzkoxQ==\"}','{\"authorized\":true}','2025-07-05 01:24:05'),(49,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"4jAfulyszO3bf0oppD6xqJ0a+WpgFdDvq2SjPAppXP+h1c6Xyudd\\/tW\\/Da7LE3Ps1W97NSlInNuYi\\/MATTf4nQ==\"}','{\"authorized\":true}','2025-07-05 01:24:07'),(50,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"BK6EquClVktbFskWJkifiL5HXY0wKn6fIY9iTdw2cTEGQx19Qh3CUE0PITxSK\\/pL9uhIub7XuOPrXXOOznJPBA==\"}','{\"authorized\":true}','2025-07-05 01:24:08'),(51,'wxid_ud1cgcq0sk8412','verify','162.158.178.61','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"6yQv6LHHH4XpAFRwwD37CKSVJ+UfkBQWhZeOB827g+yy1b43lHPbATvWBvJpE7y7twyjAr2Con1WrU6qb8llAQ==\"}','{\"authorized\":true}','2025-07-05 01:24:13'),(52,'wxid_ud1cgcq0sk8412','verify','162.158.193.39','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"uCyj6ks71uFSoOFOqKfr7576UuhT+v8Y1IKaEGkN0jdog84EnmAmoUxIdL8aZP7YWsjgdY1G8Gngxv8zQbDEOA==\"}','{\"authorized\":true}','2025-07-05 01:25:07'),(53,'wxid_ud1cgcq0sk8412','verify','162.158.193.39','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"yOelyu71QpB5aIZISFmr5ZqvXsP+Bl8Umjxl+VGVqgCtxndpDVuiUu15jJxhv2yDsZhJVJqDkchHDyKadVkHSg==\"}','{\"authorized\":true}','2025-07-05 01:25:08'),(54,'wxid_ud1cgcq0sk8412','verify','162.158.193.39','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"6hfQvbTtzE4F3x+N0tZmPzLVAT\\/yI77WDgxtrvNoWEkEc7WfiotXKOrqS52VN2a2speyYXP8MhhGpv8J7xBO5Q==\"}','{\"authorized\":true}','2025-07-05 01:25:10'),(55,'wxid_ud1cgcq0sk8412','query_no_auth','108.162.227.64','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"query_auth\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"authorized\":false}','2025-07-05 01:28:33'),(56,'wxid_ud1cgcq0sk8412','query_no_auth','108.162.227.64','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"query_auth\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"authorized\":false}','2025-07-05 01:28:34'),(57,'wxid_ud1cgcq0sk8412','query_no_auth','108.162.227.64','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"query_auth\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"authorized\":false}','2025-07-05 01:28:35'),(58,'wxid_ud1cgcq0sk8412','query_no_auth','108.162.227.64','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"query_auth\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"authorized\":false}','2025-07-05 01:28:35'),(59,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"gS8ux+s4ZozcLMk5sspVUTMiZaFNKVbaFKkqW2Qz\\/s8h6DMebpsgrMdiMaRP3xTAN1x3P5StBk7pEPxqEkfE2A==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:28:50'),(60,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"ol0\\/8lFM1J\\/hJ+DWRZ6O6sOy5IiW6bpdANlnDLtEO6rOEaYv3P0IVkQ1NTm9u63h2rwIiNLEQNjgYd3BVizD7g==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:28:55'),(61,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"Oo4ARNC19F2YpeHR7c12SQPkMvL0845omxSGorG7mWYK0TKCPSm+IAAr6wpIzZJLyYQPx43xUEkF4yCC\\/T\\/GMQ==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:29:18'),(62,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"umCoE3iAJiD1MhSbZ4DntpfqtaJj9rxU70BU0aJRU\\/SF7KPOxlWd4rEmhpwupjQVf5Lhx+k\\/lNFy2UHLZDfclw==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:29:24'),(63,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"SK8XgzYc\\/FGYLcZnCtyan8t2\\/yr1VCB5fsA0zGWhiqVvWpwYC9TZvyhGQGXcN9LvWtM+heirvstqC1b7uhORlg==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:29:28'),(64,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"KmDgcV1fiQHJFiNzzSKxxYTw6vDM8Um2tt8qLI8ZPzt+Bk9Boa\\/FFlLcVaezLwfrqD3kTseZUDTRnu6h3VguLg==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:29:55'),(65,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"TcMQZ7tmWUK5XPm5OxrUGp9N\\/\\/EQsgksenQau4kAJzsicijNwKo+eybpuxqfkU10znrf\\/QUN5O3HYY\\/ZTzCBFA==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:29:59'),(66,'wxid_ud1cgcq0sk8412','verify','172.71.215.60','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"7XeMVjegG1GXR7dUN\\/z2x8XiY4SPRL3z3i2Jh\\/10wNkNBTLATLTgJKVkAobSmq\\/iEkRfWqKEZwPcf+aEOL7gkQ==\"}','{\"authorized\":false,\"message\":\"\\u672a\\u627e\\u5230\\u6388\\u6743\"}','2025-07-05 01:30:27'),(67,'wxid_ud1cgcq0sk8412','redeem_success','172.68.211.155','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"card_code\":\"Apibug_CE238AA6BAEECEAD\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"success\":true,\"duration_days\":30,\"expire_timestamp\":1754271140}','2025-07-05 01:32:20'),(68,'wxid_ud1cgcq0sk8412','verify','172.71.218.251','WeChat/8.0.61.34 CFNetwork/1331.0.7 Darwin/21.4.0','{\"data\":\"IApsQBsHD0N\\/NKkN4s\\/8ctnkc0lZKDmQ\\/sS2bUbUf5OEi\\/1nIu26wns2CH3uM+5JTENnGgjS9V24yhZb2nernQ==\"}','{\"authorized\":true}','2025-07-05 01:32:54'),(69,'wxid_ud1cgcq0sk8412','query_auth','108.162.227.63','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','{\"action\":\"query_auth\",\"wxid\":\"wxid_ud1cgcq0sk8412\"}','{\"success\":true,\"authorized\":true,\"expire_date\":\"2025-08-04 09:32:20\",\"expired\":false,\"message\":\"\\u6388\\u6743\\u6709\\u6548\"}','2025-07-05 01:33:15');
/*!40000 ALTER TABLE `auth_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authorizations`
--

DROP TABLE IF EXISTS `authorizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authorizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wxid` varchar(255) NOT NULL COMMENT '微信ID',
  `expire_timestamp` bigint(20) NOT NULL COMMENT '过期时间戳',
  `card_key` varchar(255) NOT NULL COMMENT '使用的卡密',
  `auth_info` text COMMENT '授权信息',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wxid` (`wxid`),
  KEY `idx_wxid` (`wxid`),
  KEY `idx_expire` (`expire_timestamp`),
  KEY `idx_card_key` (`card_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='授权表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authorizations`
--

LOCK TABLES `authorizations` WRITE;
/*!40000 ALTER TABLE `authorizations` DISABLE KEYS */;
INSERT INTO `authorizations` VALUES (2,'wxid_ud1cgcq0sk8412',1754271140,'Apibug_CE238AA6BAEECEAD','卡密兑换成功，有效期：30天','2025-07-05 01:32:20','2025-07-05 01:32:20');
/*!40000 ALTER TABLE `authorizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_keys`
--

DROP TABLE IF EXISTS `card_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_key` varchar(255) NOT NULL COMMENT '卡密',
  `duration_days` int(11) NOT NULL DEFAULT '30' COMMENT '有效天数',
  `is_used` tinyint(1) DEFAULT '0' COMMENT '是否已使用',
  `used_by` varchar(255) DEFAULT NULL COMMENT '使用者WXID',
  `used_at` timestamp NULL DEFAULT NULL COMMENT '使用时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_key` (`card_key`),
  KEY `idx_card_key` (`card_key`),
  KEY `idx_used` (`is_used`),
  KEY `idx_used_by` (`used_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_keys`
--

LOCK TABLES `card_keys` WRITE;
/*!40000 ALTER TABLE `card_keys` DISABLE KEYS */;
INSERT INTO `card_keys` VALUES (10,'Apibug_CE238AA6BAEECEAD',30,1,'wxid_ud1cgcq0sk8412','2025-07-05 01:32:20','2025-07-05 01:31:42');
/*!40000 ALTER TABLE `card_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'wechatfaka'
--

--
-- Dumping routines for database 'wechatfaka'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-05  9:50:33
