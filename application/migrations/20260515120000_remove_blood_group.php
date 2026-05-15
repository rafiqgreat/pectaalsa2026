<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_remove_blood_group extends CI_Migration
{
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

	public function up()
	{
		$this->drop_column_if_exists('users', 'blood_group');
	}

	public function down()
	{
		$this->add_column_if_missing('users', 'blood_group', "`blood_group` VARCHAR(10) DEFAULT NULL AFTER `phone`");
	}
}

