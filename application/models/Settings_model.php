<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends MY_Model {

	public $table = 'settings';

	public function __construct()
	{
		parent::__construct();
	}

	public function getValueByKey($key = '')
	{
		return ($query = $this->db->get_where($this->table, ['key' => $key], 1)) && $query->num_rows() > 0 ? $query->row()->value : null;
	}

	public function getByKey($key = '')
	{
		return ($query = $this->db->get_where($this->table, ['key' => $key], 1)) && $query->num_rows() > 0 ? $query->row() : null;
	}

	public function updateByKey($key, $value)
	{
		$row = $this->getByKey($key);
		if ($row) {
			$this->db->where('key', $key);
			return $this->db->update($this->table, [
				'value' => $value
			]);
		}

		return $this->db->insert($this->table, [
			'key' => $key,
			'value' => $value
		]);
	}

	public function get_setting($key, $default = null)
	{
		$this->db->from($this->table);
		$this->db->where('key', $key);
		$this->db->limit(1);
		$row = $this->db->get()->row();
		if (!$row) {
			return $default;
		}
		return $row->value;
	}

	public function set_setting($key, $value)
	{
		$this->db->from($this->table);
		$this->db->where('key', $key);
		$this->db->limit(1);
		$row = $this->db->get()->row();

		if ($row) {
			$this->db->where('key', $key);
			return $this->db->update($this->table, [
				'value' => $value,
			]);
		}

		return $this->db->insert($this->table, [
			'key' => $key,
			'value' => $value,
			'created_at' => date('Y-m-d H:i:s'),
		]);
	}

	public function get_registration_close_at()
	{
		$value = (string) $this->get_setting('registration_close_at', '');
		$value = trim($value);
		return $value !== '' ? $value : null;
	}

	public function registration_is_open()
	{
		$enabled = (string) $this->get_setting('registration_enabled', '1');
		if ($enabled !== '1') {
			return false;
		}

		$close_at = $this->get_registration_close_at();
		if (!$close_at) {
			return true;
		}

		$close_dt = DateTime::createFromFormat('Y-m-d H:i:s', $close_at);
		if (!$close_dt) {
			return true;
		}

		$now = new DateTime('now');
		return $now <= $close_dt;
	}
}

/* End of file Settings_model.php */
/* Location: ./application/models/Settings_model.php */
