<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Profile extends MY_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'Profile Management';
		$this->page_data['page']->menu = 'evaluator_profile';
		$this->load->model('Signup_model', 'signup');
	}

	public function index($tab = null)
	{
		// Backward compatibility: keep password/picture tabs accessible.
		if (in_array((string) $tab, ['change_password', 'change_pic'], true)) {
			if ((string) $tab === 'change_password') {
				$this->page_data['page']->menu = 'change_password';
				$this->page_data['page']->title = 'Change Password';
				$this->page_data['user'] = $this->users_model->getById(logged('id'));
				$this->page_data['user']->role = $this->roles_model->getById(logged('role'));
				$this->load->view('user/account/change_password', $this->page_data);
				return;
			}
			$this->page_data['user'] = $this->users_model->getById(logged('id'));
			$this->page_data['user']->role = $this->roles_model->getById(logged('role'));
			$this->page_data['activeTab'] = (string) $tab;
			$this->load->view('user/account/profile', $this->page_data);
			return;
		}

		redirect('user/profile/step/1', 'refresh');
	}

	public function change_password()
	{
		redirect('user/profile/index/change_password', 'refresh');
	}

	public function change_pic()
	{
		redirect('user/profile/index/change_pic', 'refresh');
	}

	public function step($step = 1)
	{
		$step = (int) $step;
		if ($step < 1 || $step > 8) {
			show_404();
		}

		$user_id = (int) logged('id');
		$steps = $this->signup->get_steps($user_id);

		$step_titles = [
			1 => 'Personal Information',
			2 => 'Address Details',
			3 => 'Educational Details',
			4 => 'Experience Details',
			5 => 'Bank Detail',
			6 => 'Area of Specialization',
			7 => 'Security Setup',
			8 => 'Emarking Experience',
		];
		$step_keys = [
			1 => 'personal',
			2 => 'address',
			3 => 'education',
			4 => 'experience',
			5 => 'bank',
			6 => 'specialization',
			7 => 'security',
			8 => 'emarking',
		];

		$view_map = [
			1 => 'user/profile_wizard/steps/personal',
			2 => 'signup/steps/address',
			3 => 'signup/steps/education',
			4 => 'signup/steps/experience',
			5 => 'signup/steps/bank',
			6 => 'signup/steps/specialization',
			7 => 'user/profile_wizard/steps/security',
			8 => 'signup/steps/emarking',
		];

		$action_map = [
			1 => site_url('user/profile/save_personal'),
			2 => site_url('user/profile/save_address'),
			3 => site_url('user/profile/save_education'),
			4 => site_url('user/profile/save_experience'),
			5 => site_url('user/profile/save_bank'),
			6 => site_url('user/profile/save_specialization'),
			7 => site_url('user/profile/save_security'),
			8 => site_url('user/profile/save_emarking'),
		];

		$form_data = [];
		switch ($step) {
			case 1:
				$form_data = ['user' => $this->signup->get_user($user_id)];
				break;
			case 2:
				$form_data = ['address' => $this->signup->get_address($user_id)];
				break;
			case 3:
				$form_data = ['educations' => $this->signup->get_educations($user_id)];
				break;
			case 4:
				$form_data = [
					'experiences' => $this->signup->get_experiences($user_id),
					'no_experience' => $steps ? (int) $steps->no_experience : 0,
				];
				break;
			case 5:
				$form_data = ['bank' => $this->signup->get_bank($user_id)];
				break;
			case 6:
				$form_data = ['specialization' => $this->signup->get_specialization($user_id)];
				break;
			case 7:
				$form_data = [
					'security' => $this->signup->get_security($user_id),
					'user' => $this->signup->get_user($user_id),
				];
				break;
			case 8:
				$form_data = [
					'emarking' => $this->signup->get_emarking($user_id),
					'no_emarking_experience' => $steps ? (int) $steps->no_emarking_experience : 0,
				];
				break;
		}

		$this->page_data['assets'] = assets_url();
		$this->page_data['wizard_base'] = 'user/profile';
		$this->page_data['step'] = $step;
		$this->page_data['step_titles'] = $step_titles;
		$this->page_data['step_keys'] = $step_keys;
		$this->page_data['steps_row'] = $steps;
		$this->page_data['step_view'] = $view_map[$step];
		$this->page_data['form_action'] = $action_map[$step];
		$this->page_data['form_data'] = $form_data;

		$this->load->view('user/profile_wizard/step_layout', $this->page_data);
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

	public function upload_file()
	{
		if (empty($_FILES['file'])) {
			$this->json(['success' => false, 'message' => 'No file uploaded.'], 422);
			return;
		}

		$user_id = (int) logged('id');
		$relative_dir = 'uploads/teacher_registration/' . $user_id . '/';
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
			'file_name' => $info['client_name'],
			'file_path' => $relative_path,
		]);
	}

	public function delete_file()
	{
		$relative_path = (string) $this->input->post('file_path', true);
		$relative_path = str_replace(['\\', '//'], ['/', '/'], $relative_path);
		if ($relative_path === '' || strpos($relative_path, '..') !== false) {
			$this->json(['success' => false, 'message' => 'Invalid file path.'], 422);
			return;
		}

		$user_id = (int) logged('id');
		$allowed = (strpos($relative_path, 'uploads/teacher_registration/' . $user_id . '/') === 0);
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
		$user_id = (int) logged('id');
		$current = $this->signup->get_user($user_id);

		$this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('father_name', 'Father Name', 'trim|required|max_length[150]|xss_clean');
		$this->form_validation->set_rules('blood_group', 'Blood Group', 'trim|required|max_length[10]|xss_clean');
		$this->form_validation->set_rules('gender', 'Gender', 'trim|required|in_list[Male,Female,Other]|xss_clean');
		$this->form_validation->set_rules('phone', 'Phone Number', 'trim|required|max_length[30]|xss_clean');
		$this->form_validation->set_rules('dob', 'Date Of Birth', 'trim|required|xss_clean');
		$this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email|max_length[150]|xss_clean');
		$this->form_validation->set_rules('cnic', 'CNIC', 'trim|required|regex_match[/^(\\d{13}|\\d{5}-\\d{7}-\\d)$/]|xss_clean');
		$this->form_validation->set_rules('employee_no', 'Personal No/Employee Id', 'trim|required|max_length[100]|xss_clean');

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		// CNIC is read-only; prevent changing.
		$cnic_digits = preg_replace('/\\D+/', '', (string) post('cnic'));
		$cnic_fmt = substr($cnic_digits, 0, 5) . '-' . substr($cnic_digits, 5, 7) . '-' . substr($cnic_digits, 12, 1);
		if (!empty($current->cnic) && (string) $current->cnic !== (string) $cnic_fmt) {
			$this->json(['success' => false, 'message' => 'CNIC cannot be changed.'], 422);
			return;
		}

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
		$result = $this->signup->save_personal($user_id, $payload, $profile_path, false);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}

		$this->json(['success' => true, 'message' => 'Profile updated.', 'next_url' => site_url('user/profile/step/2')]);
	}

	public function save_address()
	{
		postAllowed();
		$user_id = (int) logged('id');

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
		$this->json(['success' => true, 'message' => 'Address updated.', 'next_url' => site_url('user/profile/step/3')]);
	}

	public function save_education()
	{
		postAllowed();
		$user_id = (int) logged('id');

		$degrees = (array) $this->input->post('degree', true);
		$institutes = (array) $this->input->post('institute', true);
		$years = (array) $this->input->post('passing_year', true);
		$cgpas = (array) $this->input->post('cgpa_percentage', true);
		$files = (array) $this->input->post('transcript_file', true);

		if (count($degrees) < 1) {
			$this->json(['success' => false, 'message' => 'Please add at least one education record.'], 422);
			return;
		}

		$rows = [];
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
			$rows[] = $row;
		}

		$result = $this->signup->save_education($user_id, $rows);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Education updated.', 'next_url' => site_url('user/profile/step/4')]);
	}

	public function save_experience()
	{
		postAllowed();
		$user_id = (int) logged('id');

		$no_experience = $this->input->post('no_experience', true) ? 1 : 0;
		if ($no_experience) {
			$result = $this->signup->save_experience($user_id, [], true);
			if (!$result['success']) {
				$this->json($result, 422);
				return;
			}
			$this->json(['success' => true, 'message' => 'Experience updated.', 'next_url' => site_url('user/profile/step/5')]);
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
				'teaching_level' => trim((string) ($teaching_levels[$i] ?? '')) ?: null,
				'bps' => ($sector === 'Government') ? (trim((string) ($bps[$i] ?? '')) ?: null) : null,
				'document_file' => trim((string) ($docs[$i] ?? '')),
			];
			if ($row['department'] === '' || $row['sector'] === '' || $row['experience_type'] === '' || $row['job_type'] === '' || $row['start_date'] === '' || $row['document_file'] === '') {
				$this->json(['success' => false, 'message' => 'All experience fields and uploads are required.'], 422);
				return;
			}
			if (!$this->is_valid_ymd($row['start_date'])) {
				$this->json(['success' => false, 'message' => 'Invalid Start Date.'], 422);
				return;
			}
			if (!$currently && empty($row['end_date'])) {
				$this->json(['success' => false, 'message' => 'End Date is required unless Currently Working is checked.'], 422);
				return;
			}
			if (!$currently) {
				if (!$this->is_valid_ymd($row['end_date'])) {
					$this->json(['success' => false, 'message' => 'Invalid End Date.'], 422);
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
		$this->json(['success' => true, 'message' => 'Experience updated.', 'next_url' => site_url('user/profile/step/5')]);
	}

	public function save_bank()
	{
		postAllowed();
		$user_id = (int) logged('id');

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
		$this->json(['success' => true, 'message' => 'Bank details updated.', 'next_url' => site_url('user/profile/step/6')]);
	}

	public function save_specialization()
	{
		postAllowed();
		$user_id = (int) logged('id');

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

		$result = $this->signup->save_specialization($user_id, ['specialization' => $spec]);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Specialization updated.', 'next_url' => site_url('user/profile/step/7')]);
	}

	public function save_security()
	{
		postAllowed();
		$user_id = (int) logged('id');

		$this->form_validation->set_rules('document_type', 'Document Type', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('identification_number', 'Identification Number', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('expiry_date', 'Expiry Date', 'trim|required|xss_clean');
		$this->form_validation->set_rules('document_file', 'Upload Document', 'trim|required|xss_clean');

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

		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$data = [
			'document_type' => post('document_type'),
			'identification_number' => post('identification_number'),
			'expiry_date' => post('expiry_date'),
			'document_file' => post('document_file'),
			'password' => $password,
		];

		$result = $this->signup->save_security($user_id, $data);
		if (!$result['success']) {
			$this->json($result, 422);
			return;
		}
		$this->json(['success' => true, 'message' => 'Security updated.', 'next_url' => site_url('user/profile/step/8')]);
	}

	public function save_emarking()
	{
		postAllowed();
		$user_id = (int) logged('id');

		$no_exp = $this->input->post('no_emarking_experience', true) ? 1 : 0;
		if ($no_exp) {
			$result = $this->signup->save_emarking($user_id, [], true);
			if (!$result['success']) {
				$this->json($result, 422);
				return;
			}
			$this->json(['success' => true, 'message' => 'Profile updated.', 'next_url' => site_url('user/evaluator_profile')]);
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
		$this->json(['success' => true, 'message' => 'Profile updated.', 'next_url' => site_url('user/evaluator_profile')]);
	}
	public function updateProfile()
	{
		$id = logged('id');
		
		postAllowed();
		$data = [
			'role' => post('role'),
			'name' => post('name'),
			'username' => post('username'),
			'email' => post('email'),
			'phone' => post('contact'),
			'address' => post('address'),
		];
		$id = $this->users_model->update($id, $data);
		$this->activity_model->add("User #$id updated the profile");
		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Profile has been Updated Successfully');
		
		redirect('user/profile/index/edit');
	}
	public function updatePassword()
	{
		$id = logged('id');
		
		postAllowed();
		if ( post('password') !== post('password_confirm') ) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Password does not matches with Confirm Password !');
			redirect('user/profile/index/change_password');
		}
		
		if ( strlen(post('password')) < 6 ) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Password must have atleast 6 Characters');
			redirect('user/profile/index/change_password');
		}

		$stored_hash = (string) $this->users_model->getRowById($id, 'password');
		$old_ok = false;
		if ($stored_hash !== '' && strpos($stored_hash, '$') === 0) {
			$old_ok = password_verify((string) post('old_password'), $stored_hash);
		} else {
			$old_ok = (hash('sha256', (string) post('old_password')) === $stored_hash);
		}

		if (!$old_ok) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Invalid Old Password !');
			redirect('user/profile/index/change_password');
		}

		$password = post('password');
		$data['password'] = password_hash((string) $password, PASSWORD_DEFAULT);
		$id = $this->users_model->update($id, $data);
		$this->activity_model->add("User #$id changed the password !");

		// Force logout and ask user to login again with new password.
		$this->load->model('user/Users_model', 'user_users_model');
		$this->user_users_model->logout();

		$this->session->set_flashdata('message_type', 'success');
		$this->session->set_flashdata('message', 'Password changed successfully. Please login again with your new password.');
		redirect('user/login', 'refresh');
	}
	public function updateProfilePic()
	{
		$id = logged('id');
		
		if (!empty($_FILES['image']['name'])) {
			$path = $_FILES['image']['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$this->uploadlib->initialize([
				'file_name' => $id.'.'.$ext
			]);
			$image = $this->uploadlib->uploadImage('image', '/users');
			if($image['status']){
				$this->users_model->update($id, ['img_type' => $ext]);
			}
			$this->activity_model->add("User #$id Updated his/her Profile Image.");
			$this->session->set_flashdata('alert-type', 'success');
			$this->session->set_flashdata('alert', 'Profile Image has been Updated Successfully');
		}
		else{
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Server Error Occured while Uploading Image !');
		}
		redirect('user/profile/index/change_pic');
	}
	public function change_language($code = '')
	{
		// $this->lang->load('basic', 'spanish');
		// die(var_dump( $this->lang->language ));
		setUserlang($code);
		redirect(!empty($_REQUEST['back']) ? urldecode($_REQUEST['back']) : '' );
	}

}
/* End of file Profile.php */
/* Location: ./application/controllers/Profile.php */
