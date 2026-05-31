<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends MY_Model {
	public $table = 'users';
	private $table_fields_cache = [];

	private function get_table_fields($table)
	{
		if (isset($this->table_fields_cache[$table])) {
			return $this->table_fields_cache[$table];
		}
		if (!$this->db->table_exists($table)) {
			$this->table_fields_cache[$table] = [];
			return [];
		}
		$this->table_fields_cache[$table] = $this->db->list_fields($table);
		return $this->table_fields_cache[$table];
	}

	private function filter_row_for_table($table, array $row)
	{
		$fields = $this->get_table_fields($table);
		if (empty($fields)) {
			return [];
		}
		return array_intersect_key($row, array_flip($fields));
	}

	private function safe_insert($table, array $row)
	{
		$filtered = $this->filter_row_for_table($table, $row);
		if (empty($filtered)) {
			return false;
		}
		return $this->db->insert($table, $filtered);
	}

	public function count_users()
	{
		return $this->db->count_all($this->table);
	}

	public function get_users_page($limit, $offset)
	{
		$this->db->select('u.id, u.name, u.username, u.email, u.role, u.status, r.title AS role_title');
		if ($this->db->table_exists('teacher_registration_steps') && $this->db->field_exists('review_status', 'teacher_registration_steps')) {
			$this->db->select('s.review_status');
			$this->db->join('teacher_registration_steps s', 's.user_id = u.id', 'left');
		} else {
			$this->db->select("'' AS review_status", false);
		}
		if ($this->db->table_exists('teacher_experiences')) {
			$this->db->join("(
				SELECT x.user_id,
					(
						SELECT x2.sector
						FROM teacher_experiences x2
						WHERE x2.user_id = x.user_id
						  AND x2.sector IS NOT NULL
						  AND x2.sector <> ''
						ORDER BY COALESCE(x2.end_date, CURDATE()) DESC, x2.id DESC
						LIMIT 1
					) AS sector
				FROM teacher_experiences x
				GROUP BY x.user_id
			) exp", 'exp.user_id = u.id', 'left', false);
			$this->db->select('exp.sector');
		} else {
			$this->db->select("'' AS sector", false);
		}

		return $this->db->from('users u')
			->join('roles r', 'r.id = u.role', 'left')
			->order_by('u.id', 'desc')
			->limit((int)$limit, (int)$offset)
			->get()
			->result();
	}

	private function apply_user_filters($filters)
	{
		if (!empty($filters['name'])) {
			$this->db->like('u.name', $filters['name']);
		}
		if (!empty($filters['username'])) {
			$this->db->like('u.username', $filters['username']);
		}
		if (!empty($filters['email'])) {
			$this->db->like('u.email', $filters['email']);
		}
		if (!empty($filters['role_id'])) {
			$this->db->where('u.role', (int)$filters['role_id']);
		}
	}

	public function count_users_filtered($filters = [])
	{
		$this->db->from('users u');
		$this->apply_user_filters($filters);
		return (int)$this->db->count_all_results();
	}

	public function get_users_page_filtered($filters, $limit, $offset)
	{
		$this->db->select('u.id, u.name, u.username, u.email, u.role, u.status, r.title AS role_title')
			->from('users u')
			->join('roles r', 'r.id = u.role', 'left');
		if ($this->db->table_exists('teacher_registration_steps') && $this->db->field_exists('review_status', 'teacher_registration_steps')) {
			$this->db->select('s.review_status');
			$this->db->join('teacher_registration_steps s', 's.user_id = u.id', 'left');
		} else {
			$this->db->select("'' AS review_status", false);
		}
		if ($this->db->table_exists('teacher_experiences')) {
			$this->db->join("(
				SELECT x.user_id,
					(
						SELECT x2.sector
						FROM teacher_experiences x2
						WHERE x2.user_id = x.user_id
						  AND x2.sector IS NOT NULL
						  AND x2.sector <> ''
						ORDER BY COALESCE(x2.end_date, CURDATE()) DESC, x2.id DESC
						LIMIT 1
					) AS sector
				FROM teacher_experiences x
				GROUP BY x.user_id
			) exp", 'exp.user_id = u.id', 'left', false);
			$this->db->select('exp.sector');
		} else {
			$this->db->select("'' AS sector", false);
		}
		$this->apply_user_filters($filters);
		return $this->db->order_by('u.id', 'desc')
			->limit((int)$limit, (int)$offset)
			->get()
			->result();
	}

	public function archive_delete_user($user_id, $meta = [])
	{
		$user = $this->db->get_where('users', ['id' => $user_id])->row_array();
		if (empty($user)) {
			return false;
		}

		$meta = array_merge([
			'deleted_at' => date('Y-m-d H:i:s'),
			'deleted_by_user_id' => null,
			'delete_reason' => null,
			'deleted_from_ip' => null,
			'delete_module' => null,
		], $meta);

		$this->db->trans_begin();
		$this->safe_insert('deleted_users', array_merge($user, $meta));
		$this->db->where('id', $user_id)->delete('users');

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return false;
		}

		$this->db->trans_commit();
		return true;
	}

	public function attempt($data)
	{
		$this->db->where('username', $data['username']);
		$this->db->or_where('email', $data['username']);
		$query = $this->db->get($this->table);

		if (!empty($query) && $query->num_rows() > 0) {
			$stored = (string) $query->row()->password;
			$info = password_get_info($stored);
			$is_valid = false;
			if (!empty($info['algo'])) {
				$is_valid = password_verify((string) $data['password'], $stored);
			} else {
				$is_valid = ($stored === hash("sha256", (string) $data['password']));
			}

			if ($is_valid) {
				return ($query->row()->status === '1') ? 'valid' : 'not_allowed';
			}
			return 'invalid_password';
		}

		return false;
	}

	public function login($row, $remember = false)
	{
		$time = time();
		$login_token = sha1($row->id . $row->password . $time);

		if ($remember === false) {
			$this->session->set_userdata([
				'login' => true,
				'login_token' => $login_token,
				'logged' => [
					'id' => $row->id,
					'role' => $row->role,
					'time' => $time,
				]
			]);
		} else {
			$data = [
				'id' => $row->id,
				'time' => time(),
			];
			$expiry = strtotime('+7 days');
			set_cookie('login', true, $expiry);
			set_cookie('logged', json_encode($data), $expiry);
			set_cookie('login_token', $login_token, $expiry);
		}

		setUserlang('es');
		$this->update($row->id, ['last_login' => date('Y-m-d H:m:i')]);
		$this->activity_model->add($row->name . ' (' . $row->username . ') Logged in', $row->id);
	}

	public function logout()
	{
		$this->session->unset_userdata('login');
		$this->session->unset_userdata('logged');
		delete_cookie('login');
		delete_cookie('logged');
		delete_cookie('login_token');
	}

	public function resetPassword($data)
	{
		$this->db->where('username', $data['username']);
		$this->db->or_where('email', $data['username']);
		$user = $this->db->get_where($this->table)->row();

		if (empty($user)) {
			return 'invalid';
		}

		$reset_token = password_hash((time() . $user->id), PASSWORD_BCRYPT);
		$this->db->where('id', $user->id);
		$this->db->update($this->table, compact('reset_token'));

		$this->email->from(setting('company_email'), setting('company_name'));
		$this->email->to($user->email);
		$this->email->subject('Reset Your Account Password | ' . setting('company_name'));

		$reset_link = url('login/new_password?token=' . $reset_token);
		$data = getEmailShortCodes();
		$data['user_id'] = $user->id;
		$data['user_name'] = $user->name;
		$data['user_email'] = $user->email;
		$data['user_username'] = $user->username;
		$data['reset_link'] = $reset_link;

		$html = $this->parser->parse('templates/email/reset', $data, true);
		$this->email->message($html);
		$this->email->send();

		return $user->email;
	}

	public function appendToSelectStr() { return NULL; }
	public function fromTableStr() { return 'users'; }
	public function joinArray() { return NULL; }
	public function whereClauseArray() { return NULL; }

	public function get_blacklisted_users()
	{
		return $this->db->select('u.id AS user_id, u.name AS user_name, u.username AS cnic, NULL AS application_id, u.blacklistedtype AS blacklist_reason, r.title AS application_type, u.district AS district, u.tehsil AS tehsil, NULL AS app_status, 0 AS has_application, u.role AS role_id')
			->from('users u')
			->join('roles r', 'r.id = u.role', 'left')
			->where('u.blacklisted', 1)
			->order_by('u.name', 'ASC')
			->get()
			->result_array();
	}
}
