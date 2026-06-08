<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_rechecking_summary_indexes extends CI_Migration
{
	private function index_exists($table, $index_name)
	{
		if (!$this->db->table_exists($table)) {
			return false;
		}

		$query = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($index_name));
		return $query && $query->num_rows() > 0;
	}

	private function add_index_if_missing($table, $index_name, $columns_sql)
	{
		if (!$this->db->table_exists($table) || $this->index_exists($table, $index_name)) {
			return;
		}

		$this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ({$columns_sql})");
	}

	public function up()
	{
		// Helps the rechecking summary scan MARKED rows and join/filter by date, question and emarker.
		$this->add_index_if_missing(
			'emarking_marks',
			'idx_rechecking_summary_scan',
			'`marking_status`, `marked_at`, `question_id`, `emarker_id`'
		);

		// Helps when the optimizer starts from filtered question metadata before joining marks.
		$this->add_index_if_missing(
			'emarking_questions',
			'idx_rechecking_summary_filters',
			'`assessment_type`, `grade`, `subject_code`, `id`'
		);
	}

	public function down()
	{
		// Non-destructive: keep performance indexes once created.
	}
}
