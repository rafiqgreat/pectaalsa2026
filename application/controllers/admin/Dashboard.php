<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
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
		$this->load->view('admin/dashboard', $this->page_data);
	}

	private function render_role_dashboard($role_title)
	{
		$this->page_data['page']->title = $role_title . ' Dashboard';
		$this->page_data['page']->menu = 'dashboard';
		$this->page_data['user'] = $this->users_model->getById(logged('id'));
		$this->page_data['user']->role = $this->roles_model->getById(logged('role'));

		$subjects = [];
		if (!empty($this->page_data['user']->subjects)) {
			$decoded = json_decode((string) $this->page_data['user']->subjects, true);
			if (is_array($decoded)) $subjects = $decoded;
		}
		$this->page_data['subjects'] = $subjects;

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
