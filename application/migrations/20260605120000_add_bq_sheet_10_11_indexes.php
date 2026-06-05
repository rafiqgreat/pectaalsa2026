<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_bq_sheet_10_11_indexes extends CI_Migration
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
		$tables = [
			'sheet_10',
			'sheet_11',
		];

		foreach ($tables as $table) {
			if (!$this->db->table_exists($table)) {
				continue;
			}

			$this->add_index_if_missing($table, 'idx_bq_student_barcode', '`Student_Barcode`(14)');
			$this->add_index_if_missing($table, 'idx_bq_sheet_family', '`Sheet_Family`(8)');
			$this->add_index_if_missing($table, 'idx_bq_sheet_code', '`Sheet_Code`(8)');
		}
	}

	public function down()
	{
		// Non-destructive: keep performance indexes once created.
	}
}
