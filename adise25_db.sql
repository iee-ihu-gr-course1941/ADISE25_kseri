-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: adise25_db
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
-- Table structure for table `board`
--

DROP TABLE IF EXISTS `board`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `board` (
  `game_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `location` enum('deck','hand','table','discard') NOT NULL,
  `owner` varchar(20) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`game_id`,`card_id`),
  KEY `card_id` (`card_id`),
  KEY `owner` (`owner`),
  CONSTRAINT `board_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`),
  CONSTRAINT `board_ibfk_2` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`),
  CONSTRAINT `board_ibfk_3` FOREIGN KEY (`owner`) REFERENCES `players` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board`
--

LOCK TABLES `board` WRITE;
/*!40000 ALTER TABLE `board` DISABLE KEYS */;
INSERT INTO `board` VALUES (4,1,'deck',NULL,28),(4,2,'table',NULL,NULL),(4,3,'deck',NULL,24),(4,4,'deck',NULL,19),(4,5,'hand','test2',NULL),(4,6,'deck',NULL,52),(4,7,'deck',NULL,23),(4,8,'deck',NULL,46),(4,9,'hand','test2',NULL),(4,10,'hand','test2',NULL),(4,11,'table',NULL,NULL),(4,12,'deck',NULL,44),(4,13,'table',NULL,NULL),(4,14,'deck',NULL,41),(4,15,'hand','test2',NULL),(4,16,'deck',NULL,26),(4,17,'hand','test',NULL),(4,18,'deck',NULL,27),(4,19,'deck',NULL,37),(4,20,'hand','test',NULL),(4,21,'hand','test',NULL),(4,22,'table',NULL,NULL),(4,23,'deck',NULL,48),(4,24,'deck',NULL,33),(4,25,'deck',NULL,35),(4,26,'deck',NULL,31),(4,27,'hand','test',NULL),(4,28,'hand','test2',NULL),(4,29,'deck',NULL,49),(4,30,'deck',NULL,43),(4,31,'deck',NULL,20),(4,32,'hand','test2',NULL),(4,33,'deck',NULL,29),(4,34,'deck',NULL,30),(4,35,'deck',NULL,22),(4,36,'deck',NULL,36),(4,37,'deck',NULL,42),(4,38,'deck',NULL,32),(4,39,'deck',NULL,51),(4,40,'deck',NULL,38),(4,41,'deck',NULL,50),(4,42,'deck',NULL,21),(4,43,'deck',NULL,39),(4,44,'deck',NULL,47),(4,45,'hand','test',NULL),(4,46,'deck',NULL,45),(4,47,'deck',NULL,34),(4,48,'deck',NULL,18),(4,49,'hand','test',NULL),(4,50,'deck',NULL,25),(4,51,'deck',NULL,17),(4,52,'deck',NULL,40);
/*!40000 ALTER TABLE `board` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suit` enum('hearts','diamonds','clubs','spades') NOT NULL,
  `rank` enum('2','3','4','5','6','7','8','9','10','J','Q','K','A') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_card` (`suit`,`rank`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
INSERT INTO `cards` VALUES (1,'hearts','2'),(2,'hearts','3'),(3,'hearts','4'),(4,'hearts','5'),(5,'hearts','6'),(6,'hearts','7'),(7,'hearts','8'),(8,'hearts','9'),(9,'hearts','10'),(10,'hearts','J'),(11,'hearts','Q'),(12,'hearts','K'),(13,'hearts','A'),(14,'diamonds','2'),(15,'diamonds','3'),(16,'diamonds','4'),(17,'diamonds','5'),(18,'diamonds','6'),(19,'diamonds','7'),(20,'diamonds','8'),(21,'diamonds','9'),(22,'diamonds','10'),(23,'diamonds','J'),(24,'diamonds','Q'),(25,'diamonds','K'),(26,'diamonds','A'),(27,'clubs','2'),(28,'clubs','3'),(29,'clubs','4'),(30,'clubs','5'),(31,'clubs','6'),(32,'clubs','7'),(33,'clubs','8'),(34,'clubs','9'),(35,'clubs','10'),(36,'clubs','J'),(37,'clubs','Q'),(38,'clubs','K'),(39,'clubs','A'),(40,'spades','2'),(41,'spades','3'),(42,'spades','4'),(43,'spades','5'),(44,'spades','6'),(45,'spades','7'),(46,'spades','8'),(47,'spades','9'),(48,'spades','10'),(49,'spades','J'),(50,'spades','Q'),(51,'spades','K'),(52,'spades','A');
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game`
--

DROP TABLE IF EXISTS `game`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('initialized','started','ended','aborted') NOT NULL DEFAULT 'initialized',
  `current_player_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `last_change` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `current_player_id` (`current_player_id`),
  KEY `winner_id` (`winner_id`),
  CONSTRAINT `game_ibfk_1` FOREIGN KEY (`current_player_id`) REFERENCES `players` (`id`),
  CONSTRAINT `game_ibfk_2` FOREIGN KEY (`winner_id`) REFERENCES `players` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game`
--

LOCK TABLES `game` WRITE;
/*!40000 ALTER TABLE `game` DISABLE KEYS */;
INSERT INTO `game` VALUES (4,'started',3,NULL,'2025-12-21 21:44:06');
/*!40000 ALTER TABLE `game` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL,
  `last_action` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `game_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `token` (`token`),
  KEY `game_id` (`game_id`),
  CONSTRAINT `players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
INSERT INTO `players` VALUES (3,'test',0,'f22e8136b6e6aea40550c0325852a389','2025-12-21 21:40:55',4),(4,'test2',0,'1d00c213ced8425c97fdac9ac7d8486e','2025-12-21 21:41:01',4);
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-22 13:13:48
