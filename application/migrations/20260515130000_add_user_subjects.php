<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_user_subjects extends CI_Migration
{
	private function add_column_if_missing($table, $column, $definition_sql)
	{
		if (!$this->db->table_exists($table)) {
			return;
		}
		if ($this->db->field_exists($column, $table)) {
			return;
		}
		$this->db->query("ALTER TABLE `{$table}` ADD COLUMN {$definition_sql}");
	}

	private function drop_column_if_exists($table, $column)
	{
		if (!$this->db->table_exists($table)) {
			return;
		}
		if (!$this->db->field_exists($column, $table)) {
			return;
		}
		$this->db->query("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
	}

	public function up()
	{
		// Stores multi-subject selection for special roles (e.g., role 18/19).
		$this->add_column_if_missing('users', 'subjects', "`subjects` TEXT NULL DEFAULT NULL AFTER `address`");
	}

	public function down()
	{
		$this->drop_column_if_exists('users', 'subjects');
	}
}

