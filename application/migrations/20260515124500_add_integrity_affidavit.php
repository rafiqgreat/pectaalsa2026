<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_integrity_affidavit extends CI_Migration
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
		$this->add_column_if_missing(
			'teacher_security_documents',
			'integrity_affidavit_file',
			"`integrity_affidavit_file` VARCHAR(255) NULL DEFAULT NULL AFTER `document_file`"
		);
	}

	public function down()
	{
		$this->drop_column_if_exists('teacher_security_documents', 'integrity_affidavit_file');
	}
}

