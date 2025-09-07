ALTER TABLE `users` CHANGE `role` `role` ENUM('Admin','Teacher','Cashier','Student','Parent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL;

ALTER TABLE `students` ADD `parent_id` INT NULL DEFAULT NULL AFTER `user_id`, ADD INDEX (`parent_id`);

ALTER TABLE `students` ADD CONSTRAINT `fk_student_parent` FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
