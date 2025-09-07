-- Adminer 4.8.1 MySQL 8.0.36 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `activity` varchar(255) NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL,
  `marked_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_class_date` (`student_id`,`class_id`,`attendance_date`),
  KEY `class_id` (`class_id`),
  KEY `marked_by` (`marked_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_name_academic_year` (`class_name`,`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `classes` (`id`, `class_name`, `academic_year`) VALUES
(1,	'Grade 1',	'2025-2026'),
(2,	'Grade 2',	'2025-2026');

DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Online','Cheque','DD') NOT NULL,
  `payment_date` date NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `created_by` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `student_id` (`student_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admission_no` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `mobile_no` varchar(20) DEFAULT NULL,
  `parent_mobile` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `class_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admission_no` (`admission_no`),
  KEY `class_id` (`class_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `students` (`id`, `admission_no`, `first_name`, `last_name`, `father_name`, `mother_name`, `date_of_birth`, `gender`, `mobile_no`, `parent_mobile`, `photo`, `class_id`, `academic_year`, `is_active`, `user_id`) VALUES
(1,	'S001',	'John',	'Doe',	'Richard Doe',	'Jane Doe',	'2015-01-15',	'Male',	'1234567890',	'0987654321',	NULL,	1,	'2025-2026',	1,	4);

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Teacher','Cashier','Student') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `full_name`, `is_active`, `created_at`) VALUES
(1,	'admin',	'$2y$10$E.qLp3b7V6aC.1xY.b.d.e/U3f5j3.Z.cW/gY.h.i.j.k.l.m.n',	'Admin',	'admin@school.com',	'System Administrator',	1,	'2025-09-07 08:00:00'),
(2,	'teacher1',	'$2y$10$E.qLp3b7V6aC.1xY.b.d.e/U3f5j3.Z.cW/gY.h.i.j.k.l.m.n',	'Teacher',	'teacher@school.com',	'John Smith',	1,	'2025-09-07 08:00:00'),
(3,	'cashier1',	'$2y$10$E.qLp3b7V6aC.1xY.b.d.e/U3f5j3.Z.cW/gY.h.i.j.k.l.m.n',	'Cashier',	'cashier@school.com',	'Jane Doe',	1,	'2025-09-07 08:00:00'),
(4,	'student1',	'$2y$10$E.qLp3b7V6aC.1xY.b.d.e/U3f5j3.Z.cW/gY.h.i.j.k.l.m.n',	'Student',	'student@school.com',	'John Doe',	1,	'2025-09-07 08:00:00');

-- 2025-09-07 12:30:40
