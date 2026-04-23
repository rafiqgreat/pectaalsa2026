<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Logout extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		if(!is_logged()){
			redirect('admin','refresh');
		}
	}
	public function index()
	{
		$this->activity_model->add("User: ".logged('name').' Logged Out');
		$this->users_model->logout();
		redirect('admin','refresh');
	}
}
/* End of file Logout.php */
/* Location: ./application/controllers/Admin/Logout.php */
