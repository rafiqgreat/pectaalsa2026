<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Evaluator_profile extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'Evaluator Profile';
		$this->page_data['page']->menu = 'evaluator_profile';
	}

	public function index()
	{
		$user_id = (int) logged('id');
		$user = $this->users_model->getById($user_id);

		$address = $this->db->get_where('teacher_addresses', ['user_id' => $user_id])->row();
		$educations = $this->db->order_by('id', 'ASC')->get_where('teacher_educations', ['user_id' => $user_id])->result();
		$experiences = $this->db->order_by('id', 'ASC')->get_where('teacher_experiences', ['user_id' => $user_id])->result();
		$bank = $this->db->get_where('teacher_bank_details', ['user_id' => $user_id])->row();
		$specialization = $this->db->get_where('teacher_specializations', ['user_id' => $user_id])->row();
		$security = $this->db->get_where('teacher_security_documents', ['user_id' => $user_id])->row();
		$emarking = $this->db->order_by('id', 'ASC')->get_where('teacher_emarking_experiences', ['user_id' => $user_id])->result();
		$steps = $this->db->get_where('teacher_registration_steps', ['user_id' => $user_id])->row();

		$this->page_data['user_row'] = $user;
		$this->page_data['address'] = $address;
		$this->page_data['educations'] = $educations;
		$this->page_data['experiences'] = $experiences;
		$this->page_data['bank'] = $bank;
		$this->page_data['specialization'] = $specialization;
		$this->page_data['security'] = $security;
		$this->page_data['emarking'] = $emarking;
		$this->page_data['steps_row'] = $steps;

		$this->load->view('user/emarker/view_profile', $this->page_data);
	}
}

