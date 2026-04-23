<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_model extends MY_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function get_summary()
	{
		$users_total = (int) $this->db->count_all('users');
		$active_users = (int) $this->db->where('status', 1)->count_all_results('users');
		$roles_total = (int) $this->db->count_all('roles');
		$schools_total = (int) $this->db->count_all('schools');

		$recent_users = $this->db->select('users.id, users.name, users.email, users.username, users.created_at, roles.title AS role_title')
			->from('users')
			->join('roles', 'roles.id = users.role', 'left')
			->order_by('users.id', 'DESC')
			->limit(10)
			->get()
			->result_array();

		return [
			'users_total' => $users_total,
			'active_users' => $active_users,
			'roles_total' => $roles_total,
			'schools_total' => $schools_total,
			'recent_users' => $recent_users,
		];
	}
}
