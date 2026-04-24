<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Emarker_review extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('teacher_registration_steps')) {
			return;
		}

		$fields = $this->db->field_data('teacher_registration_steps');
		$existing = [];
		foreach ($fields as $f) {
			$existing[$f->name] = true;
		}

		if (!isset($existing['review_status'])) {
			// ENUM is not supported by dbforge reliably; use raw SQL.
			$this->db->query("ALTER TABLE `teacher_registration_steps` ADD COLUMN `review_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `registration_completed`");
		}
		if (!isset($existing['rejection_reason'])) {
			$this->db->query("ALTER TABLE `teacher_registration_steps` ADD COLUMN `rejection_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `review_status`");
		}
		if (!isset($existing['review_notes'])) {
			$this->db->query("ALTER TABLE `teacher_registration_steps` ADD COLUMN `review_notes` TEXT NULL AFTER `rejection_reason`");
		}
		if (!isset($existing['rejected_at'])) {
			$this->db->query("ALTER TABLE `teacher_registration_steps` ADD COLUMN `rejected_at` DATETIME NULL DEFAULT NULL AFTER `review_notes`");
		}
		if (!isset($existing['approved_at'])) {
			$this->db->query("ALTER TABLE `teacher_registration_steps` ADD COLUMN `approved_at` DATETIME NULL DEFAULT NULL AFTER `rejected_at`");
		}
		if (!isset($existing['reviewed_by'])) {
			$this->db->query("ALTER TABLE `teacher_registration_steps` ADD COLUMN `reviewed_by` INT NULL DEFAULT NULL AFTER `approved_at`");
		}

		// Best-effort backfill for existing active accounts.
		if (isset($existing['review_status']) || $this->db->field_exists('review_status', 'teacher_registration_steps')) {
			$this->db->query("
				UPDATE `teacher_registration_steps` s
				INNER JOIN `users` u ON u.id = s.user_id
				SET s.review_status = 'approved',
					s.approved_at = COALESCE(s.approved_at, NOW())
				WHERE u.status = 1
				  AND s.registration_completed = 1
				  AND (s.review_status = 'pending' OR s.review_status IS NULL)
			");
		}
	}

	public function down()
	{
		// Non-destructive: keep columns.
	}
}

