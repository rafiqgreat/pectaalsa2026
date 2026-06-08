<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_emarker_payment_summary_index extends CI_Migration
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
		// Helps eMarker payment summary scan date-filtered marks and roll up by question/emarker/status.
		$this->add_index_if_missing(
			'emarking_marks',
			'idx_emarker_payment_summary',
			'`marked_at`, `question_id`, `emarker_id`, `marking_status`'
		);
	}

	public function down()
	{
		// Non-destructive: keep performance indexes once created.
	}
}
