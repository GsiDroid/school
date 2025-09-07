CREATE TABLE `fee_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `fee_structure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `category_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_category` (`class_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structure_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
