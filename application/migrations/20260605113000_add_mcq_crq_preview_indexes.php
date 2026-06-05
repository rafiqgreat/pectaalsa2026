<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_mcq_crq_preview_indexes extends CI_Migration
{
	private function add_index_if_missing($table, $index_name, $columns_sql)
	{
		$exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($index_name))->row_array();
		if (!empty($exists)) {
			return;
		}

		$this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ({$columns_sql})");
	}

	public function up()
	{
		if ($this->db->table_exists('emarking_question_images')) {
			$this->add_index_if_missing(
				'emarking_question_images',
				'idx_crq_mcq_preview_barcode',
				'`assessment_type`, `grade`, `subject_code`, `version`, `paper_barcode`'
			);
		}
	}

	public function down()
	{
		// Non-destructive: keep performance indexes once added.
	}
}
