DROP TABLE IF EXISTS `person`;

CREATE TABLE `person` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `access_token` varchar(40) DEFAULT NULL,
  `twitter_id` bigint(20) NOT NULL,
  `twitter_access_token` text,
  `twitter_access_token_secret` text,
  `twitter_name` varchar(30) NOT NULL,
  `poster` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_twitter_id` (`twitter_id`),
  UNIQUE KEY `unique_access_token` (`access_token`),
  KEY `poster` (`poster`),
  CONSTRAINT `person_ibfk_1` FOREIGN KEY (`poster`) REFERENCES `person` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
