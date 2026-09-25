-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: inventario_pecas
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.22.04.4

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
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Fonte de Alimentação','Fontes ATX para computadores desktop','2025-12-12 19:16:49'),(2,'Placa Mãe','Placas mãe para diversos sockets (Intel e AMD)','2025-12-12 19:16:49'),(3,'Memória RAM','Memórias DDR3, DDR4, DDR5 para desktop e notebook','2025-12-12 19:16:49'),(4,'Processador','CPUs Intel Core i3/i5/i7/i9 e AMD Ryzen','2025-12-12 19:16:49'),(5,'Armazenamento','HDDs, SSDs SATA, NVMe M.2','2025-12-12 19:16:49'),(6,'Placa de Vídeo','GPUs NVIDIA e AMD para games e trabalho','2025-12-12 19:16:49'),(7,'Gabinete','Gabinetes ATX, Micro-ATX, Mini-ITX com ou sem fonte','2025-12-12 19:16:49'),(8,'Cooler/ Ventoinha','Coolers para processador e ventoinhas para gabinete','2025-12-12 19:16:49'),(9,'Placa de Rede','Placas de rede cabeada e wireless','2025-12-12 19:16:49'),(10,'Monitor','Monitores LCD, LED de diversos tamanhos','2025-12-12 19:16:49'),(11,'Teclado/Mouse','Periféricos de entrada USB ou wireless','2025-12-12 19:16:49'),(12,'Outros','Outros componentes e periféricos diversos','2025-12-12 19:16:49');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `controle_uso`
--

DROP TABLE IF EXISTS `controle_uso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `controle_uso` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peca_id` int DEFAULT NULL,
  `quantidade` int DEFAULT NULL,
  `tecnico` varchar(100) DEFAULT NULL,
  `numero_chamado` varchar(50) DEFAULT NULL,
  `data_uso` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text,
  PRIMARY KEY (`id`),
  KEY `peca_id` (`peca_id`),
  CONSTRAINT `controle_uso_ibfk_1` FOREIGN KEY (`peca_id`) REFERENCES `pecas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `controle_uso`
--

LOCK TABLES `controle_uso` WRITE;
/*!40000 ALTER TABLE `controle_uso` DISABLE KEYS */;
/*!40000 ALTER TABLE `controle_uso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico`
--

DROP TABLE IF EXISTS `historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peca_id` int NOT NULL,
  `tipo_movimentacao` enum('entrada','saida','transferencia','ajuste','uso','descarte') NOT NULL,
  `quantidade` int NOT NULL,
  `quantidade_anterior` int NOT NULL,
  `quantidade_nova` int NOT NULL,
  `responsavel` varchar(100) NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `numero_chamado` varchar(50) DEFAULT NULL,
  `observacoes` text,
  `data_movimentacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_peca_id` (`peca_id`),
  KEY `idx_data` (`data_movimentacao`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historico_ibfk_1` FOREIGN KEY (`peca_id`) REFERENCES `pecas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico`
--

LOCK TABLES `historico` WRITE;
/*!40000 ALTER TABLE `historico` DISABLE KEYS */;
INSERT INTO `historico` VALUES (37,302,'entrada',50,0,50,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-10 20:02:56'),(42,306,'entrada',50,0,50,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 18:13:32'),(43,306,'saida',10,50,40,'sergio',NULL,'manuenção','121222','Peça retirada para manutenção de computadores em laboratório.','2026-09-24 19:01:48'),(44,307,'entrada',3,0,3,'sergio',NULL,'Cadastro no inventário','18383838','Cadastro inicial da peça no sistema','2026-09-24 19:02:52'),(45,308,'entrada',3,0,3,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 19:45:56'),(46,309,'entrada',1,0,1,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 19:46:23'),(47,310,'entrada',6,0,6,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 19:46:50'),(48,311,'entrada',1,0,1,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 19:47:13'),(49,312,'entrada',1,0,1,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 19:47:51'),(50,313,'entrada',4,0,4,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 19:48:26'),(53,315,'entrada',1,0,1,'sergio',NULL,'Cadastro no inventário','','Cadastro inicial da peça no sistema','2026-09-24 20:02:26'),(54,315,'saida',1,1,0,'sergio',NULL,'manutenção','232323','','2026-09-24 20:04:17');
/*!40000 ALTER TABLE `historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pecas`
--

DROP TABLE IF EXISTS `pecas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pecas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) NOT NULL,
  `descricao` text,
  `categoria_id` int DEFAULT NULL,
  `quantidade` int NOT NULL DEFAULT '0',
  `estado` enum('novo','usado','reparado','danificado') NOT NULL,
  `localizacao` varchar(100) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `data_aquisicao` date DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text,
  `usuario_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `pecas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pecas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=317 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pecas`
--

LOCK TABLES `pecas` WRITE;
/*!40000 ALTER TABLE `pecas` DISABLE KEYS */;
INSERT INTO `pecas` VALUES (302,'Fonte AT 500W','',1,50,'novo','Armario A','','2026-09-10','2026-09-10 20:02:56','',NULL),(306,'Memoria 4G DDR4','',3,40,'novo','Armario C','22222','2026-09-24','2026-09-24 18:13:32','',NULL),(307,'Placa de Vídeo GTX 1050 Ti','Placa de video reaproveitada.',6,3,'usado','Armario A','554545454','2026-09-24','2026-09-24 19:02:52','Em bom estado',NULL),(308,'Processador AMD Ryzen 5 3600','',4,3,'usado','Armario C','2122222','2026-09-24','2026-09-24 19:45:56','',NULL),(309,'Processador Intel Core i5-10400F','',4,1,'usado','Armario C','3232322','2026-09-24','2026-09-24 19:46:23','',NULL),(310,'Placa de Vídeo NVIDIA GeForce GTX 1660 Super','',6,6,'reparado','Armário C','1221121','2026-09-24','2026-09-24 19:46:50','',NULL),(311,'Placa de Vídeo AMD Radeon RX 580 (8GB) (','',6,1,'usado','Armario C','','2026-09-24','2026-09-24 19:47:13','',NULL),(312,'HD Interno Western Digital WD Blue 1TB (','',5,1,'usado','Armario C','3333222','2026-09-24','2026-09-24 19:47:51','',NULL),(313,'Memória RAM HyperX / Kingston FURY Beast DDR4 16GB (2x8GB) 3200MHz','',3,4,'novo','Armario C','2323232','2026-09-24','2026-09-24 19:48:26','',NULL),(315,'Memoria RAM 8G DDR4','Memória reutilizada.',3,0,'usado','Armário C','23232323','2026-09-24','2026-09-24 20:02:26','',NULL);
/*!40000 ALTER TABLE `pecas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('tecnico','supervisor','admin') DEFAULT 'tecnico',
  `ativo` tinyint(1) DEFAULT '1',
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (11,'sergio','sergio@sergio.com','$2y$10$/Lszh0umRob91cWVeJiXhu6f0TAoR4ZLmfGTlH6CbjrPtovij1Viy','admin',1,'2026-09-10 19:57:43',NULL),(15,'teste','teste@teste.com','$2y$10$hlVzm3j3avADFqyJlFDY5eA3JIZn0SWLQ64jkte33WPhbAgF.XYyy','tecnico',1,'2026-09-24 18:15:05',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-25 15:19:43
