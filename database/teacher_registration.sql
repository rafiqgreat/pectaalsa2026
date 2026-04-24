-- Teacher / E-Marker Registration (8-step wizard) schema
-- Generated: 2026-04-24

-- Notes:
-- - If your existing `users` table already has some columns, skip the ALTERs that already exist.
-- - Passwords: this project historically used SHA-256; the wizard uses `password_hash()` and the login logic should be updated to support password_verify().
-- - New registrations are created with `users.status = 0` (pending admin approval).

-- Users table additions (run only if columns are missing)
-- ALTER TABLE `users` ADD COLUMN `father_name` VARCHAR(150) DEFAULT NULL AFTER `name`;
-- ALTER TABLE `users` ADD COLUMN `blood_group` VARCHAR(10) DEFAULT NULL AFTER `phone`;
-- ALTER TABLE `users` ADD COLUMN `gender` ENUM('Male','Female','Other') DEFAULT NULL AFTER `blood_group`;
-- ALTER TABLE `users` ADD COLUMN `dob` DATE DEFAULT NULL AFTER `gender`;
-- ALTER TABLE `users` ADD COLUMN `cnic` VARCHAR(20) DEFAULT NULL AFTER `dob`;
-- ALTER TABLE `users` ADD COLUMN `employee_no` VARCHAR(100) DEFAULT NULL AFTER `cnic`;
-- ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL AFTER `employee_no`;

CREATE TABLE IF NOT EXISTS `teacher_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `address` TEXT NOT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `province` VARCHAR(100) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Pakistan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_address_user` (`user_id`),
  KEY `idx_address_district_city` (`district`, `city`),
  CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_educations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `degree` VARCHAR(150) NOT NULL,
  `institute` VARCHAR(255) NOT NULL,
  `passing_year` YEAR NOT NULL,
  `cgpa_percentage` VARCHAR(30) NOT NULL,
  `transcript_file` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_edu_user` (`user_id`),
  KEY `idx_edu_degree_year` (`degree`, `passing_year`),
  CONSTRAINT `fk_edu_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_experiences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `department` VARCHAR(150) NOT NULL,
  `sector` ENUM('Government','Private','Semi Government','Other') NOT NULL,
  `experience_type` VARCHAR(150) NOT NULL,
  `job_type` VARCHAR(100) NOT NULL,
  `teaching_level` VARCHAR(100) DEFAULT NULL,
  `bps` VARCHAR(20) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `currently_working` TINYINT(1) NOT NULL DEFAULT 0,
  `document_file` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_exp_user` (`user_id`),
  KEY `idx_exp_department` (`department`),
  KEY `idx_exp_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_exp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_bank_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `bank_name` VARCHAR(150) NOT NULL,
  `branch_name` VARCHAR(150) NOT NULL,
  `branch_code` VARCHAR(50) NOT NULL,
  `account_title` VARCHAR(150) NOT NULL,
  `iban_account_no` VARCHAR(50) NOT NULL,
  `international_user` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_bank_user` (`user_id`),
  KEY `idx_bank_name` (`bank_name`),
  KEY `idx_iban` (`iban_account_no`),
  CONSTRAINT `fk_bank_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_specializations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `specialization` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_specialization_user` (`user_id`),
  KEY `idx_specialization` (`specialization`),
  CONSTRAINT `fk_specialization_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_security_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `document_type` VARCHAR(100) NOT NULL,
  `identification_number` VARCHAR(100) NOT NULL,
  `expiry_date` DATE NOT NULL,
  `document_file` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_security_user` (`user_id`),
  KEY `idx_security_doc_no` (`identification_number`),
  KEY `idx_security_expiry` (`expiry_date`),
  CONSTRAINT `fk_security_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_emarking_experiences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `department` VARCHAR(150) NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_emarking_user` (`user_id`),
  KEY `idx_emarking_department` (`department`),
  KEY `idx_emarking_dates` (`from_date`, `to_date`),
  CONSTRAINT `fk_emarking_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teacher_registration_steps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `personal_completed` TINYINT(1) DEFAULT 0,
  `address_completed` TINYINT(1) DEFAULT 0,
  `education_completed` TINYINT(1) DEFAULT 0,
  `experience_completed` TINYINT(1) DEFAULT 0,
  `bank_completed` TINYINT(1) DEFAULT 0,
  `specialization_completed` TINYINT(1) DEFAULT 0,
  `security_completed` TINYINT(1) DEFAULT 0,
  `emarking_completed` TINYINT(1) DEFAULT 0,
  `no_experience` TINYINT(1) NOT NULL DEFAULT 0,
  `no_emarking_experience` TINYINT(1) NOT NULL DEFAULT 0,
  `current_step` TINYINT NOT NULL DEFAULT 1,
  `registration_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_steps_user` (`user_id`),
  KEY `idx_registration_completed` (`registration_completed`),
  KEY `idx_current_step` (`current_step`),
  CONSTRAINT `fk_steps_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
