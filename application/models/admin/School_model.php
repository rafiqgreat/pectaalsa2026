<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class School_model extends MY_Model {

	public $table = 'schools';

	public function __construct()
	{
		parent::__construct();
	}

	private function apply_filters($filters)
	{
		if (!empty($filters['q'])) {
			$q = trim($filters['q']);
			$this->db->group_start()
				->like('schools.school_name', $q)
				->or_like('schools.username', $q)
				->or_like('schools.school_code', $q)
				->or_like('schools.school_lsacode', $q)
				->or_like('schools.school_address', $q)
				->group_end();
		}

		if (!empty($filters['school_level'])) {
			$this->db->where('schools.school_level', $filters['school_level']);
		}
		if (!empty($filters['school_gender'])) {
			$this->db->where('schools.school_gender', $filters['school_gender']);
		}
		if (!empty($filters['school_state_id'])) {
			$this->db->where('schools.school_state_id', $filters['school_state_id']);
		}
		if (!empty($filters['school_district_id'])) {
			$this->db->where('schools.school_district_id', $filters['school_district_id']);
		}
		if (!empty($filters['school_tehsil_id'])) {
			$this->db->where('schools.school_tehsil_id', $filters['school_tehsil_id']);
		}
		if (isset($filters['school_status']) && $filters['school_status'] !== '') {
			$this->db->where('schools.school_status', (int) $filters['school_status']);
		}
	}

	public function count_filtered_schools($filters = [])
	{
		$this->db->from('schools');
		$this->apply_filters($filters);
		return (int) $this->db->count_all_results();
	}

	public function get_filtered_schools($filters = [], $limit = null, $offset = 0)
	{
		$this->db->select('schools.*, states.state_name_en, districts.district_name_en, tehsils.tehsil_name_en')
			->from('schools')
			->join('states', 'states.state_id = schools.school_state_id', 'left')
			->join('districts', 'districts.district_id = schools.school_district_id', 'left')
			->join('tehsils', 'tehsils.tehsil_id = schools.school_tehsil_id', 'left');

		$this->apply_filters($filters);
		$this->db->order_by('schools.school_id', 'desc');

		if ($limit !== null) {
			$this->db->limit($limit, $offset);
		}

		return $this->db->get()->result();
	}

	public function username_exist($username)
	{
		$this->db->select('*');
		$this->db->from('schools');
		$this->db->where('username', $username);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function file_insert($path, $file_key, $type, $size_limit)
	{
		if (!is_dir($path)) {
			return 0;
		}

		if (!isset($_FILES[$file_key])) {
			return 0;
		}

		$file = $_FILES[$file_key];
		if ($file['size'] > $size_limit) {
			return 0;
		}

		$allowed_types = ['xls', 'xlsx', 'csv'];
		$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

		if (!in_array($file_ext, $allowed_types)) {
			return 0;
		}

		$destination = $path . '/' . basename($file['name']);
		if (move_uploaded_file($file['tmp_name'], $destination)) {
			return 1;
		}

		return 0;
	}
}
