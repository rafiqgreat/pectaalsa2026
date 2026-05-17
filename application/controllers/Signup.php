<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends CI_Controller
{
	private $canonical_base = 'singup';
	private $register_base = 'user/login/register';

	private $step_titles = [
		1 => 'Personal Information',
		2 => 'Address Details',
		3 => 'Educational Details',
		4 => 'Experience Details',
		5 => 'Bank Detail',
		6 => 'Area of Specialization',
		7 => 'Security Setup',
		8 => 'Emarking Experience',
	];

	private $step_keys = [
		1 => 'personal',
		2 => 'address',
		3 => 'education',
		4 => 'experience',
		5 => 'bank',
		6 => 'specialization',
		7 => 'security',
		8 => 'emarking',
	];

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Signup_model', 'signup');
	}

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

	public function index()
	{
		$base = $this->wizard_base();
		$user_id = (int) $this->session->userdata('signup_user_id');
		if ($user_id > 0) {
			$steps = $this->signup->get_steps($user_id);
			if ($steps && (int) $steps->registration_completed === 0) {
				redirect($base . '/step/' . (int) $steps->current_step);
				return;
			}
			$this->session->unset_userdata('signup_user_id');
		}
		// For the public registration entrypoint, go directly to step 1.
		redirect($base . '/step/1');
	}

	public function resume()
	{
		$base = $this->wizard_base();
		if ($base !== $this->register_base) {
			redirect($base . '/step/1');
			return;
		}

		$data = [
			'assets' => assets_url(),
			'wizard_base' => $base,
			'resume_error' => (string) $this->session->flashdata('resume_error'),
			'resume_cnic' => (string) $this->session->flashdata('resume_cnic'),
			'resume_dob' => (string) $this->session->flashdata('resume_dob'),
		];
		$this->load->view('signup/resume', $data, false);
	}

	private function normalize_cnic($cnic)
	{
		$digits = preg_replace('/\\D+/', '', (string) $cnic);
		if (strlen($digits) !== 13) {
			return '';
		}
		return substr($digits, 0, 5) . '-' . substr($digits, 5, 7) . '-' . substr($digits, 12, 1);
	}

	public function resume_submit()
	{
		$base = $this->wizard_base();
		if ($base !== $this->register_base) {
			show_404();
		}

		postAllowed();
		$cnic_in = (string) $this->input->post('cnic', true);
		$dob = (string) $this->input->post('dob', true);

		$cnic = $this->normalize_cnic($cnic_in);
		if ($cnic === '') {
			$this->session->set_flashdata('resume_error', 'Invalid CNIC. Please enter 13 digits (with or without dashes).');
			$this->session->set_flashdata('resume_cnic', $cnic_in);
			$this->session->set_flashdata('resume_dob', $dob);
			redirect($base);
			return;
		}
		if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dob) || !$this->is_valid_ymd($dob)) {
			$this->session->set_flashdata('resume_error', 'Invalid Date of Birth.');
			$this->session->set_flashdata('resume_cnic', $cnic_in);
			$this->session->set_flashdata('resume_dob', $dob);
			redirect($base);
			return;
		}
		if (!$this->is_at_least_years_old($dob, 18)) {
			$this->session->set_flashdata('resume_error', 'You must be at least 18 years old to register.');
			$this->session->set_flashdata('resume_cnic', $cnic_in);
			$this->session->set_flashdata('resume_dob', $dob);
			redirect($base);
			return;
		}

		$role_col = $this->role_column();

		$this->db->select('u.id, u.dob, s.current_step, s.registration_completed');
		$this->db->from('users u');
		$this->db->join('teacher_registration_steps s', 's.user_id = u.id', 'inner');
		$this->db->where('u.cnic', $cnic);
		$this->db->where('u.dob', $dob);
		$this->db->where('u.' . $role_col, 2);
		$this->db->where('s.registration_completed', 0);
		$row = $this->db->get()->row();

		if (!$row) {
			$this->session->set_flashdata('resume_error', 'No in-progress registration found for the provided CNIC and Date of Birth.');
			$this->session->set_flashdata('resume_cnic', $cnic_in);
			$this->session->set_flashdata('resume_dob', $dob);
			redirect($base);
			return;
		}

		$step = (int) ($row->current_step ?? 1);
		$step = max(1, min(8, $step));

		$this->session->set_userdata('signup_user_id', (int) $row->id);
		$this->session->unset_userdata('signup_temp_key');

		$this->session->set_flashdata('resume_success', 'Resumed registration for CNIC ' . $cnic . '.');
		redirect($base . '/step/' . $step);
	}

	public function check_resume()
	{
		postAllowed();
		$base = $this->wizard_base();

		// Only allow this endpoint on the public registration wizard URL(s).
		if ($base !== $this->register_base && $base !== $this->canonical_base) {
			$this->json(['success' => false, 'message' => 'Not allowed.'], 403);
			return;
		}

		// If already in a signup session, do not auto-resume over it.
		$existing_user_id = (int) $this->session->userdata('signup_user_id');
		if ($existing_user_id > 0) {
			$this->json(['success' => true, 'found' => false]);
			return;
		}

		$cnic_in = (string) $this->input->post('cnic', true);
		$dob = (string) $this->input->post('dob', true);

		$cnic = $this->normalize_cnic($cnic_in);
		if ($cnic === '') {
			$this->json(['success' => false, 'message' => 'Invalid CNIC.']);
			return;
		}
		if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dob) || !$this->is_valid_ymd($dob)) {
			$this->json(['success' => false, 'message' => 'Invalid Date of Birth.']);
			return;
		}
		if (!$this->is_at_least_years_old($dob, 18)) {
			$this->json(['success' => false, 'message' => 'You must be at least 18 years old to register.']);
			return;
		}

		$role_col = $this->role_column();

		$this->db->select('u.id, s.current_step, s.registration_completed');
		$this->db->from('users u');
		$this->db->join('teacher_registration_steps s', 's.user_id = u.id', 'inner');
		$this->db->where('u.cnic', $cnic);
		$this->db->where('u.dob', $dob);
		$this->db->where('u.' . $role_col, 2);
		$this->db->where('s.registration_completed', 0);
		$row = $this->db->get()->row();

		if (!$row) {
			$this->json(['success' => true, 'found' => false]);
			return;
		}

		$step = (int) ($row->current_step ?? 1);
		$step = max(1, min(8, $step));

		$this->session->set_userdata('signup_user_id', (int) $row->id);
		$this->session->unset_userdata('signup_temp_key');
		$this->session->set_flashdata('resume_success', 'Resumed registration for CNIC ' . $cnic . '.');

		$this->json([
			'success' => true,
			'found' => true,
			'resume_url' => site_url($this->register_base . '/step/' . $step),
			'message' => 'Resumed registration.',
		]);
	}

	public function step($step = 1)
	{
		$base = $this->wizard_base();
		$step = (int) $step;
		if ($step < 1 || $step > 8) {
			show_404();
		}

		$user_id = (int) $this->session->userdata('signup_user_id');
		$steps = $user_id > 0 ? $this->signup->get_steps($user_id) : null;
		$allowed_step = $this->get_allowed_step($steps);

		if ($step !== 1 && (!$user_id || !$steps)) {
			redirect($base . '/step/1');
			return;
		}
		if ($step > $allowed_step) {
			redirect($base . '/step/' . $allowed_step);
			return;
		}

		$data = [
			'assets' => assets_url(),
			'step' => $step,
			'wizard_base' => $base,
			'step_titles' => $this->step_titles,
			'step_keys' => $this->step_keys,
			'steps_row' => $steps,
			'user_id' => $user_id,
			'allowed_step' => $allowed_step,
			'resume_success' => (string) $this->session->flashdata('resume_success'),
			'form_action' => $this->get_step_action($step),
			'step_view' => $this->get_step_view($step),
			'form_data' => $this->get_step_data($step, $user_id),
		];

		$this->load->view('signup/step_layout', $data, false);
	}

	private function get_allowed_step($steps_row)
	{
		if (!$steps_row) {
			return 1;
		}
		$allowed = (int) $steps_row->current_step;
		return max(1, min(8, $allowed));
	}

	private function get_step_view($step)
	{
		$map = [
			1 => 'signup/steps/personal',
			2 => 'signup/steps/address',
			3 => 'signup/steps/education',
			4 => 'signup/steps/experience',
			5 => 'signup/steps/bank',
			6 => 'signup/steps/specialization',
			7 => 'signup/steps/security',
			8 => 'signup/steps/emarking',
		];
		return $map[$step];
	}

	private function get_step_action($step)
	{
		$base = $this->wizard_base();
		$map = [
			1 => site_url($base . '/save_personal'),
			2 => site_url($base . '/save_address'),
			3 => site_url($base . '/save_education'),
			4 => site_url($base . '/save_experience'),
			5 => site_url($base . '/save_bank'),
			6 => site_url($base . '/save_specialization'),
			7 => site_url($base . '/save_security'),
			8 => site_url($base . '/save_emarking'),
		];
		return $map[$step];
	}

	private function wizard_base()
	{
		$s1 = strtolower((string) $this->uri->segment(1));
		$s2 = strtolower((string) $this->uri->segment(2));
		$s3 = strtolower((string) $this->uri->segment(3));

		if ($s1 === 'user' && $s2 === 'login' && $s3 === 'register') {
			return $this->register_base;
		}
		if ($s1 === 'signup' || $s1 === 'singup') {
			return $this->canonical_base;
		}
		return $this->canonical_base;
	}

	private function get_step_data($step, $user_id)
	{
		if ((int) $user_id <= 0) {
			return [];
		}
		switch ((int) $step) {
			case 1:
				return ['user' => $this->signup->get_user($user_id)];
			case 2:
				return ['address' => $this->signup->get_address($user_id)];
			case 3:
				return [
					'educations' => $this->signup->get_educations($user_id),
					'degree_options' => $this->signup->get_degree_options(),
				];
			case 4:
				$steps = $this->signup->get_steps($user_id);
				return [
					'experiences' => $this->signup->get_experiences($user_id),
					'no_experience' => $steps ? (int) $steps->no_experience : 0,
				];
			case 5:
				return ['bank' => $this->signup->get_bank($user_id)];
			case 6:
				return [
					'specialization' => $this->signup->get_specialization($user_id),
					'specialization_options' => $this->signup->get_specialization_options(),
				];
			case 7:
				return [
					'security' => $this->signup->get_security($user_id),
					'user' => $this->signup->get_user($user_id),
				];
			case 8:
				$steps = $this->signup->get_steps($user_id);
				return [
					'emarking' => $this->signup->get_emarking($user_id),
					'no_emarking_experience' => $steps ? (int) $steps->no_emarking_experience : 0,
				];
			default:
				return [];
		}
	}

	private function json($payload, $http_code = 200)
	{
		$this->output
			->set_status_header((int) $http_code)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload));
	}

	private function is_valid_ymd($date)
	{
		$date = (string) $date;
		if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
			return false;
		}
		$dt = DateTime::createFromFormat('Y-m-d', $date);
		return $dt && $dt->format('Y-m-d') === $date;
	}

	private function is_at_least_years_old($dob, $years)
	{
		$dob = (string) $dob;
		$years = (int) $years;
		if ($years < 0) $years = 0;
		if (!$this->is_valid_ymd($dob)) return false;

		try {
			$birth = new DateTime($dob);
			$cutoff = (clone $birth)->modify('+' . $years . ' years');
			$today = new DateTime(date('Y-m-d'));
			return $cutoff <= $today;
		} catch (Throwable $e) {
			return false;
		}
	}

	private function require_user_for_step($expected_step)
	{
		$user_id = (int) $this->session->userdata('signup_user_id');
		if ($user_id <= 0) {
			$this->json(['success' => false, 'message' => 'Session expired. Please start again.'], 401);
			return [0, null];
		}
		$steps = $this->signup->get_steps($user_id);
		if (!$steps) {
			$this->json(['success' => false, 'message' => 'Registration session not found.'], 401);
			return [0, null];
		}
		$allowed = $this->get_allowed_step($steps);
		if ((int) $expected_step > $allowed) {
			$this->json(['success' => false, 'message' => 'Please complete previous steps first.'], 403);
			return [0, null];
		}
		return [$user_id, $steps];
	}

	public function upload_file()
	{
		if (empty($_FILES['file'])) {
			$this->json(['success' => false, 'message' => 'No file uploaded.'], 422);
			return;
		}

		$field = (string) $this->input->post('field', true);
		$field = preg_replace('/[^a-zA-Z0-9_\\-]/', '', $field);
		if ($field === '') {
			$field = 'file';
		}

		$user_id = (int) $this->session->userdata('signup_user_id');
		$temp_key = (string) $this->session->userdata('signup_temp_key');
		if ($temp_key === '') {
			$temp_key = bin2hex(random_bytes(16));
			$this->session->set_userdata('signup_temp_key', $temp_key);
		}
		$relative_dir = $user_id > 0
			? 'uploads/teacher_registration/' . $user_id . '/'
			: 'uploads/teacher_registration/temp/' . $temp_key . '/';

		$relative_dir = str_replace(['\\', '//'], ['/', '/'], $relative_dir);
		$abs_dir = rtrim(FCPATH, "\\/") . DIRECTORY_SEPARATOR
			. str_replace('/', DIRECTORY_SEPARATOR, trim($relative_dir, '/'))
			. DIRECTORY_SEPARATOR;

		if (!is_dir($abs_dir)) {
			$made = @mkdir($abs_dir, 0777, true);
			$last_err = error_get_last();
			clearstatcache(true, $abs_dir);
			if (!$made && !is_dir($abs_dir)) {
				$detail = '';
				if ($last_err && !empty($last_err['message'])) {
					$detail = ' (' . $last_err['message'] . ')';
				}
				$this->json(['success' => false, 'message' => 'Upload directory could not be created: ' . $abs_dir . $detail], 422);
				return;
			}
		}
		if (!is_writable($abs_dir)) {
			$this->json(['success' => false, 'message' => 'Upload directory is not writable.'], 422);
			return;
		}
		$real = realpath($abs_dir);
		if ($real !== false) {
			$abs_dir = rtrim($real, "\\/") . DIRECTORY_SEPARATOR;
		}

		$allowed = 'jpg|jpeg|png|pdf';
		$config = [
			'upload_path' => $abs_dir,
			'allowed_types' => $allowed,
			'max_size' => 5120,
			'encrypt_name' => true,
			'remove_spaces' => true,
		];
		$this->load->library('upload');
		$this->upload->initialize($config);

		if (!$this->upload->do_upload('file')) {
			$this->json(['success' => false, 'message' => strip_tags($this->upload->display_errors())], 422);
			return;
		}

		$info = $this->upload->data();
		$relative_path = rtrim($relative_dir, '/') . '/' . $info['file_name'];

		$this->json([
			'success' => true,
			'field' => $field,
			'file_name' => $info['client_name'],
			'file_path' => $relative_path,
		]);
	}

	public function delete_file()
	{
		postAllowed();
		$relative_path = (string) $this->input->post('file_path', true);
		$relative_path = str_replace(['\\', '//'], ['/', '/'], $relative_path);
		if ($relative_path === '' || strpos($relative_path, '..') !== false) {
			$this->json(['success' => false, 'message' => 'Invalid file path.'], 422);
			return;
		}

		$user_id = (int) $this->session->userdata('signup_user_id');
		$temp_key = (string) $this->session->userdata('signup_temp_key');

		$allowed = false;
		if ($user_id > 0) {
			$allowed = (strpos($relative_path, 'uploads/teacher_registration/' . $user_id . '/') === 0);
		} else if ($temp_key !== '') {
			$allowed = (strpos($relative_path, 'uploads/teacher_registration/temp/' . $temp_key . '/') === 0);
		}

		if (!$allowed) {
			$this->json(['success' => false, 'message' => 'Not allowed.'], 403);
			return;
		}

		$abs = FCPATH . $relative_path;
		if (is_file($abs)) {
			@unlink($abs);
		}

		$this->json(['success' => true, 'message' => 'File removed.']);
	}

	public function save_personal()
	{
		postAllowed();
		$this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('father_name', 'Father Name', 'trim|required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('gender', 'Gender', 'trim|required|in_list[Male,Female,Other]|xss_clean');
		$this->form_validation->set_rules('phone', 'Phone Number', 'trim|required|max_length[30]|xss_clean');
		$this->form_validation->set_rules('dob', 'Date Of Birth', 'trim|required|xss_clean');
		$this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email|max_length[150]|xss_clean');
		$this->form_validation->set_rules('cnic', 'CNIC', 'trim|required|regex_match[/^(\\d{13}|\\d{5}-\\d{7}-\\d)$/]|xss_clean');
		$this->form_validation->set_rules('employee_no', 'Personal No/Employee Id', 'trim|required|max_length[100]|xss_clean');
		$this->form_validation->set_rules('profile_picture_path', 'Profile Picture', 'trim|required|xss_clean');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$user_id = (int) $this->session->userdata('signup_user_id');
		$cnic_raw = (string) post('cnic');
		$cnic_digits = preg_replace('/\\D+/', '', $cnic_raw);
		if (strlen($cnic_digits) !== 13) {
			$this->json(['success' => false, 'message' => 'Invalid CNIC.', 'errors' => ['cnic' => 'CNIC must be 13 digits.']], 422);
			return;
		}
		$cnic_fmt = substr($cnic_digits, 0, 5) . '-' . substr($cnic_digits, 5, 7) . '-' . substr($cnic_digits, 12, 1);
		$dob = (string) post('dob');
		if (!$this->is_at_least_years_old($dob, 18)) {
			$this->json([
				'success' => false,
				'message' => 'Please correct the highlighted errors.',
				'errors' => ['dob' => 'You must be at least 18 years old.'],
			], 422);
			return;
		}

		$payload = [
			'name' => post('name'),
			'father_name' => post('father_name'),
			'gender' => post('gender'),
			'phone' => post('phone'),
			'dob' => $dob,
			'email' => post('email'),
			'cnic' => $cnic_fmt,
			'employee_no' => post('employee_no'),
		];
		$profile_path = (string) post('profile_picture_path');

		$result = $this->signup->save_personal($user_id, $payload, $profile_path, true);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}

		$this->session->set_userdata('signup_user_id', (int) $result['user_id']);
		$this->session->unset_userdata('signup_temp_key');
		$this->json([
			'success' => true,
			'message' => 'Personal information saved.',
			'next_url' => site_url($this->wizard_base() . '/step/2'),
		]);
	}

	public function save_address()
	{
		postAllowed();
		[$user_id, $steps] = $this->require_user_for_step(2);
		if ($user_id <= 0) return;

		$this->form_validation->set_rules('address', 'Address', 'trim|required|xss_clean');
		$this->form_validation->set_rules('district', 'District', 'trim|xss_clean|max_length[100]');
		$this->form_validation->set_rules('city', 'City', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('province', 'Province', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('country', 'Country', 'trim|required|xss_clean|max_length[100]');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$data = [
			'address' => post('address'),
			'district' => post('district') ?: null,
			'city' => post('city'),
			'province' => post('province'),
			'country' => post('country'),
		];

		$result = $this->signup->save_address($user_id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Address saved.', 'next_url' => site_url($this->wizard_base() . '/step/3')]);
	}

	public function save_education()
	{
		postAllowed();
		[$user_id] = $this->require_user_for_step(3);
		if ($user_id <= 0) return;

		$degrees = (array) $this->input->post('degree', true);
		$institutes = (array) $this->input->post('institute', true);
		$years = (array) $this->input->post('passing_year', true);
		$cgpas = (array) $this->input->post('cgpa_percentage', true);
		$files = (array) $this->input->post('transcript_file', true);

		if (count($degrees) < 1) {
			$this->json(['success' => false, 'message' => 'Please add at least one education record for SSC, HSSC, and Master / M.A / MSc. / BS (Hons) (16 Years) using the "Add More" button.'], 422);
			return;
		}

		$required_degree_16 = 'Master / M.A/ MSc./ BS (Hons) (16 years)';
		$required_degree_hssc = 'HSSC';
		$required_degree_ssc = 'SSC';

		$rows = [];
		$has_16 = false;
		$has_hssc = false;
		$has_ssc = false;
		for ($i = 0; $i < count($degrees); $i++) {
			$row = [
				'degree' => trim((string) ($degrees[$i] ?? '')),
				'institute' => trim((string) ($institutes[$i] ?? '')),
				'passing_year' => trim((string) ($years[$i] ?? '')),
				'cgpa_percentage' => trim((string) ($cgpas[$i] ?? '')),
				'transcript_file' => trim((string) ($files[$i] ?? '')),
			];
			if ($row['degree'] === '' || $row['institute'] === '' || $row['passing_year'] === '' || $row['cgpa_percentage'] === '' || $row['transcript_file'] === '') {
				$this->json(['success' => false, 'message' => 'All education fields and uploads are required.'], 422);
				return;
			}
			if ($row['degree'] === $required_degree_16) $has_16 = true;
			if ($row['degree'] === $required_degree_hssc) $has_hssc = true;
			if ($row['degree'] === $required_degree_ssc) $has_ssc = true;
			$rows[] = $row;
		}

		if (!$has_16 || !$has_hssc || !$has_ssc) {
			$missing = [];
			if (!$has_16) $missing[] = $required_degree_16;
			if (!$has_hssc) $missing[] = $required_degree_hssc;
			if (!$has_ssc) $missing[] = $required_degree_ssc;
			$missing_text = implode(', ', $missing);
			$this->json([
				'success' => false,
				'message' => 'Please correct the highlighted errors.',
				'errors' => ['degree[]' => 'Required degrees missing: ' . $missing_text . '. Please add them using Add More.'],
			], 422);
			return;
		}

		$result = $this->signup->save_education($user_id, $rows);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Educational details saved.', 'next_url' => site_url($this->wizard_base() . '/step/4')]);
	}

	public function save_experience()
	{
		postAllowed();
		[$user_id] = $this->require_user_for_step(4);
		if ($user_id <= 0) return;

		$no_experience = $this->input->post('no_experience', true) ? 1 : 0;
		if ($no_experience) {
			$result = $this->signup->save_experience($user_id, [], true);
			if (!$result['success']) {
				$this->json($result, 422);
				return;
			}
			$this->json(['success' => true, 'message' => 'Experience saved.', 'next_url' => site_url('Signup/step/5')]);
			return;
		}

		$departments = (array) $this->input->post('department', true);
		$sectors = (array) $this->input->post('sector', true);
		$experience_types = (array) $this->input->post('experience_type', true);
		$job_types = (array) $this->input->post('job_type', true);
		$start_dates = (array) $this->input->post('start_date', true);
		$end_dates = (array) $this->input->post('end_date', true);
		$current_pos = (array) $this->input->post('currently_working_pos', true);
		$current_flags = (array) $this->input->post('currently_working', true);
		$teaching_levels = (array) $this->input->post('teaching_level', true);
		$bps = (array) $this->input->post('bps', true);
		$docs = (array) $this->input->post('document_file', true);

		if (count($departments) < 1) {
			$this->json(['success' => false, 'message' => 'Please add at least one experience record or select No Experience.'], 422);
			return;
		}

		$rows = [];
		for ($i = 0; $i < count($departments); $i++) {
			$sector = trim((string) ($sectors[$i] ?? ''));
			$currently = 0;
			if (isset($current_pos[$i])) {
				$currently = ((string) $current_pos[$i] === '1') ? 1 : 0;
			} else {
				$currently = !empty($current_flags[$i]) ? 1 : 0;
			}
			$row = [
				'department' => trim((string) ($departments[$i] ?? '')),
				'sector' => $sector,
				'experience_type' => trim((string) ($experience_types[$i] ?? '')),
				'job_type' => trim((string) ($job_types[$i] ?? '')),
				'start_date' => trim((string) ($start_dates[$i] ?? '')),
				'end_date' => $currently ? null : trim((string) ($end_dates[$i] ?? '')),
				'currently_working' => $currently,
				'teaching_level' => trim((string) ($teaching_levels[$i] ?? '')),
				'bps' => ($sector === 'Government') ? (trim((string) ($bps[$i] ?? '')) ?: null) : null,
				'document_file' => trim((string) ($docs[$i] ?? '')),
			];
			if ($row['department'] === '' || $row['sector'] === '' || $row['experience_type'] === '' || $row['job_type'] === '' || $row['start_date'] === '' || $row['teaching_level'] === '' || $row['document_file'] === '') {
				$this->json(['success' => false, 'message' => 'All experience fields and uploads are required.'], 422);
				return;
			}
			if (!$this->is_valid_ymd($row['start_date'])) {
				$this->json(['success' => false, 'message' => 'Invalid Start Date.', 'errors' => ['start_date' => 'Invalid Start Date.']], 422);
				return;
			}
			if (!$currently && empty($row['end_date'])) {
				$this->json(['success' => false, 'message' => 'End Date is required unless Currently Working is checked.'], 422);
				return;
			}
			if (!$currently) {
				if (!$this->is_valid_ymd($row['end_date'])) {
					$this->json(['success' => false, 'message' => 'Invalid End Date.', 'errors' => ['end_date' => 'Invalid End Date.']], 422);
					return;
				}
				if ($row['end_date'] < $row['start_date']) {
					$this->json(['success' => false, 'message' => 'End Date must be greater than or equal to Start Date.'], 422);
					return;
				}
			}
			if ($sector === 'Government' && empty($row['bps'])) {
				$this->json(['success' => false, 'message' => 'BPS is required for Government sector.'], 422);
				return;
			}
			$rows[] = $row;
		}

		$result = $this->signup->save_experience($user_id, $rows, false);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Experience saved.', 'next_url' => site_url($this->wizard_base() . '/step/5')]);
	}

	public function save_bank()
	{
		postAllowed();
		[$user_id] = $this->require_user_for_step(5);
		if ($user_id <= 0) return;

		$this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|required|xss_clean|max_length[150]');
		$this->form_validation->set_rules('branch_name', 'Branch Name', 'trim|required|xss_clean|max_length[150]');
		$this->form_validation->set_rules('branch_code', 'Branch Code', 'trim|required|xss_clean|max_length[50]');
		$this->form_validation->set_rules('account_title', 'Account Title', 'trim|required|xss_clean|max_length[150]');
		$this->form_validation->set_rules('iban_account_no', 'Account/IBAN Number', 'trim|required|xss_clean|max_length[50]');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$iban = strtoupper(preg_replace('/\s+/', '', (string) post('iban_account_no')));
		if (strlen($iban) !== 24 || !ctype_alnum($iban)) {
			$this->json([
				'success' => false,
				'message' => 'Please correct the highlighted errors.',
				'errors' => ['iban_account_no' => 'IBAN must be exactly 24 characters (letters/numbers only).'],
			], 422);
			return;
		}

		$data = [
			'bank_name' => post('bank_name'),
			'branch_name' => post('branch_name'),
			'branch_code' => post('branch_code'),
			'account_title' => post('account_title'),
			'iban_account_no' => $iban,
			'international_user' => $this->input->post('international_user', true) ? 1 : 0,
		];

		$result = $this->signup->save_bank($user_id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Bank details saved.', 'next_url' => site_url($this->wizard_base() . '/step/6')]);
	}

	public function save_specialization()
	{
		postAllowed();
		[$user_id] = $this->require_user_for_step(6);
		if ($user_id <= 0) return;

		$this->form_validation->set_rules('specialization', 'Area of specialization', 'trim|required|xss_clean|max_length[150]');
		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$spec = strtoupper(trim((string) post('specialization')));

		$result = $this->signup->save_specialization($user_id, ['specialization' => $spec]);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Specialization saved.', 'next_url' => site_url($this->wizard_base() . '/step/7')]);
	}

	public function save_security()
	{
		postAllowed();
		[$user_id] = $this->require_user_for_step(7);
		if ($user_id <= 0) return;

		$this->form_validation->set_rules('document_type', 'Document Type', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('identification_number', 'Identification Number', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('expiry_date', 'Expiry Date', 'trim|required|xss_clean');
		$this->form_validation->set_rules('document_file', 'Upload Document', 'trim|required|xss_clean');

		$user = $this->signup->get_user($user_id);
		$role_col = $this->role_column();
		$is_emarker = ($user && isset($user->{$role_col}) && (int) $user->{$role_col} === 2);
		if ($is_emarker) {
			$this->form_validation->set_rules('integrity_affidavit_file', 'Integrity Affidavit', 'trim|required|xss_clean');
		}

		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
		$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$data = [
			'document_type' => post('document_type'),
			'identification_number' => post('identification_number'),
			'expiry_date' => post('expiry_date'),
			'document_file' => post('document_file'),
			'integrity_affidavit_file' => $is_emarker ? post('integrity_affidavit_file') : null,
			'password' => (string) $this->input->post('password', false),
		];

		$result = $this->signup->save_security($user_id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Security setup saved.', 'next_url' => site_url($this->wizard_base() . '/step/8')]);
	}

	public function save_emarking()
	{
		postAllowed();
		[$user_id, $steps] = $this->require_user_for_step(8);
		if ($user_id <= 0) return;

		$no_exp = $this->input->post('no_emarking_experience', true) ? 1 : 0;
		if ($no_exp) {
			$result = $this->signup->save_emarking($user_id, [], true);
			if (!$result['success']) {
				$this->json($result, 422);
				return;
			}
			$final = $this->signup->finalize_registration($user_id);
			if (!$final['success']) {
				$this->json($final, 422);
				return;
			}
			$this->session->unset_userdata('signup_user_id');
			$this->json([
				'success' => true,
				'message' => 'Signup completed.',
				'completed' => true,
				'login_url' => site_url('user/login'),
			]);
			return;
		}

		$departments = (array) $this->input->post('department', true);
		$from_dates = (array) $this->input->post('from_date', true);
		$to_dates = (array) $this->input->post('to_date', true);

		if (count($departments) < 1) {
			$this->json(['success' => false, 'message' => 'Please add at least one e-marking experience record or select No Experience.'], 422);
			return;
		}

		$rows = [];
		for ($i = 0; $i < count($departments); $i++) {
			$row = [
				'department' => trim((string) ($departments[$i] ?? '')),
				'from_date' => trim((string) ($from_dates[$i] ?? '')),
				'to_date' => trim((string) ($to_dates[$i] ?? '')),
			];
			if ($row['department'] === '' || $row['from_date'] === '' || $row['to_date'] === '') {
				$this->json(['success' => false, 'message' => 'All e-marking experience fields are required.'], 422);
				return;
			}
			if (!$this->is_valid_ymd($row['from_date']) || !$this->is_valid_ymd($row['to_date'])) {
				$this->json(['success' => false, 'message' => 'Invalid e-marking dates.'], 422);
				return;
			}
			if ($row['to_date'] < $row['from_date']) {
				$this->json(['success' => false, 'message' => 'To date must be greater than or equal to From date.'], 422);
				return;
			}
			$rows[] = $row;
		}

		$result = $this->signup->save_emarking($user_id, $rows, false);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}

		$final = $this->signup->finalize_registration($user_id);
		if (!$final['success']) {
			$this->json($final, 422);
			return;
		}
		$this->session->unset_userdata('signup_user_id');
		$this->json([
			'success' => true,
			'message' => 'Signup completed.',
			'completed' => true,
			'login_url' => site_url('user/login'),
		]);
	}
}
