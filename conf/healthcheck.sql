DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enabled` TINYINT(4) NOT NULL DEFAULT '1',
  `node` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `node_2` (`node`),
  KEY `node` (`node`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `status`
--

LOCK TABLES `status` WRITE;
/*!40000 ALTER TABLE `status` DISABLE KEYS */;
INSERT INTO `status` VALUES (1,'1','web-1'),(2,'0','web-2');

