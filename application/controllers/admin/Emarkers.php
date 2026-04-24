<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emarkers extends MY_Controller
{
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
		// Admin only (role 1)
		if ((int) logged('role') !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$this->page_data['page']->title = 'E-Markers';
		$this->page_data['page']->menu = 'emarkers';
		$this->load->model('Signup_model', 'signup');
	}

	public function index()
	{
		$status = (string) $this->input->get('status', true);
		$status = in_array($status, ['all', 'pending', 'active'], true) ? $status : 'all';
		$reg = (string) $this->input->get('reg', true);
		$reg = in_array($reg, ['all', 'completed', 'incomplete'], true) ? $reg : 'all';
		$q = trim((string) $this->input->get('q', true));

		$this->db->select('u.id, u.name, u.email, u.phone, u.cnic, u.status, u.created_at, s.registration_completed')
			->from('users u')
			->join('teacher_registration_steps s', 's.user_id = u.id', 'left')
			->where('u.role', 2)
			->order_by('u.id', 'DESC');

		if ($status === 'pending') {
			$this->db->where('u.status', 0);
		} elseif ($status === 'active') {
			$this->db->where('u.status', 1);
		}

		if ($reg === 'completed') {
			$this->db->where('s.registration_completed', 1);
		} elseif ($reg === 'incomplete') {
			$this->db->group_start()
				->where('s.registration_completed IS NULL', null, false)
				->or_where('s.registration_completed', 0)
				->group_end();
		}

		if ($q !== '') {
			$this->db->group_start()
				->like('u.name', $q)
				->or_like('u.email', $q)
				->or_like('u.phone', $q)
				->or_like('u.cnic', $q)
				->group_end();
		}

		$rows = $this->db->get()->result();

		// Attach doc presence counts (small N -> per-row queries acceptable)
		foreach ($rows as $r) {
			$r->has_security_doc = (int) $this->db->from('teacher_security_documents')->where('user_id', (int) $r->id)->count_all_results() > 0;
			$r->edu_docs = (int) $this->db->from('teacher_educations')->where('user_id', (int) $r->id)->count_all_results();
			$r->exp_docs = (int) $this->db->from('teacher_experiences')->where('user_id', (int) $r->id)->count_all_results();
		}

		$this->page_data['emarkers'] = $rows;
		$this->page_data['filters'] = ['status' => $status, 'reg' => $reg, 'q' => $q];
		$this->load->view('admin/emarkers/list', $this->page_data);
	}

	public function view($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$this->page_data['page']->title = 'E-Marker Profile';
		$this->page_data['page']->menu = 'emarkers';

		$this->page_data['user_row'] = $user;
		$this->page_data['steps_row'] = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		$this->page_data['address'] = $this->db->get_where('teacher_addresses', ['user_id' => $id])->row();
		$this->page_data['educations'] = $this->db->order_by('id', 'ASC')->get_where('teacher_educations', ['user_id' => $id])->result();
		$this->page_data['experiences'] = $this->db->order_by('id', 'ASC')->get_where('teacher_experiences', ['user_id' => $id])->result();
		$this->page_data['bank'] = $this->db->get_where('teacher_bank_details', ['user_id' => $id])->row();
		$this->page_data['specialization'] = $this->db->get_where('teacher_specializations', ['user_id' => $id])->row();
		$this->page_data['security'] = $this->db->get_where('teacher_security_documents', ['user_id' => $id])->row();
		$this->page_data['emarking'] = $this->db->order_by('id', 'ASC')->get_where('teacher_emarking_experiences', ['user_id' => $id])->result();

		$this->load->view('admin/emarkers/view', $this->page_data);
	}

	public function change_password($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$this->page_data['page']->title = 'Change E-Marker Password';
		$this->page_data['page']->menu = 'emarkers';
		$this->page_data['user_row'] = $user;
		$this->load->view('admin/emarkers/change_password', $this->page_data);
	}

	public function update_password($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();
		postAllowed();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$password = (string) $this->input->post('password', false);
		$confirm = (string) $this->input->post('password_confirm', false);

		if (strlen($password) < 6) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Password must be at least 6 characters.');
			redirect('admin/emarkers/change_password/' . $id);
			return;
		}
		if ($password !== $confirm) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Password does not match confirm password.');
			redirect('admin/emarkers/change_password/' . $id);
			return;
		}

		$this->db->where('id', $id)->update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)]);
		$this->activity_model->add("Admin reset E-Marker user #{$id} password", logged('id'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Password updated successfully.');
		redirect('admin/emarkers/view/' . $id);
	}

	public function edit($id = 0, $step = 1)
	{
		$id = (int) $id;
		$step = (int) $step;
		if ($id <= 0) show_404();
		if ($step < 1 || $step > 8) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$steps = $this->signup->get_steps($id);
		$form_data = $this->get_step_data($step, $id);

		$view_map = [
			1 => 'signup/steps/personal',
			2 => 'signup/steps/address',
			3 => 'signup/steps/education',
			4 => 'signup/steps/experience',
			5 => 'signup/steps/bank',
			6 => 'signup/steps/specialization',
			7 => 'admin/emarkers/steps/security',
			8 => 'signup/steps/emarking',
		];

		$action_map = [
			1 => site_url('admin/emarkers/save_personal/' . $id),
			2 => site_url('admin/emarkers/save_address/' . $id),
			3 => site_url('admin/emarkers/save_education/' . $id),
			4 => site_url('admin/emarkers/save_experience/' . $id),
			5 => site_url('admin/emarkers/save_bank/' . $id),
			6 => site_url('admin/emarkers/save_specialization/' . $id),
			7 => site_url('admin/emarkers/save_security/' . $id),
			8 => site_url('admin/emarkers/save_emarking/' . $id),
		];

		$data = [
			'step' => $step,
			'user_id' => $id,
			'step_titles' => $this->step_titles,
			'step_keys' => $this->step_keys,
			'steps_row' => $steps,
			// Admin can jump anywhere during edit
			'allowed_step' => 8,
			'step_view' => $view_map[$step],
			'form_action' => $action_map[$step],
			'form_data' => $form_data,
			'upload_url' => site_url('admin/emarkers/upload_file/' . $id),
			'delete_url' => site_url('admin/emarkers/delete_file/' . $id),
		];

		$this->page_data['page']->title = 'Edit E-Marker';
		$this->page_data['page']->menu = 'emarkers';
		$this->page_data['wizard'] = $data;
		$this->load->view('admin/emarkers/edit_wizard', $this->page_data);
	}

	public function upload_file($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		if (empty($_FILES['file'])) {
			$this->json(['success' => false, 'message' => 'No file uploaded.'], 422);
			return;
		}

		$field = (string) $this->input->post('field', true);
		$field = preg_replace('/[^a-zA-Z0-9_\\-]/', '', $field);
		if ($field === '') $field = 'file';

		$relative_dir = 'uploads/teacher_registration/' . $id . '/';
		$abs_dir = FCPATH . rtrim(str_replace(['\\', '//'], ['/', '/'], $relative_dir), '/') . '/';
		if (!is_dir($abs_dir)) {
			@mkdir($abs_dir, 0777, true);
		}

		$config = [
			'upload_path' => $abs_dir,
			'allowed_types' => 'jpg|jpeg|png|pdf',
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

	public function delete_file($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		postAllowed();
		$relative_path = (string) $this->input->post('file_path', true);
		$relative_path = str_replace(['\\', '//'], ['/', '/'], $relative_path);
		if ($relative_path === '' || strpos($relative_path, '..') !== false) {
			$this->json(['success' => false, 'message' => 'Invalid file path.'], 422);
			return;
		}
		if (strpos($relative_path, 'uploads/teacher_registration/' . $id . '/') !== 0) {
			$this->json(['success' => false, 'message' => 'Not allowed.'], 403);
			return;
		}
		$abs = FCPATH . $relative_path;
		if (is_file($abs)) {
			@unlink($abs);
		}
		$this->json(['success' => true, 'message' => 'File removed.']);
	}

	public function save_personal($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('father_name', 'Father Name', 'trim|required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('blood_group', 'Blood Group', 'trim|required|max_length[10]|xss_clean');
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

		$cnic_raw = (string) post('cnic');
		$cnic_digits = preg_replace('/\\D+/', '', $cnic_raw);
		if (strlen($cnic_digits) !== 13) {
			$this->json(['success' => false, 'message' => 'Invalid CNIC.', 'errors' => ['cnic' => 'CNIC must be 13 digits.']], 422);
			return;
		}
		$cnic_fmt = substr($cnic_digits, 0, 5) . '-' . substr($cnic_digits, 5, 7) . '-' . substr($cnic_digits, 12, 1);

		$payload = [
			'name' => post('name'),
			'father_name' => post('father_name'),
			'blood_group' => post('blood_group'),
			'gender' => post('gender'),
			'phone' => post('phone'),
			'dob' => post('dob'),
			'email' => post('email'),
			'cnic' => $cnic_fmt,
			'employee_no' => post('employee_no'),
		];
		$profile_path = (string) post('profile_picture_path');

		$result = $this->signup->save_personal($id, $payload, $profile_path, true);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Personal information updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/2')]);
	}

	public function save_address($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

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

		$result = $this->signup->save_address($id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Address updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/3')]);
	}

	public function save_education($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$degrees = $this->input->post('degree', true);
		$institutes = $this->input->post('institute', true);
		$years = $this->input->post('passing_year', true);
		$cgp = $this->input->post('cgpa_percentage', true);
		$files = $this->input->post('transcript_file', true);

		$rows = [];
		if (is_array($degrees)) {
			foreach ($degrees as $i => $deg) {
				$deg = trim((string) $deg);
				$inst = trim((string) ($institutes[$i] ?? ''));
				$yr = trim((string) ($years[$i] ?? ''));
				$cg = trim((string) ($cgp[$i] ?? ''));
				$file = trim((string) ($files[$i] ?? ''));
				if ($deg === '' && $inst === '' && $yr === '' && $cg === '' && $file === '') continue;
				$rows[] = [
					'degree' => $deg,
					'institute' => $inst,
					'passing_year' => $yr,
					'cgpa_percentage' => $cg,
					'transcript_file' => $file,
				];
			}
		}

		if (empty($rows)) {
			$this->json(['success' => false, 'message' => 'Please add at least one education record.'], 422);
			return;
		}

		foreach ($rows as $idx => $r) {
			if ($r['degree'] === '') {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['degree[]' => 'Degree is required.']], 422);
				return;
			}
			if ($r['institute'] === '') {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['institute[]' => 'Institute/University is required.']], 422);
				return;
			}
			if (!preg_match('/^\\d{4}$/', (string) $r['passing_year'])) {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['passing_year[]' => 'Passing Year is required.']], 422);
				return;
			}
			if ($r['cgpa_percentage'] === '') {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['cgpa_percentage[]' => 'CGPA/Percentage is required.']], 422);
				return;
			}
			if ($r['transcript_file'] === '') {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['transcript_file[]' => 'Upload Degree/Transcript is required.']], 422);
				return;
			}
		}

		$result = $this->signup->save_education($id, $rows);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Educational details updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/4')]);
	}

	public function save_experience($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$no_experience = $this->input->post('no_experience', true) ? 1 : 0;
		if ($no_experience) {
			$result = $this->signup->save_experience($id, [], true);
			if (!$result['success']) {
				$this->json($result, 422);
				return;
			}
			$this->json(['success' => true, 'message' => 'Experience updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/5')]);
			return;
		}

		$departments = $this->input->post('department', true);
		$sectors = $this->input->post('sector', true);
		$experience_types = $this->input->post('experience_type', true);
		$job_types = $this->input->post('job_type', true);
		$start_dates = $this->input->post('start_date', true);
		$end_dates = $this->input->post('end_date', true);
		$teaching_levels = $this->input->post('teaching_level', true);
		$bps = $this->input->post('bps', true);
		$docs = $this->input->post('document_file', true);
		$currently_pos = $this->input->post('currently_working_pos', true);

		$rows = [];
		if (is_array($departments)) {
			foreach ($departments as $i => $dep) {
				$dep = trim((string) $dep);
				$sec = trim((string) ($sectors[$i] ?? ''));
				$et = trim((string) ($experience_types[$i] ?? ''));
				$jt = trim((string) ($job_types[$i] ?? ''));
				$sd = trim((string) ($start_dates[$i] ?? ''));
				$ed = trim((string) ($end_dates[$i] ?? ''));
				$tl = trim((string) ($teaching_levels[$i] ?? ''));
				$bp = trim((string) ($bps[$i] ?? ''));
				$doc = trim((string) ($docs[$i] ?? ''));
				$cw = is_array($currently_pos) ? ((int) ($currently_pos[$i] ?? 0) === 1) : false;
				if ($dep === '' && $sec === '' && $et === '' && $jt === '' && $sd === '' && $ed === '' && $tl === '' && $bp === '' && $doc === '') continue;
				$rows[] = [
					'department' => $dep,
					'sector' => $sec,
					'experience_type' => $et,
					'job_type' => $jt,
					'teaching_level' => $tl ?: null,
					'bps' => ($sec === 'Government') ? ($bp ?: null) : null,
					'start_date' => $sd,
					'end_date' => $cw ? null : ($ed ?: null),
					'currently_working' => $cw ? 1 : 0,
					'document_file' => $doc,
				];
			}
		}

		if (empty($rows)) {
			$this->json(['success' => false, 'message' => 'Please add at least one experience record or check No Experience.'], 422);
			return;
		}

		// Validate date ranges (basic)
		foreach ($rows as $r) {
			if (empty($r['start_date'])) {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['start_date[]' => 'Start Date is required.']], 422);
				return;
			}
			if (empty($r['currently_working']) && empty($r['end_date'])) {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['end_date[]' => 'End Date is required unless Currently Working is checked.']], 422);
				return;
			}
			if (!empty($r['end_date']) && $r['start_date'] > $r['end_date']) {
				$this->json(['success' => false, 'message' => 'Please correct your experience dates.', 'errors' => ['end_date[]' => 'End Date must be greater than or equal to Start Date.']], 422);
				return;
			}
		}

		$result = $this->signup->save_experience($id, $rows, false);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Experience updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/5')]);
	}

	public function save_bank($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|required|xss_clean|max_length[150]');
		$this->form_validation->set_rules('branch_name', 'Branch Name', 'trim|required|xss_clean|max_length[150]');
		$this->form_validation->set_rules('branch_code', 'Branch Code', 'trim|required|xss_clean|max_length[50]');
		$this->form_validation->set_rules('account_title', 'Account Title', 'trim|required|xss_clean|max_length[150]');
		$this->form_validation->set_rules('iban_account_no', 'Account/IBAN Number', 'trim|required|xss_clean|max_length[50]');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$iban = strtoupper(preg_replace('/\\s+/', '', (string) post('iban_account_no')));
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

		$result = $this->signup->save_bank($id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Bank details updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/6')]);
	}

	public function save_specialization($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$this->form_validation->set_rules('specialization', 'Area of specialization', 'trim|required|xss_clean|max_length[150]');
		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$allowed = ['ENGLISH', 'URDU', 'MATH', 'SCIENCE'];
		$spec = strtoupper(trim((string) post('specialization')));
		if (!in_array($spec, $allowed, true)) {
			$this->json([
				'success' => false,
				'message' => 'Please correct the highlighted errors.',
				'errors' => ['specialization' => 'Please select a valid specialization.'],
			], 422);
			return;
		}

		$result = $this->signup->save_specialization($id, ['specialization' => $spec]);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Specialization updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/7')]);
	}

	public function save_security($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$this->form_validation->set_rules('document_type', 'Document Type', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('identification_number', 'Identification Number', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('expiry_date', 'Expiry Date', 'trim|required|xss_clean');
		$this->form_validation->set_rules('document_file', 'Upload Document', 'trim|required|xss_clean');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$password = (string) $this->input->post('password', false);
		$confirm = (string) $this->input->post('confirm_password', false);
		if ($password !== '') {
			if (strlen($password) < 6) {
				$this->json(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
				return;
			}
			if ($password !== $confirm) {
				$this->json(['success' => false, 'message' => 'Password and confirm password must match.'], 422);
				return;
			}
		}

		$data = [
			'document_type' => post('document_type'),
			'identification_number' => post('identification_number'),
			'expiry_date' => post('expiry_date'),
			'document_file' => post('document_file'),
			'password' => $password,
		];
		$result = $this->signup->save_security($id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Security setup updated.', 'next_url' => site_url('admin/emarkers/edit/' . $id . '/8')]);
	}

	public function save_emarking($id = 0)
	{
		$id = (int) $id;
		postAllowed();
		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$no_exp = $this->input->post('no_emarking_experience', true) ? 1 : 0;
		if ($no_exp) {
			$result = $this->signup->save_emarking($id, [], true);
			if (!$result['success']) {
				$this->json($result, 422);
				return;
			}
			$this->json(['success' => true, 'message' => 'E-marking experience updated.', 'next_url' => site_url('admin/emarkers/view/' . $id)]);
			return;
		}

		$deps = $this->input->post('department', true);
		$froms = $this->input->post('from_date', true);
		$tos = $this->input->post('to_date', true);

		$rows = [];
		if (is_array($deps)) {
			foreach ($deps as $i => $d) {
				$d = trim((string) $d);
				$f = trim((string) ($froms[$i] ?? ''));
				$t = trim((string) ($tos[$i] ?? ''));
				if ($d === '' && $f === '' && $t === '') continue;
				$rows[] = [
					'department' => $d,
					'from_date' => $f,
					'to_date' => $t,
				];
			}
		}

		if (empty($rows)) {
			$this->json(['success' => false, 'message' => 'Please add at least one record or check No Experience.'], 422);
			return;
		}
		foreach ($rows as $r) {
			if ($r['department'] === '' || $r['from_date'] === '' || $r['to_date'] === '') {
				$this->json(['success' => false, 'message' => 'Please fill all required fields.'], 422);
				return;
			}
			if ($r['from_date'] > $r['to_date']) {
				$this->json(['success' => false, 'message' => 'Please correct your e-marking dates.'], 422);
				return;
			}
		}

		$result = $this->signup->save_emarking($id, $rows, false);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'E-marking experience updated.', 'next_url' => site_url('admin/emarkers/view/' . $id)]);
	}

	private function get_step_data($step, $user_id)
	{
		switch ((int) $step) {
			case 1:
				return ['user' => (object) $this->signup->get_user($user_id)];
			case 2:
				return ['address' => $this->signup->get_address($user_id)];
			case 3:
				return ['educations' => $this->signup->get_educations($user_id)];
			case 4:
				$steps = $this->signup->get_steps($user_id);
				return [
					'experiences' => $this->signup->get_experiences($user_id),
					'no_experience' => $steps ? (int) $steps->no_experience : 0,
				];
			case 5:
				return ['bank' => $this->signup->get_bank($user_id)];
			case 6:
				return ['specialization' => $this->signup->get_specialization($user_id)];
			case 7:
				return [
					'security' => $this->signup->get_security($user_id),
					'user' => (object) $this->signup->get_user($user_id),
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

	public function approve($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$steps = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		if (!$steps || (int) ($steps->registration_completed ?? 0) !== 1) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Cannot approve: registration is not completed.');
			redirect('admin/emarkers/view/' . $id);
			return;
		}

		$security = $this->db->get_where('teacher_security_documents', ['user_id' => $id])->row();
		if (!$security || empty($security->document_file)) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Cannot approve: security document is missing.');
			redirect('admin/emarkers/view/' . $id);
			return;
		}

		$this->db->where('id', $id)->update('users', ['status' => 1]);
		$this->activity_model->add("Admin approved E-Marker user #{$id}", logged('id'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'E-Marker account approved and activated.');
		redirect('admin/emarkers/view/' . $id);
	}

	public function change_status($id = 0)
	{
		$id = (int) $id;
		$status = $this->input->get('status');
		$status = ($status === 'true' || $status === true || (string) $status === '1') ? 1 : 0;

		if ($id <= 0) {
			$this->output->set_output('error');
			return;
		}

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->output->set_output('error');
			return;
		}

		$this->db->where('id', $id)->update('users', ['status' => $status]);
		$this->activity_model->add("Admin changed E-Marker user #{$id} status to {$status}", logged('id'));
		$this->output->set_output('done');
	}
}
