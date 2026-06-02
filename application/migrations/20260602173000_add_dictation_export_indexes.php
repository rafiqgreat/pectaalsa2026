<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_dictation_export_indexes extends CI_Migration
{
	private function table_exists($table)
	{
		return $this->db->table_exists($table);
	}

	private function index_exists($table, $index_name)
	{
		if (!$this->table_exists($table)) {
			return false;
		}

		$query = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($index_name));
		return $query && $query->num_rows() > 0;
	}

	private function add_index_if_missing($table, $index_name, $columns_sql)
	{
		if (!$this->table_exists($table) || $this->index_exists($table, $index_name)) {
			return;
		}

		$this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ({$columns_sql})");
	}

	private function drop_index_if_exists($table, $index_name)
	{
		if (!$this->table_exists($table) || !$this->index_exists($table, $index_name)) {
			return;
		}

		$this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$index_name}`");
	}

	public function up()
	{
		// Helps the dictation CSV export filter and group question-image rows quickly.
		$this->add_index_if_missing(
			'emarking_question_images',
			'idx_dictation_export_filter',
			'`assessment_type`, `grade`, `subject_code`, `version`, `source_table`, `source_paper_id`, `paper_barcode`'
		);

		// Helps joins from filtered question images into school metadata when district filter is used.
		$this->add_index_if_missing(
			'emarking_question_images',
			'idx_dictation_export_school',
			'`assessment_type`, `school_id`, `source_table`, `source_paper_id`'
		);

		// Helps pick the best/final mark row per image/question during export.
		$this->add_index_if_missing(
			'emarking_marks',
			'idx_dictation_export_pick',
			'`question_image_id`, `question_id`, `is_final`, `finalized_at`, `marked_at`, `id`'
		);
	}

	public function down()
	{
		$this->drop_index_if_exists('emarking_marks', 'idx_dictation_export_pick');
		$this->drop_index_if_exists('emarking_question_images', 'idx_dictation_export_school');
		$this->drop_index_if_exists('emarking_question_images', 'idx_dictation_export_filter');
	}
}
