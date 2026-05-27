<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emarkers extends MY_Controller
{
	private function export_emarkers_csv($rows, $type, $filters)
	{
		// Only Admin can export
		if ((int) logged('role') !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$type = strtolower((string) $type);
		$type = in_array($type, ['approved', 'pending', 'rejected'], true) ? $type : 'pending';

		$ts = date('Ymd_His');
		$filename = "emarkers_{$type}_{$ts}.csv";

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		$out = fopen('php://output', 'w');
		if ($out === false) {
			show_error('Unable to create export output stream.', 500);
			return;
		}

		// Optional: write selected filters at top (comment-style)
		$cnic = trim((string) ($filters['cnic'] ?? ''));
		$name = trim((string) ($filters['name'] ?? ''));
		$spec = trim((string) ($filters['spec'] ?? ''));
		$qual = trim((string) ($filters['qual'] ?? ''));
		if ($cnic !== '' || $name !== '' || $spec !== '' || $qual !== '') {
			fputcsv($out, ['# Filters', 'cnic=' . $cnic, 'name=' . $name, 'spec=' . $spec, 'qual=' . $qual]);
		}

		fputcsv($out, [
			'ID',
			'Name',
			'CNIC',
			'Email',
			'Phone',
			'Specialization',
			'Sector',
			'Experience (Years)',
			'Qualification',
			'Teaching Level',
			'Active Status',
			'Queue Status',
			'Created At',
		]);

		foreach ((array) $rows as $r) {
			$active = ((int) ($r->status ?? 0) === 1) ? 'Active' : 'Inactive';
			$queueStatus = (string) (!empty($r->derived_status) ? $r->derived_status : ($type === 'approved' ? 'Approved' : ($type === 'rejected' ? 'Rejected' : 'Pending')));

			fputcsv($out, [
				(int) ($r->id ?? 0),
				(string) ($r->name ?? ''),
				(string) ($r->cnic ?? ''),
				(string) ($r->email ?? ''),
				(string) ($r->phone ?? ''),
				(string) ($r->specialization ?? ''),
				(string) ($r->sector ?? ''),
				number_format((float) ($r->total_years ?? 0), 1, '.', ''),
				(string) ($r->highest_degree ?? ''),
				(string) ($r->teaching_level ?? ''),
				$active,
				$queueStatus,
				(string) ($r->created_at ?? ''),
			]);
		}

		fclose($out);
		die;
	}

	private function get_subject_specialist_subjects()
	{
		// Subject Specialist assigned subjects are stored in `users.subjects`.
		// In this codebase it is typically JSON-encoded array (see admin/Users and admin/Dashboard),
		// but we also support legacy comma-separated strings.
		if (!$this->db->field_exists('subjects', 'users')) return [];

		$uid = (int) logged('id');
		if ($uid <= 0) return [];

		$row = $this->db->select('subjects')->get_where('users', ['id' => $uid])->row();
		$raw = trim((string) ($row->subjects ?? ''));
		if ($raw === '') return [];

		$subjects = [];
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			$subjects = $decoded;
		} else {
			$subjects = preg_split('/\s*,\s*/', $raw);
		}

		$subjects = array_values(array_unique(array_filter(array_map('trim', (array) $subjects), static function ($v) {
			return $v !== '';
		})));
		return $subjects;
	}

	private function subject_specialist_can_access_method($method)
	{
		// Subject Specialist can only browse and view evaluator profiles.
		return in_array((string) $method, ['index', 'pending', 'approved', 'rejected', 'view', 'approve', 'reject', 'seek_information'], true);
	}

	private function role_column()
	{
		if ($this->db->field_exists('role', 'users')) return 'role';
		if ($this->db->field_exists('role_id', 'users')) return 'role_id';
		return 'role';
	}

	private function ss_can_manage_emarker($id)
	{
		$id = (int) $id;
		if ($id <= 0) return false;
		if ((int) logged('role') !== 18) return false;

		$subjects = $this->get_subject_specialist_subjects();
		$allowed = array_map('strtoupper', (array) $subjects);
		if (empty($allowed)) return false;

		$spec_row = $this->db->select('specialization')->get_where('teacher_specializations', ['user_id' => $id])->row();
		$eval_spec = trim((string) ($spec_row->specialization ?? ''));
		if ($eval_spec === '') return false;

		return in_array(strtoupper($eval_spec), $allowed, true);
	}

	private function get_emarker_user($id)
	{
		$role_col = $this->role_column();
		return $this->db->get_where('users', ['id' => (int) $id, $role_col => 2])->row();
	}

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
		// Admin (role 1) and Subject Specialist (role 18) can access this controller.
		$role = (int) logged('role');
		if (!in_array($role, [1, 18], true)) {
			redirect('errors/permission_denied');
			die;
		}

		// Subject Specialist must be limited to non-destructive methods.
		if ($role === 18 && !$this->subject_specialist_can_access_method($this->router->fetch_method())) {
			redirect('errors/permission_denied');
			die;
		}

		$this->page_data['page']->title = 'E-Markers';
		$this->page_data['page']->menu = 'emarkers';
		$this->page_data['page']->submenu = 'pending';
		$this->load->model('Signup_model', 'signup');
	}

	public function index($type = 'pending')
	{
		$type = strtolower((string) $type);
		$type = in_array($type, ['approved', 'pending', 'rejected'], true) ? $type : 'pending';
		$this->page_data['page']->submenu = $type;

		// Ensure a clean query builder state for the listing query.
		$this->db->reset_query();

		$cnic = trim((string) $this->input->get('cnic', true));
		$name = trim((string) $this->input->get('name', true));
		// Legacy filter (kept for backward compatibility with older links)
		$q = trim((string) $this->input->get('q', true));
		if ($cnic === '' && $name === '' && $q !== '') {
			$cnic = $q;
		}
		$spec = trim((string) $this->input->get('spec', true));
		$qual = trim((string) $this->input->get('qual', true));
		$sort = (string) $this->input->get('sort', true);
		$dir = strtolower((string) $this->input->get('dir', true));
		$dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';
		if ($sort !== 'exp') $sort = '';

		$role_col = $this->role_column();
		$has_review = $this->db->field_exists('review_status', 'teacher_registration_steps');
		$review_select = $has_review
			? "s.review_status, s.rejection_reason, s.rejected_at, s.updated_at AS steps_updated_at,
			CASE WHEN s.rejected_at IS NOT NULL AND s.updated_at > s.rejected_at THEN 1 ELSE 0 END AS is_resubmission,"
			: "'' AS review_status, NULL AS rejection_reason, NULL AS rejected_at, s.updated_at AS steps_updated_at, 0 AS is_resubmission,";

		// NOTE: Do NOT execute any other DB queries after we start building the listing query below.
		// CodeIgniter's Query Builder state is reset after queries, which would wipe SELECT/FROM/JOIN
		// and result in "No tables used".
		$ss_subjects = null;
		if ((int) logged('role') === 18) {
			$ss_subjects = $this->get_subject_specialist_subjects();
			// UX/debug hint (rendered in the view, not flashdata): show what subjects are applied for SS filtering.
			// This does not reveal any evaluator data, only the SS's own assigned subjects.
			$this->page_data['ss_subjects_filter'] = is_array($ss_subjects) ? $ss_subjects : [];
		}

		// Base query
		// Build listing query as raw SQL to avoid Query Builder state corruption ("No tables used").
		$where = [];
		$params = [];

		$where[] = 'u.`' . $role_col . '` = ?';
		$params[] = 2;

		// Registration must be completed to appear in queues
		$where[] = 's.registration_completed = ?';
		$params[] = 1;

		// Filter by request type (review_status)
		if ($has_review) {
			$where[] = 's.review_status = ?';
			$params[] = $type;
		} else {
			// Backward compatible fallback: pending=inactive, approved=active, rejected=none
			if ($type === 'approved') { $where[] = 'u.status = ?'; $params[] = 1; }
			if ($type === 'pending')  { $where[] = 'u.status = ?'; $params[] = 0; }
			if ($type === 'rejected') { $where[] = 'u.status = ?'; $params[] = -1; }
		}

		if ($cnic !== '') {
			$where[] = 'u.cnic LIKE ?';
			$params[] = '%' . $cnic . '%';
		}
		if ($name !== '') {
			$where[] = 'u.name LIKE ?';
			$params[] = '%' . $name . '%';
		}
		if ($spec !== '') {
			$where[] = 'sp.specialization = ?';
			$params[] = $spec;
		}
		if ($qual !== '') {
			$where[] = 'edu.highest_degree = ?';
			$params[] = $qual;
		}

		// Only show Govt teachers (sector must be Government)
		$where[] = "UPPER(exp.sector) = 'GOVERNMENT'";

		// IMPORTANT ACCESS RULE:
		// Subject Specialist (role 18) can only see evaluator records where evaluator's
		// `teacher_specializations.specialization` matches any subject assigned in SS `users.subjects`.
		if ((int) logged('role') === 18) {
			$subjects = is_array($ss_subjects) ? $ss_subjects : [];
			if (empty($subjects)) {
				$where[] = '1 = 0';
			} else {
				$placeholders = implode(',', array_fill(0, count($subjects), '?'));
				$where[] = 'UPPER(sp.specialization) IN (' . $placeholders . ')';
				foreach ($subjects as $subj) {
					$params[] = strtoupper((string) $subj);
				}
			}
		}

		$orderBy = 'u.id DESC';
		if ($sort === 'exp') {
			$orderBy = 'exp.total_years ' . ($dir === 'desc' ? 'DESC' : 'ASC');
		}

		$sis_select = $this->db->field_exists('sis_verified', 'users') ? 'u.sis_verified' : '0 AS sis_verified';

		$sql = "SELECT
			u.id, u.name, u.email, u.phone, u.cnic, {$sis_select}, u.status, u.created_at,
			s.registration_completed, {$review_select}
			sp.specialization,
			edu.highest_degree,
			exp.total_years,
			exp.sector,
			exp.teaching_level
		FROM users u
		LEFT JOIN teacher_registration_steps s ON s.user_id = u.id
		LEFT JOIN teacher_specializations sp ON sp.user_id = u.id
		LEFT JOIN (
			SELECT e.user_id,
				(
					SELECT e2.degree
					FROM teacher_educations e2
					WHERE e2.user_id = e.user_id
					ORDER BY
						CASE
							WHEN e2.degree = 'PhD' THEN 6
							WHEN e2.degree = 'MPhil. / MS (18 years)' THEN 5
							WHEN e2.degree = 'Master / M.A/ MSc./ BS (Hons) (16 years)' THEN 4
							WHEN e2.degree = 'B.A / BSc. (14 years)' THEN 3
							WHEN e2.degree = 'HSSC' THEN 2
							WHEN e2.degree = 'SSC' THEN 1
							ELSE 0
						END DESC,
						e2.passing_year DESC,
						e2.id DESC
					LIMIT 1
				) AS highest_degree
			FROM teacher_educations e
			GROUP BY e.user_id
		) edu ON edu.user_id = u.id
		LEFT JOIN (
			SELECT x.user_id,
				ROUND(SUM(DATEDIFF(COALESCE(x.end_date, CURDATE()), x.start_date)) / 365.25, 1) AS total_years,
				(
					SELECT x2.sector
					FROM teacher_experiences x2
					WHERE x2.user_id = x.user_id
					  AND x2.sector IS NOT NULL
					  AND x2.sector <> ''
					ORDER BY COALESCE(x2.end_date, CURDATE()) DESC, x2.id DESC
					LIMIT 1
				) AS sector,
				(
					SELECT x2.teaching_level
					FROM teacher_experiences x2
					WHERE x2.user_id = x.user_id
					  AND x2.teaching_level IS NOT NULL
					  AND x2.teaching_level <> ''
					ORDER BY COALESCE(x2.end_date, CURDATE()) DESC, x2.id DESC
					LIMIT 1
				) AS teaching_level
			FROM teacher_experiences x
			GROUP BY x.user_id
		) exp ON exp.user_id = u.id
		WHERE " . implode(' AND ', $where) . "
		ORDER BY {$orderBy}";

		$query = $this->db->query($sql, $params);
		if ($query === false) {
			$err = $this->db->error();
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata(
				'alert',
				'Evaluator list query failed: ' . htmlspecialchars((string) ($err['message'] ?? 'Unknown error'))
			);
			$rows = [];
		} else {
			$rows = $query->result();
		}

		// Keep SS UI minimal: only show applied subject filter (see view).

		// Build dropdown options (include defaults even if no records exist yet)
		$spec_opts = $this->signup->get_specialization_options();
		$qual_opts = $this->signup->get_degree_options();

		// Add derived status label (Fresh/Resubmission) for pending list
		foreach ($rows as $r) {
			$r->derived_status = '';
			if ($type === 'pending') {
				$r->derived_status = !empty($r->is_resubmission) ? 'Resubmission' : 'Fresh';
			} elseif ($type === 'approved') {
				$r->derived_status = 'Approved';
			} elseif ($type === 'rejected') {
				$r->derived_status = 'Rejected';
			}
			if (empty($r->total_years)) $r->total_years = 0.0;
			if (empty($r->sector)) $r->sector = '---';
			if (empty($r->teaching_level)) $r->teaching_level = '---';
			if ((string) $r->teaching_level === 'SSC/HSSC') $r->teaching_level = 'Secondary';
		}

		// Export CSV (Admin only): keeps the same filters currently applied.
		$export = strtolower(trim((string) $this->input->get('export', true)));
		if ($export === 'csv') {
			$this->export_emarkers_csv($rows, $type, [
				'type' => $type,
				'cnic' => $cnic,
				'name' => $name,
				'spec' => $spec,
				'qual' => $qual,
				'sort' => $sort,
				'dir' => $dir,
			]);
			return;
		}

		$this->page_data['emarkers'] = $rows;
		$this->page_data['filters'] = [
			'type' => $type,
			'cnic' => $cnic,
			'name' => $name,
			'q' => $q,
			'spec' => $spec,
			'qual' => $qual,
			'sort' => $sort,
			'dir' => $dir,
			'spec_opts' => $spec_opts,
			'qual_opts' => $qual_opts,
		];
		$this->load->view('admin/emarkers/list', $this->page_data);
	}

	public function pending()
	{
		$this->index('pending');
	}

	public function approved()
	{
		$this->index('approved');
	}

	public function rejected()
	{
		$this->index('rejected');
	}

	public function view($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->get_emarker_user($id);
		if (!$user) show_404();

		// Subject Specialist is only allowed to view evaluator profiles within assigned subjects.
		if ((int) logged('role') === 18) {
			$subjects = $this->get_subject_specialist_subjects();
			$spec_row = $this->db->select('specialization')->get_where('teacher_specializations', ['user_id' => $id])->row();
			$eval_spec = trim((string) ($spec_row->specialization ?? ''));
			$allowed = array_map('strtoupper', $subjects);
			if ($eval_spec === '' || empty($allowed) || !in_array(strtoupper($eval_spec), $allowed, true)) {
				redirect('errors/permission_denied');
				die;
			}
		}

		$this->page_data['page']->title = 'E-Marker Profile';
		$this->page_data['page']->menu = 'emarkers';

		$this->page_data['user_row'] = $user;
		$steps_row = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		$this->page_data['steps_row'] = $steps_row;
		$sub = 'pending';
		if ($steps_row && !empty($steps_row->review_status)) {
			$sub = (string) $steps_row->review_status;
		} else if ((int) ($user->status ?? 0) === 1) {
			$sub = 'approved';
		}
		$this->page_data['page']->submenu = $sub;
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

		$user = $this->get_emarker_user($id);
		if (!$user) show_404();

		$this->page_data['page']->title = 'Change E-Marker Password';
		$this->page_data['page']->menu = 'emarkers';
		$steps_row = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		$this->page_data['page']->submenu = ($steps_row && !empty($steps_row->review_status)) ? (string) $steps_row->review_status : 'pending';
		$this->page_data['user_row'] = $user;
		$this->load->view('admin/emarkers/change_password', $this->page_data);
	}

	public function update_password($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();
		postAllowed();

		$user = $this->get_emarker_user($id);
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

		$user = $this->get_emarker_user($id);
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
		$steps_row2 = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		$this->page_data['page']->submenu = ($steps_row2 && !empty($steps_row2->review_status)) ? (string) $steps_row2->review_status : 'pending';
		$this->page_data['wizard'] = $data;
		$this->load->view('admin/emarkers/edit_wizard', $this->page_data);
	}

	public function upload_file($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->get_emarker_user($id);
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
		$user = $this->get_emarker_user($id);
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

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

		$cnic_raw = (string) post('cnic');
		$cnic_digits = preg_replace('/\\D+/', '', $cnic_raw);
		if (strlen($cnic_digits) !== 13) {
			$this->json(['success' => false, 'message' => 'Invalid CNIC.', 'errors' => ['cnic' => 'CNIC must be 13 digits.']], 422);
			return;
		}
		$cnic_fmt = substr($cnic_digits, 0, 5) . '-' . substr($cnic_digits, 5, 7) . '-' . substr($cnic_digits, 12, 1);

		$dob = (string) post('dob');
		try {
			$birth = new DateTime($dob);
			$cutoff = (clone $birth)->modify('+18 years');
			$today = new DateTime(date('Y-m-d'));
			if ($cutoff > $today) {
				$this->json([
					'success' => false,
					'message' => 'Please correct the highlighted errors.',
					'errors' => ['dob' => 'User must be at least 18 years old.'],
				], 422);
				return;
			}
		} catch (Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Please correct the highlighted errors.',
				'errors' => ['dob' => 'Invalid Date Of Birth.'],
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
		$user = $this->get_emarker_user($id);
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
		$user = $this->get_emarker_user($id);
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

		$required_degree_16 = 'Master / M.A/ MSc./ BS (Hons) (16 years)';
		$required_degree_hssc = 'HSSC';
		$required_degree_ssc = 'SSC';
		$has_16 = false;
		$has_hssc = false;
		$has_ssc = false;

		foreach ($rows as $idx => $r) {
			if ($r['degree'] === '') {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['degree[]' => 'Degree is required.']], 422);
				return;
			}
			if ((string) $r['degree'] === $required_degree_16) $has_16 = true;
			if ((string) $r['degree'] === $required_degree_hssc) $has_hssc = true;
			if ((string) $r['degree'] === $required_degree_ssc) $has_ssc = true;
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
		$user = $this->get_emarker_user($id);
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
					'teaching_level' => $tl,
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
			if (empty($r['teaching_level'])) {
				$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => ['teaching_level[]' => 'Teaching Level is required.']], 422);
				return;
			}
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
		$user = $this->get_emarker_user($id);
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
		$user = $this->get_emarker_user($id);
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$this->form_validation->set_rules('specialization', 'Area of specialization', 'trim|required|xss_clean|max_length[150]');
		if ($this->form_validation->run() === false) {
			$this->json(['success' => false, 'message' => 'Please correct the highlighted errors.', 'errors' => $this->form_validation->error_array()], 422);
			return;
		}

		$spec = strtoupper(trim((string) post('specialization')));

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
		$user = $this->get_emarker_user($id);
		if (!$user) {
			$this->json(['success' => false, 'message' => 'Invalid user.'], 404);
			return;
		}

		$this->form_validation->set_rules('document_type', 'Document Type', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('identification_number', 'Identification Number', 'trim|required|xss_clean|max_length[100]');
		$this->form_validation->set_rules('expiry_date', 'Expiry Date', 'trim|required|xss_clean');
		$this->form_validation->set_rules('document_file', 'Upload Document', 'trim|required|xss_clean');
		$this->form_validation->set_rules('integrity_affidavit_file', 'Integrity Affidavit', 'trim|required|xss_clean');

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
			'integrity_affidavit_file' => post('integrity_affidavit_file'),
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
		$user = $this->get_emarker_user($id);
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
		$role = (int) logged('role');
		if ($role !== 1 && $role !== 18) {
			redirect('errors/permission_denied');
			die;
		}
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->get_emarker_user($id);
		if (!$user) show_404();

		if ($role === 18 && !$this->ss_can_manage_emarker($id)) {
			redirect('errors/permission_denied');
			die;
		}

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
		if ($this->db->table_exists('teacher_registration_steps') && $this->db->field_exists('review_status', 'teacher_registration_steps')) {
			// Update only this user's steps row
			$update = [
				'review_status' => 'approved',
			];
			if ($this->db->field_exists('approved_at', 'teacher_registration_steps')) {
				$update['approved_at'] = date('Y-m-d H:i:s');
			}
			if ($this->db->field_exists('rejected_at', 'teacher_registration_steps')) {
				$update['rejected_at'] = null;
			}
			if ($this->db->field_exists('rejection_reason', 'teacher_registration_steps')) {
				$update['rejection_reason'] = null;
			}
			if ($this->db->field_exists('reviewed_by', 'teacher_registration_steps')) {
				$update['reviewed_by'] = (int) logged('id');
			}
			$this->db->where('user_id', $id)->update('teacher_registration_steps', $update);
		}
		$who = ($role === 18) ? 'Subject Specialist' : 'Admin';
		$this->activity_model->add("{$who} approved E-Marker user #{$id}", logged('id'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'E-Marker account approved and activated.');
		redirect('admin/emarkers/view/' . $id);
	}

	public function reject($id = 0)
	{
		$role = (int) logged('role');
		if ($role !== 1 && $role !== 18) {
			redirect('errors/permission_denied');
			die;
		}
		$id = (int) $id;
		if ($id <= 0) show_404();
		postAllowed();

		$user = $this->get_emarker_user($id);
		if (!$user) show_404();

		if ($role === 18 && !$this->ss_can_manage_emarker($id)) {
			redirect('errors/permission_denied');
			die;
		}

		$reason = trim((string) $this->input->post('reason', true));
		if ($reason === '') {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Rejection reason is required.');
			redirect('admin/emarkers/view/' . $id);
			return;
		}

		if ($this->db->table_exists('teacher_registration_steps') && $this->db->field_exists('review_status', 'teacher_registration_steps')) {
			// Update only this user's steps row
			$update = [
				'review_status' => 'rejected',
			];
			if ($this->db->field_exists('rejection_reason', 'teacher_registration_steps')) {
				$update['rejection_reason'] = $reason;
			}
			if ($this->db->field_exists('rejected_at', 'teacher_registration_steps')) {
				$update['rejected_at'] = date('Y-m-d H:i:s');
			}
			if ($this->db->field_exists('approved_at', 'teacher_registration_steps')) {
				$update['approved_at'] = null;
			}
			if ($this->db->field_exists('reviewed_by', 'teacher_registration_steps')) {
				$update['reviewed_by'] = (int) logged('id');
			}
			$this->db->where('user_id', $id)->update('teacher_registration_steps', $update);
		}
		// Keep account inactive until approved.
		$this->db->where('id', $id)->update('users', ['status' => 0]);

		$who = ($role === 18) ? 'Subject Specialist' : 'Admin';
		$this->activity_model->add("{$who} rejected E-Marker user #{$id}", logged('id'));
		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Request rejected.');
		redirect('admin/emarkers/view/' . $id);
	}

	public function seek_information($id = 0)
	{
		$role = (int) logged('role');
		if ($role !== 1 && $role !== 18) {
			redirect('errors/permission_denied');
			die;
		}
		$id = (int) $id;
		if ($id <= 0) show_404();
		postAllowed();

		$user = $this->get_emarker_user($id);
		if (!$user) show_404();

		if ($role === 18 && !$this->ss_can_manage_emarker($id)) {
			redirect('errors/permission_denied');
			die;
		}

		$note = trim((string) $this->input->post('note', true));
		if ($note === '') {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Message is required.');
			redirect('admin/emarkers/view/' . $id);
			return;
		}

		if ($this->db->table_exists('teacher_registration_steps') && $this->db->field_exists('review_status', 'teacher_registration_steps')) {
			// Update only this user's steps row
			$update = [
				'review_status' => 'pending',
			];
			if ($this->db->field_exists('review_notes', 'teacher_registration_steps')) {
				$update['review_notes'] = $note;
			}
			if ($this->db->field_exists('reviewed_by', 'teacher_registration_steps')) {
				$update['reviewed_by'] = (int) logged('id');
			}
			$this->db->where('user_id', $id)->update('teacher_registration_steps', $update);
		}

		$who = ($role === 18) ? 'Subject Specialist' : 'Admin';
		$this->activity_model->add("{$who} requested information from E-Marker user #{$id}", logged('id'));
		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Information request saved.');
		redirect('admin/emarkers/view/' . $id);
	}

	public function change_status($id = 0)
	{
		if ((int) logged('role') !== 1) {
			$this->output->set_output('error');
			return;
		}
		$id = (int) $id;
		$status = $this->input->get('status');
		$status = ($status === 'true' || $status === true || (string) $status === '1') ? 1 : 0;

		if ($id <= 0) {
			$this->output->set_output('error');
			return;
		}

		$user = $this->get_emarker_user($id);
		if (!$user) {
			$this->output->set_output('error');
			return;
		}

		$this->db->where('id', $id)->update('users', ['status' => $status]);
		$this->activity_model->add("Admin changed E-Marker user #{$id} status to {$status}", logged('id'));
		$this->output->set_output('done');
	}
}
