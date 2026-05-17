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
		// Redirect e-marker users to the profile dashboard (matches provided UI).
		if ((int) logged('role') === 1) {
			redirect('user/evaluator_profile', 'refresh');
			return;
		}
		$user_id = $this->session->userdata('logged')['id'];
		$this->load->model('user/Users_model');
		$this->page_data['user'] = $this->Users_model->get($user_id);

		// For eMarker role, show assigned subjects summary on dashboard
		$this->page_data['assigned_subjects'] = [];
		if ((int) logged('role') === 2) {
			$this->load->model('Marking_model', 'marking');
			$batches = $this->marking->get_emarker_batches((int) $user_id);
			$set = [];
			foreach (($batches ?? []) as $b) {
				$key = (string) ($b->assessment_type ?? '') . '|' . (string) ($b->grade ?? '') . '|' . (string) ($b->subject_code ?? '');
				$set[$key] = [
					'assessment_type' => (string) ($b->assessment_type ?? ''),
					'grade' => (int) ($b->grade ?? 0),
					'subject_code' => (string) ($b->subject_code ?? ''),
				];
			}
			$this->page_data['assigned_subjects'] = array_values($set);
		}
		$this->load->view('user/dashboard', $this->page_data);
	}
}
