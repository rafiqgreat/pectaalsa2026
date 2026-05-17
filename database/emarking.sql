-- e-Marking module tables (MySQL)
-- Generated for CodeIgniter 3 project integration

CREATE TABLE `emarking_questions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `assessment_type` ENUM('CRQ','DICTATION') NOT NULL DEFAULT 'CRQ',
  `source_table` VARCHAR(100) DEFAULT NULL,
  `grade` INT NOT NULL,
  `subject_code` VARCHAR(10) NOT NULL,
  `version` INT NOT NULL DEFAULT 1,
  `page_no` VARCHAR(10) NOT NULL,
  `question_no` VARCHAR(20) NOT NULL,
  `question_title` TEXT NOT NULL,
  `question_type` ENUM('OBJECTIVE_STEPS','WRITING','PARAGRAPH','LIST','DICTATION','OTHER') DEFAULT 'OBJECTIVE_STEPS',
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `rubric_title` VARCHAR(255) DEFAULT NULL,
  `rubric_detail` TEXT DEFAULT NULL,
  `sample_answer` LONGTEXT DEFAULT NULL,
  `sample_answer_file` VARCHAR(500) DEFAULT NULL,
  `guide_text` LONGTEXT DEFAULT NULL,
  `guide_file` VARCHAR(500) DEFAULT NULL,
  `question_paper_file` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_question` (`assessment_type`,`grade`,`subject_code`,`version`,`page_no`,`question_no`),
  KEY `idx_filter` (`assessment_type`,`grade`,`subject_code`,`version`,`page_no`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_question_rubric_steps` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `step_order` INT NOT NULL,
  `step_label` VARCHAR(100) DEFAULT NULL,
  `step_title` TEXT NOT NULL,
  `step_detail` TEXT DEFAULT NULL,
  `step_marks` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `marking_type` ENUM('ZERO_ONE','RANGE','FIXED') DEFAULT 'ZERO_ONE',
  `min_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_rubric_question`
    FOREIGN KEY (`question_id`) REFERENCES `emarking_questions`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_question_images` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `assessment_type` ENUM('CRQ','DICTATION') NOT NULL DEFAULT 'CRQ',
  `source_table` VARCHAR(100) NOT NULL,
  `source_paper_id` INT NOT NULL,
  `paper_barcode` VARCHAR(20) NOT NULL,
  `grade` INT NOT NULL,
  `school_id` INT DEFAULT NULL,
  `lsacode` VARCHAR(100) DEFAULT NULL,
  `subject_code` VARCHAR(10) NOT NULL,
  `version` INT NOT NULL,
  `roll_no` VARCHAR(20) NOT NULL,
  `page_no` VARCHAR(10) NOT NULL,
  `question_id` INT NOT NULL,
  `question_no` VARCHAR(20) NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `upload_batch_no` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('UPLOADED','ASSIGNED','MARKED','SKIPPED','NOT_ATTEMPTED','RECHECK','FINALIZED') DEFAULT 'UPLOADED',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_question_image` (`assessment_type`,`paper_barcode`,`question_id`),
  KEY `idx_barcode` (`paper_barcode`),
  KEY `idx_question` (`question_id`),
  KEY `idx_status` (`status`),
  KEY `idx_source` (`source_table`,`source_paper_id`),
  CONSTRAINT `fk_eqi_question`
    FOREIGN KEY (`question_id`) REFERENCES `emarking_questions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_batches` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `batch_code` VARCHAR(100) NOT NULL,
  `assessment_type` ENUM('CRQ','DICTATION') NOT NULL DEFAULT 'CRQ',
  `grade` INT NOT NULL,
  `subject_code` VARCHAR(10) NOT NULL,
  `version` INT DEFAULT NULL,
  `question_id` INT NOT NULL,
  `batch_size` INT NOT NULL DEFAULT 100,
  `assigned_to` INT NOT NULL,
  `assigned_by` INT DEFAULT NULL,
  `deadline` DATETIME DEFAULT NULL,
  `status` ENUM('PENDING','IN_PROGRESS','COMPLETED','FINALIZED') DEFAULT 'PENDING',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch_code` (`batch_code`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_question_status` (`question_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_batch_items` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `batch_id` BIGINT NOT NULL,
  `question_image_id` BIGINT NOT NULL,
  `status` ENUM('PENDING','MARKED','SKIPPED','NOT_ATTEMPTED','RECHECK','FINALIZED') DEFAULT 'PENDING',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch_image` (`question_image_id`),
  KEY `idx_batch_status` (`batch_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_marks` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `batch_item_id` BIGINT NOT NULL,
  `question_image_id` BIGINT NOT NULL,
  `question_id` INT NOT NULL,
  `emarker_id` INT NOT NULL,
  `marks_obtained` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `marking_status` ENUM('MARKED','SKIPPED','NOT_ATTEMPTED') DEFAULT 'MARKED',
  `remarks` TEXT DEFAULT NULL,
  `marked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_final` TINYINT(1) DEFAULT 0,
  `finalized_by` INT DEFAULT NULL,
  `finalized_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_question_image_marker` (`question_image_id`,`emarker_id`),
  KEY `idx_emarker` (`emarker_id`),
  KEY `idx_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_marks_steps` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `mark_id` BIGINT NOT NULL,
  `rubric_step_id` INT NOT NULL,
  `selected_value` VARCHAR(50) DEFAULT NULL,
  `marks_awarded` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mark_step` (`mark_id`,`rubric_step_id`),
  KEY `idx_mark_id` (`mark_id`),
  KEY `idx_step_id` (`rubric_step_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emarking_rates` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `assessment_type` ENUM('CRQ','DICTATION') NOT NULL DEFAULT 'CRQ',
  `grade` INT NOT NULL,
  `subject_code` VARCHAR(10) NOT NULL,
  `question_id` INT DEFAULT NULL,
  `rate_type` ENUM('PER_QUESTION','PER_MARK') DEFAULT 'PER_QUESTION',
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rate_filter` (`assessment_type`,`grade`,`subject_code`,`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

