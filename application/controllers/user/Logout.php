<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Logout extends CI_Controller {

	public function __construct()

	{

		parent::__construct();

		if(!is_logged()){

			redirect('user/login','refresh');

		}

	}

	public function index()

	{

		$this->activity_model->add("User: ".logged('name').' Logged Out');

		$this->user_users_model->logout();

		$this->session->set_flashdata('message', 'Logut Successfully.');

		$this->session->set_flashdata('message_type', 'success'); // 'success', 'info', 'warning', or 'danger'

		redirect('user/login','refresh');

	}

}

/* End of file Logout.php */

/* Location: ./application/controllers/Admin/Logout.php */