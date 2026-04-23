<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		if (!is_logged()) {
			redirect('user/login', 'refresh');
		}
	}

	public function index()
	{
		$user_id = $this->session->userdata('logged')['id'];
		$this->load->model('user/Users_model');
		$this->page_data['user'] = $this->Users_model->get($user_id);
		$this->load->view('user/dashboard', $this->page_data);
	}
}
