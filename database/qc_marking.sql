-- QC / Subject Specialist Quality Check tables
-- Run this once on your database (MySQL/MariaDB) to enable SS QC marking.

CREATE TABLE IF NOT EXISTS `emarking_qc_batches` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `batch_code` VARCHAR(100) NOT NULL,
  `assessment_type` ENUM('CRQ','DICTATION') NOT NULL DEFAULT 'CRQ',
  `grade` INT NOT NULL,
  `subject_code` VARCHAR(10) NOT NULL,
  `version` INT DEFAULT NULL,
  `per_question` INT NOT NULL DEFAULT 10,
  `total_items` INT NOT NULL DEFAULT 0,
  `assigned_to` INT NOT NULL,
  `assigned_by` INT DEFAULT NULL,
  `status` ENUM('PENDING','IN_PROGRESS','COMPLETED') DEFAULT 'PENDING',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_batch_code` (`batch_code`),
  KEY `idx_qc_assigned_to` (`assigned_to`),
  KEY `idx_qc_filter` (`assessment_type`,`grade`,`subject_code`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `emarking_qc_batch_items` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `batch_id` BIGINT NOT NULL,
  `question_image_id` BIGINT NOT NULL,
  `question_id` INT NOT NULL,
  `status` ENUM('PENDING','MARKED','SKIPPED','NOT_ATTEMPTED','RECHECK','FINALIZED') DEFAULT 'PENDING',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_batch_image` (`batch_id`,`question_image_id`),
  KEY `idx_qc_batch_status` (`batch_id`,`status`),
  KEY `idx_qc_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `emarking_qc_marks` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `batch_item_id` BIGINT NOT NULL,
  `question_image_id` BIGINT NOT NULL,
  `question_id` INT NOT NULL,
  `ss_id` INT NOT NULL,
  `marks_obtained` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `marking_status` ENUM('MARKED','SKIPPED','NOT_ATTEMPTED','RECHECK') DEFAULT 'MARKED',
  `remarks` TEXT DEFAULT NULL,
  `marked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_image_ss` (`question_image_id`,`ss_id`),
  KEY `idx_qc_ss` (`ss_id`),
  KEY `idx_qc_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `emarking_qc_marks_steps` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `mark_id` BIGINT NOT NULL,
  `rubric_step_id` INT NOT NULL,
  `selected_value` VARCHAR(50) DEFAULT NULL,
  `marks_awarded` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_mark_step` (`mark_id`,`rubric_step_id`),
  KEY `idx_qc_mark_id` (`mark_id`),
  KEY `idx_qc_step_id` (`rubric_step_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

