<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking_settings_model extends CI_Model
{
	private function ensure_timer_table()
	{
		// Lightweight runtime migration (no DB migrations in this project)
		$this->db->query("CREATE TABLE IF NOT EXISTS `emarking_emarker_settings` (
			`user_id` INT NOT NULL,
			`timer_seconds` INT NOT NULL DEFAULT 15,
			`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}

	public function get_timer_seconds($user_id, $default = 15)
	{
		$this->ensure_timer_table();
		$user_id = (int) $user_id;
		if ($user_id <= 0) return (int) $default;

		$row = $this->db->get_where('emarking_emarker_settings', ['user_id' => $user_id])->row();
		if (!$row) return (int) $default;
		$sec = (int) ($row->timer_seconds ?? $default);
		if ($sec < 0) $sec = 0;
		return $sec;
	}

	public function get_timer_map($user_ids = [])
	{
		$this->ensure_timer_table();
		$out = [];
		$ids = array_values(array_unique(array_map('intval', (array) $user_ids)));
		$ids = array_values(array_filter($ids, function ($v) {
			return (int) $v > 0;
		}));
		if (empty($ids)) return $out;

		$rows = $this->db->from('emarking_emarker_settings')
			->where_in('user_id', $ids)
			->get()
			->result();
		foreach (($rows ?? []) as $r) {
			$out[(int) $r->user_id] = max(0, (int) ($r->timer_seconds ?? 15));
		}
		return $out;
	}

	public function set_timer_seconds($user_id, $seconds)
	{
		$this->ensure_timer_table();
		$user_id = (int) $user_id;
		if ($user_id <= 0) return false;

		$seconds = (int) $seconds;
		if ($seconds < 0) $seconds = 0;

		$exists = $this->db->select('user_id')
			->from('emarking_emarker_settings')
			->where('user_id', $user_id)
			->limit(1)
			->get()
			->row();

		if ($exists) {
			$this->db->where('user_id', $user_id)->update('emarking_emarker_settings', [
				'timer_seconds' => $seconds,
			]);
		} else {
			$this->db->insert('emarking_emarker_settings', [
				'user_id' => $user_id,
				'timer_seconds' => $seconds,
			]);
		}

		$err = $this->db->error();
		return empty($err['code']);
	}
}

