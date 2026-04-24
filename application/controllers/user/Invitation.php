<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invitation extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'Invitation for Paper Checking';
		$this->page_data['page']->menu = 'invitation';
	}

	public function index()
	{
		$this->load->view('user/emarker/coming_soon', $this->page_data);
	}
}

