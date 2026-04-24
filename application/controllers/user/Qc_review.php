<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Qc_review extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'QC-Review';
		$this->page_data['page']->menu = 'qc_review';
	}

	public function index()
	{
		$this->load->view('user/emarker/coming_soon', $this->page_data);
	}
}

