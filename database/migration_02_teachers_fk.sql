ALTER TABLE `users` ADD COLUMN `teacher_id` INT NULL DEFAULT NULL, ADD CONSTRAINT `fk_user_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL;
