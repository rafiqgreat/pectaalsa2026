<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_rubric_step_interval extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('emarking_question_rubric_steps')) {
			return;
		}

		$fields = $this->db->field_data('emarking_question_rubric_steps');
		$existing = [];
		foreach ($fields as $f) {
			$existing[$f->name] = true;
		}

		if (!isset($existing['interval'])) {
			$this->db->query("ALTER TABLE `emarking_question_rubric_steps` ADD COLUMN `interval` DECIMAL(5,2) NULL DEFAULT NULL AFTER `max_marks`");
		}
	}

	public function down()
	{
		// Non-destructive: keep column.
	}
}

