-- MySQL dump 10.13  Distrib 5.7.17, for macos10.12 (x86_64)
--
-- Host: localhost    Database: common_test
-- ------------------------------------------------------
-- Server version	5.7.18

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `test_table_five`
--

DROP TABLE IF EXISTS `test_table_five`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_five` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `col_json` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_five`
--

LOCK TABLES `test_table_five` WRITE;
/*!40000 ALTER TABLE `test_table_five` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_table_five` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_table_four`
--

DROP TABLE IF EXISTS `test_table_four`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_four` (
  `id` int(11) unsigned NOT NULL,
  `table_one_fk` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index2` (`table_one_fk`),
  CONSTRAINT `fk_four_one` FOREIGN KEY (`table_one_fk`) REFERENCES `test_table_one` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='description qqq';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_four`
--

LOCK TABLES `test_table_four` WRITE;
/*!40000 ALTER TABLE `test_table_four` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_table_four` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_table_one`
--

DROP TABLE IF EXISTS `test_table_one`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_one` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'comment1',
  `col_enum` enum('val1','val2','val3') NOT NULL DEFAULT 'val1',
  `col_text` longtext NOT NULL,
  `col_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'comment2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='description blabla';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_one`
--

LOCK TABLES `test_table_one` WRITE;
/*!40000 ALTER TABLE `test_table_one` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_table_one` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_table_three`
--

DROP TABLE IF EXISTS `test_table_three`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_three` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_three`
--

LOCK TABLES `test_table_three` WRITE;
/*!40000 ALTER TABLE `test_table_three` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_table_three` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_table_two`
--

DROP TABLE IF EXISTS `test_table_two`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_table_two` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_one_fk` int(11) NOT NULL COMMENT 'blabla',
  `col_text` text,
  PRIMARY KEY (`id`),
  KEY `index2` (`table_one_fk`),
  CONSTRAINT `fk_two_one` FOREIGN KEY (`table_one_fk`) REFERENCES `test_table_one` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='description qweqwe';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_table_two`
--

LOCK TABLES `test_table_two` WRITE;
/*!40000 ALTER TABLE `test_table_two` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_table_two` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-07-04 12:55:50
