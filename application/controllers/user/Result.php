<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'View Result';
		$this->page_data['page']->menu = 'result';
	}

	public function index()
	{
		$this->load->view('user/emarker/coming_soon', $this->page_data);
	}
}

