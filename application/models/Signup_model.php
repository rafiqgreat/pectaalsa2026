<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Signup_model extends CI_Model
{
	private function role_column()
	{
		if ($this->db->field_exists('role', 'users')) {
			return 'role';
		}
		if ($this->db->field_exists('role_id', 'users')) {
			return 'role_id';
		}
		return 'role';
	}

	public function get_user($user_id)
	{
		return $this->db->get_where('users', ['id' => (int) $user_id])->row();
	}

	public function get_steps($user_id)
	{
		return $this->db->get_where('teacher_registration_steps', ['user_id' => (int) $user_id])->row();
	}

	private function ensure_steps_row($user_id)
	{
		$existing = $this->get_steps($user_id);
		if ($existing) {
			return $existing;
		}
		$this->db->insert('teacher_registration_steps', [
			'user_id' => (int) $user_id,
			'current_step' => 1,
		]);
		return $this->get_steps($user_id);
	}

	private function normalize_username_from_cnic($cnic)
	{
		$digits = preg_replace('/\\D+/', '', (string) $cnic);
		return $digits ?: null;
	}

	private function is_unique_except_user($table, $field, $value, $user_id = 0)
	{
		$this->db->from($table)->where($field, $value);
		if ((int) $user_id > 0) {
			$this->db->where('id !=', (int) $user_id);
		}
		return $this->db->count_all_results() === 0;
	}

	private function move_temp_file_if_needed($user_id, $relative_path)
	{
		$relative_path = (string) $relative_path;
		if ($relative_path === '') {
			return '';
		}
		if (strpos($relative_path, '..') !== false) {
			return '';
		}
		$relative_path = str_replace(['\\', '//'], ['/', '/'], $relative_path);

		// Already in user folder
		$user_prefix = 'uploads/teacher_registration/' . (int) $user_id . '/';
		if (strpos($relative_path, $user_prefix) === 0) {
			return $relative_path;
		}

		// Only move if it came from temp
		if (strpos($relative_path, 'uploads/teacher_registration/temp/') !== 0) {
			return '';
		}

		$src = FCPATH . $relative_path;
		if (!is_file($src)) {
			return '';
		}

		$dest_dir = FCPATH . $user_prefix;
		if (!is_dir($dest_dir)) {
			@mkdir($dest_dir, 0777, true);
		}

		$basename = basename($relative_path);
		$dest_rel = $user_prefix . $basename;
		$dest = FCPATH . $dest_rel;

		if (@rename($src, $dest)) {
			return $dest_rel;
		}
		if (@copy($src, $dest)) {
			@unlink($src);
			return $dest_rel;
		}
		return '';
	}

	private function is_valid_user_upload_path($user_id, $relative_path)
	{
		$relative_path = (string) $relative_path;
		if ($relative_path === '' || strpos($relative_path, '..') !== false) {
			return false;
		}
		$relative_path = str_replace(['\\', '//'], ['/', '/'], $relative_path);
		$prefix = 'uploads/teacher_registration/' . (int) $user_id . '/';
		return (strpos($relative_path, $prefix) === 0);
	}

	public function save_personal($user_id, array $payload, $profile_picture_path, $require_profile_picture = true)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();

		try {
			$email = (string) ($payload['email'] ?? '');
			$cnic = (string) ($payload['cnic'] ?? '');
			if (!$this->is_unique_except_user('users', 'email', $email, $user_id)) {
				$this->db->trans_rollback();
				return ['success' => false, 'message' => 'Email already exists.'];
			}
			if ($this->db->field_exists('cnic', 'users') && !$this->is_unique_except_user('users', 'cnic', $cnic, $user_id)) {
				$this->db->trans_rollback();
				return ['success' => false, 'message' => 'CNIC already exists.'];
			}
			$username = $this->normalize_username_from_cnic($cnic);
			if ($username && $this->db->field_exists('username', 'users') && !$this->is_unique_except_user('users', 'username', $username, $user_id)) {
				$this->db->trans_rollback();
				return ['success' => false, 'message' => 'CNIC/Username already exists.'];
			}

			$role_col = $this->role_column();
			$row = [
				'name' => $payload['name'],
				'father_name' => $payload['father_name'],
				'blood_group' => $payload['blood_group'],
				'gender' => $payload['gender'],
				'phone' => $payload['phone'],
				'dob' => $payload['dob'],
				'email' => $email,
				'cnic' => $cnic,
				'employee_no' => $payload['employee_no'],
			];

			$new_user_id = $user_id;
			if ($user_id <= 0) {
				$row[$role_col] = 2;
				$row['username'] = $username;
				// Pending by default; admin will approve/activate later.
				$row['status'] = 0;
				$row['password'] = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
				$this->db->insert('users', $row);
				$new_user_id = (int) $this->db->insert_id();
				$this->ensure_steps_row($new_user_id);
			} else {
				$this->db->where('id', $user_id)->update('users', $row);
				$this->ensure_steps_row($user_id);
			}

			$final_profile = $this->move_temp_file_if_needed($new_user_id, $profile_picture_path);
			if ($final_profile === '') {
				// For profile editing, allow keeping existing picture.
				if ($require_profile_picture) {
					$this->db->trans_rollback();
					return ['success' => false, 'message' => 'Profile picture upload is required.'];
				}
			} else {
				$this->db->where('id', $new_user_id)->update('users', ['profile_picture' => $final_profile]);
			}

			$this->mark_step_completed($new_user_id, 1);

			if ($this->db->trans_status() === false) {
				$this->db->trans_rollback();
				return ['success' => false, 'message' => 'Failed to save personal information.'];
			}
			$this->db->trans_commit();
			return ['success' => true, 'user_id' => $new_user_id];
		} catch (Throwable $e) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Unexpected error while saving.'];
		}
	}

	public function get_address($user_id)
	{
		return $this->db->get_where('teacher_addresses', ['user_id' => (int) $user_id])->row_array();
	}

	public function save_address($user_id, array $data)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$exists = $this->db->get_where('teacher_addresses', ['user_id' => $user_id])->row();
		if ($exists) {
			$this->db->where('user_id', $user_id)->update('teacher_addresses', $data);
		} else {
			$data['user_id'] = $user_id;
			$this->db->insert('teacher_addresses', $data);
		}
		$this->mark_step_completed($user_id, 2);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save address.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	public function get_educations($user_id)
	{
		return $this->db->order_by('id', 'ASC')->get_where('teacher_educations', ['user_id' => (int) $user_id])->result_array();
	}

	public function save_education($user_id, array $rows)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$this->db->where('user_id', $user_id)->delete('teacher_educations');
		foreach ($rows as $row) {
			if (!$this->is_valid_user_upload_path($user_id, $row['transcript_file'] ?? '')) {
				$this->db->trans_rollback();
				return ['success' => false, 'message' => 'Invalid transcript upload. Please upload again.'];
			}
			$row['user_id'] = $user_id;
			$this->db->insert('teacher_educations', $row);
		}
		$this->mark_step_completed($user_id, 3);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save education.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	public function get_experiences($user_id)
	{
		return $this->db->order_by('id', 'ASC')->get_where('teacher_experiences', ['user_id' => (int) $user_id])->result_array();
	}

	public function save_experience($user_id, array $rows, $no_experience)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$this->db->where('user_id', $user_id)->delete('teacher_experiences');
		if (!$no_experience) {
			foreach ($rows as $row) {
				if (!$this->is_valid_user_upload_path($user_id, $row['document_file'] ?? '')) {
					$this->db->trans_rollback();
					return ['success' => false, 'message' => 'Invalid experience document upload. Please upload again.'];
				}
				$row['user_id'] = $user_id;
				$this->db->insert('teacher_experiences', $row);
			}
		}
		if ($this->db->field_exists('no_experience', 'teacher_registration_steps')) {
			$this->db->where('user_id', $user_id)->update('teacher_registration_steps', ['no_experience' => $no_experience ? 1 : 0]);
		}
		$this->mark_step_completed($user_id, 4);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save experience.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	public function get_bank($user_id)
	{
		return $this->db->get_where('teacher_bank_details', ['user_id' => (int) $user_id])->row_array();
	}

	public function get_degree_options()
	{
		$defaults = [
			'PhD',
			'MPhil. / MS (18 years)',
			'Master / M.A/ MSc./ BS (Hons) (16 years)',
			'B.A / BSc. (14 years)',
			'HSSC',
			'SSC',
		];

		$options = $defaults;
		$seen = array_fill_keys($defaults, true);

		if ($this->db->table_exists('teacher_educations')) {
			$rows = $this->db->select('degree')->distinct()->order_by('degree', 'ASC')->get('teacher_educations')->result_array();
			foreach ($rows as $r) {
				$deg = trim((string) ($r['degree'] ?? ''));
				if ($deg === '') continue;
				if (isset($seen[$deg])) continue;
				$seen[$deg] = true;
				$options[] = $deg;
			}
		}

		return $options;
	}

	public function get_specialization_options()
	{
		$defaults = ['ENGLISH', 'URDU', 'MATH', 'SCIENCE'];

		$options = $defaults;
		$seen = array_fill_keys($defaults, true);

		if ($this->db->table_exists('teacher_specializations')) {
			$rows = $this->db->select('specialization')->distinct()->order_by('specialization', 'ASC')->get('teacher_specializations')->result_array();
			foreach ($rows as $r) {
				$spec = strtoupper(trim((string) ($r['specialization'] ?? '')));
				if ($spec === '') continue;
				if (isset($seen[$spec])) continue;
				$seen[$spec] = true;
				$options[] = $spec;
			}
		}

		sort($options, SORT_STRING);
		return $options;
	}

	public function save_bank($user_id, array $data)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$exists = $this->db->get_where('teacher_bank_details', ['user_id' => $user_id])->row();
		if ($exists) {
			$this->db->where('user_id', $user_id)->update('teacher_bank_details', $data);
		} else {
			$data['user_id'] = $user_id;
			$this->db->insert('teacher_bank_details', $data);
		}
		$this->mark_step_completed($user_id, 5);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save bank details.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	public function get_specialization($user_id)
	{
		return $this->db->get_where('teacher_specializations', ['user_id' => (int) $user_id])->row_array();
	}

	public function save_specialization($user_id, array $data)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$exists = $this->db->get_where('teacher_specializations', ['user_id' => $user_id])->row();
		if ($exists) {
			$this->db->where('user_id', $user_id)->update('teacher_specializations', $data);
		} else {
			$data['user_id'] = $user_id;
			$this->db->insert('teacher_specializations', $data);
		}
		$this->mark_step_completed($user_id, 6);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save specialization.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	public function get_security($user_id)
	{
		return $this->db->get_where('teacher_security_documents', ['user_id' => (int) $user_id])->row_array();
	}

	public function save_security($user_id, array $data)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();

		$doc_row = [
			'document_type' => $data['document_type'],
			'identification_number' => $data['identification_number'],
			'expiry_date' => $data['expiry_date'],
			'document_file' => $data['document_file'],
		];
		if (!$this->is_valid_user_upload_path($user_id, $doc_row['document_file'] ?? '')) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Invalid document upload. Please upload again.'];
		}

		$exists = $this->db->get_where('teacher_security_documents', ['user_id' => $user_id])->row();
		if ($exists) {
			$this->db->where('user_id', $user_id)->update('teacher_security_documents', $doc_row);
		} else {
			$doc_row['user_id'] = $user_id;
			$this->db->insert('teacher_security_documents', $doc_row);
		}

		// Password is optional for profile updates; required during signup.
		if (!empty($data['password'])) {
			$this->db->where('id', $user_id)->update('users', [
				'password' => password_hash($data['password'], PASSWORD_DEFAULT),
			]);
		}

		$this->mark_step_completed($user_id, 7);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save security setup.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	public function get_emarking($user_id)
	{
		return $this->db->order_by('id', 'ASC')->get_where('teacher_emarking_experiences', ['user_id' => (int) $user_id])->result_array();
	}

	public function save_emarking($user_id, array $rows, $no_experience)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$this->db->where('user_id', $user_id)->delete('teacher_emarking_experiences');
		if (!$no_experience) {
			foreach ($rows as $row) {
				$row['user_id'] = $user_id;
				$this->db->insert('teacher_emarking_experiences', $row);
			}
		}
		if ($this->db->field_exists('no_emarking_experience', 'teacher_registration_steps')) {
			$this->db->where('user_id', $user_id)->update('teacher_registration_steps', ['no_emarking_experience' => $no_experience ? 1 : 0]);
		}
		$this->mark_step_completed($user_id, 8);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Failed to save e-marking experience.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}

	private function mark_step_completed($user_id, $step)
	{
		$user_id = (int) $user_id;
		$step = (int) $step;
		$this->ensure_steps_row($user_id);

		$flags = [
			1 => 'personal_completed',
			2 => 'address_completed',
			3 => 'education_completed',
			4 => 'experience_completed',
			5 => 'bank_completed',
			6 => 'specialization_completed',
			7 => 'security_completed',
			8 => 'emarking_completed',
		];

		$update = [];
		if (isset($flags[$step])) {
			$update[$flags[$step]] = 1;
		}

		$steps_row = $this->get_steps($user_id);
		$current = $steps_row ? (int) $steps_row->current_step : 1;
		if ($current <= $step) {
			$update['current_step'] = min(8, $step + 1);
		}

		if (!empty($update)) {
			$this->db->where('user_id', $user_id)->update('teacher_registration_steps', $update);
		}
	}

	public function finalize_registration($user_id)
	{
		$user_id = (int) $user_id;
		$this->db->trans_begin();
		$steps = $this->get_steps($user_id);
		if (!$steps) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Registration steps not found.'];
		}

		$required = [
			'personal_completed',
			'address_completed',
			'education_completed',
			'experience_completed',
			'bank_completed',
			'specialization_completed',
			'security_completed',
			'emarking_completed',
		];
		foreach ($required as $col) {
			if (empty($steps->{$col})) {
				$this->db->trans_rollback();
				return ['success' => false, 'message' => 'Please complete all required steps before Signup.'];
			}
		}

		$this->db->where('user_id', $user_id)->update('teacher_registration_steps', [
			'registration_completed' => 1,
			'current_step' => 8,
		]);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			return ['success' => false, 'message' => 'Could not finalize registration.'];
		}
		$this->db->trans_commit();
		return ['success' => true];
	}
}
