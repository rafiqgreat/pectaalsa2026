<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

	public $page_data;

	/**
	  * Extends by most of controllers not all controllers
	  */

	public function __construct()
	{

		parent::__construct();

		if( !empty($this->db->username) && !empty($this->db->hostname) && !empty($this->db->database) ){ }else{
			$this->users_model->logout();
			die('Database is not configured');
		}
		
		date_default_timezone_set( setting('timezone') );
		
		$this->config->set_item('language', getUserlang()); 

		$this->lang->load([
			'basic',
			
		], getUserlang() );

		if (!is_logged()) {
			$segment = $this->uri->segment(1);
			if ($segment === 'admin') {
				redirect('admin', 'refresh');
			}
				redirect('user/login', 'refresh');
			}

			$segment = $this->uri->segment(1);
			if ($segment !== 'admin' && user_access_blocked() && (int) logged('role') !== 1) {
				$this->session->unset_userdata('login');
				$this->session->unset_userdata('logged');
				delete_cookie('login');
				delete_cookie('login_token');
				$this->session->set_flashdata('message', user_access_block_message());
				$this->session->set_flashdata('message_type', 'danger');
				redirect('user/login', 'refresh');
			}

		$this->page_data['url'] = (object) [
			'assets' => assets_url().'/'
		];

		$this->page_data['app'] = (object) [
			'site_title' => setting('company_name')
		];

		$this->page_data['page'] = (object) [
			'title' => 'Dashboard',
			'menu' => 'dashboard',
			'submenu' => '',
		];

	}

	public function change_language()
	{
		// die(var_dump('test_func'));
	}

}

/* End of file My_Controller.php */
/* Location: ./application/core/My_Controller.php */

