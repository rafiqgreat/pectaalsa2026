<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_emarking_batch_transfers extends CI_Migration
{
	public function up()
	{
		if ($this->db->table_exists('emarking_batch_transfers')) {
			return;
		}

		// Keep this migration simple and portable by creating the audit table directly.
		$this->db->query("
			CREATE TABLE `emarking_batch_transfers` (
				`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`batch_id` INT(10) UNSIGNED NOT NULL,
				`old_emarker_id` INT(10) UNSIGNED NOT NULL,
				`new_emarker_id` INT(10) UNSIGNED NOT NULL,
				`transferred_by` INT(10) UNSIGNED NOT NULL,
				`old_status` VARCHAR(50) NULL DEFAULT NULL,
				`remarks` TEXT NULL,
				`created_at` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				KEY `idx_ebt_batch_id` (`batch_id`),
				KEY `idx_ebt_old_emarker_id` (`old_emarker_id`),
				KEY `idx_ebt_new_emarker_id` (`new_emarker_id`),
				KEY `idx_ebt_transferred_by` (`transferred_by`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");
	}

	public function down()
	{
		// Non-destructive: keep transfer audit history once created.
	}
}
