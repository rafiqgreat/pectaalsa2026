<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_emarking_rechecking_summary extends CI_Migration
{
	public function up()
	{
		if ($this->db->table_exists('emarking_rechecking_summary')) {
			return;
		}

		$this->db->query("
			CREATE TABLE `emarking_rechecking_summary` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`emarker_id` int(11) NOT NULL,
				`subject_id` int(11) NOT NULL,
				`percentage` decimal(5,2) NOT NULL,
				`rechecked_count` int(11) NOT NULL,
				`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
				`updated_at` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uniq_emarker_subject` (`emarker_id`, `subject_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");
	}

	public function down()
	{
		if ($this->db->table_exists('emarking_rechecking_summary')) {
			$this->dbforge->drop_table('emarking_rechecking_summary', true);
		}
	}
}
