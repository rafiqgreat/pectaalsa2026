<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_mcq_result_export_indexes extends CI_Migration
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
		if ($this->db->table_exists('crq_mcq_results')) {
			$this->add_index_if_missing('crq_mcq_results', 'idx_crq_mcq_barcode', '`barcode`');
			$this->add_index_if_missing('crq_mcq_results', 'idx_crq_mcq_variant_barcode', '`project_variant`, `barcode`');
		}

		$booklet_tables = [
			'digital_papers_booklets1',
			'digital_papers_booklets2',
			'digital_papers_booklets3',
			'digital_papers_booklets4',
		];

		foreach ($booklet_tables as $table) {
			if (!$this->db->table_exists($table)) {
				continue;
			}

			$this->add_index_if_missing($table, 'idx_mcq_export_filter', '`paper_grade`, `paper_subject_code`, `paper_version`, `paper_barcode`');
			$this->add_index_if_missing($table, 'idx_mcq_export_student', '`paper_school_id`, `paper_sr_roll`, `paper_subject_code`, `paper_version`, `paper_page_no`');
		}
	}

	public function down()
	{
		// Non-destructive: keep performance indexes once added.
	}
}
