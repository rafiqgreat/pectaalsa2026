<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	private function role_column()
	{
		if ($this->db->field_exists('role', 'users')) return 'role';
		if ($this->db->field_exists('role_id', 'users')) return 'role_id';
		return 'role';
	}

	private function parse_subjects($raw)
	{
		$raw = trim((string) $raw);
		if ($raw === '') return [];

		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			$subjects = $decoded;
		} else {
			$subjects = preg_split('/\s*,\s*/', $raw);
		}

		$subjects = array_values(array_unique(array_filter(array_map(static function ($v) {
			return strtoupper(trim((string) $v));
		}, (array) $subjects), static function ($v) {
			return $v !== '';
		})));

		return $subjects;
	}

	private function get_emarker_subject_stats(array $subjects)
	{
		$subjects = array_values(array_unique(array_filter(array_map(static function ($s) {
			return strtoupper(trim((string) $s));
		}, $subjects), static function ($s) {
			return $s !== '';
		})));

		if (empty($subjects)) {
			return [];
		}

		$role_col = $this->role_column();
		$has_review = $this->db->field_exists('review_status', 'teacher_registration_steps');

		$subject_placeholders = implode(',', array_fill(0, count($subjects), '?'));
		$params = $subjects;

		$statusSelect = $has_review
			? "s.review_status AS queue_status"
			: "CASE
					WHEN u.status = 1 THEN 'approved'
					WHEN u.status = 0 THEN 'pending'
					WHEN u.status = -1 THEN 'rejected'
					ELSE 'pending'
				END AS queue_status";

		$sql = "SELECT
			UPPER(sp.specialization) AS subject,
			{$statusSelect},
			COUNT(DISTINCT u.id) AS cnt
		FROM users u
		LEFT JOIN teacher_registration_steps s ON s.user_id = u.id
		LEFT JOIN teacher_specializations sp ON sp.user_id = u.id
		LEFT JOIN (
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
		) exp ON exp.user_id = u.id
		WHERE u.`{$role_col}` = 2
		  AND s.registration_completed = 1
		  AND UPPER(exp.sector) = 'GOVERNMENT'
		  AND UPPER(sp.specialization) IN ({$subject_placeholders})
		GROUP BY UPPER(sp.specialization), queue_status";

		$query = $this->db->query($sql, $params);
		$rows = $query ? $query->result_array() : [];

		$stats = [];
		foreach ($subjects as $subj) {
			$stats[$subj] = [
				'subject' => $subj,
				'total' => 0,
				'accepted' => 0,
				'rejected' => 0,
				'pending' => 0,
			];
		}

		foreach ($rows as $r) {
			$subject = strtoupper(trim((string) ($r['subject'] ?? '')));
			$status = strtolower(trim((string) ($r['queue_status'] ?? '')));
			$cnt = (int) ($r['cnt'] ?? 0);
			if ($subject === '' || !isset($stats[$subject])) continue;

			if ($status === 'approved') $stats[$subject]['accepted'] += $cnt;
			elseif ($status === 'rejected') $stats[$subject]['rejected'] += $cnt;
			elseif ($status === 'pending') $stats[$subject]['pending'] += $cnt;
		}

		foreach ($stats as $k => $v) {
			$stats[$k]['total'] = (int) $v['accepted'] + (int) $v['rejected'] + (int) $v['pending'];
		}

		return array_values($stats);
	}

	public function __construct()
	{
		parent::__construct();
		if (!is_logged()) {
			redirect('admin/login', 'refresh');
		}
	}

	public function index()
	{
		$role = (int) logged('role');
		if ($role === 17) {
			redirect('admin/dashboard/super_admin', 'refresh');
			return;
		}
		if ($role === 18) {
			redirect('admin/dashboard/subject_specialist', 'refresh');
			return;
		}
		if ($role === 19) {
			redirect('admin/dashboard/head_markers', 'refresh');
			return;
		}

		$this->page_data['dashboard_summary'] = $this->dashboard_model->get_summary();
		// Govt sector evaluator stats (subject-wise): ENGLISH, URDU, MATH, SCIENCE
		$this->page_data['emarker_subject_stats'] = $this->get_emarker_subject_stats(['ENGLISH', 'URDU', 'MATH', 'SCIENCE']);
		$this->load->view('admin/dashboard', $this->page_data);
	}

	private function render_role_dashboard($role_title)
	{
		$this->page_data['page']->title = $role_title . ' Dashboard';
		$this->page_data['page']->menu = 'dashboard';
		$this->page_data['user'] = $this->users_model->getById(logged('id'));
		$this->page_data['user']->role = $this->roles_model->getById(logged('role'));

		$subjects = $this->parse_subjects($this->page_data['user']->subjects ?? '');
		$this->page_data['subjects'] = $subjects;
		$this->page_data['emarker_subject_stats'] = $this->get_emarker_subject_stats($subjects);

		$this->load->view('admin/role_dashboards/self_dashboard', $this->page_data);
	}

	public function super_admin()
	{
		if ((int) logged('role') !== 17) show_404();
		$this->render_role_dashboard('Super Admin');
	}

	public function subject_specialist()
	{
		if ((int) logged('role') !== 18) show_404();
		$this->render_role_dashboard('Subject Specialist');
	}

	public function head_markers()
	{
		if ((int) logged('role') !== 19) show_404();
		$this->render_role_dashboard('Head Markers');
	}
}
