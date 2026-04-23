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
		$this->page_data['dashboard_summary'] = $this->dashboard_model->get_summary();
		$this->load->view('admin/dashboard', $this->page_data);
	}
}
