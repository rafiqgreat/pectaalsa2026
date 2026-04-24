<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emarkers extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		// Admin only (role 1)
		if ((int) logged('role') !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$this->page_data['page']->title = 'E-Markers';
		$this->page_data['page']->menu = 'emarkers';
	}

	public function index()
	{
		$status = (string) $this->input->get('status', true);
		$status = in_array($status, ['all', 'pending', 'active'], true) ? $status : 'all';
		$reg = (string) $this->input->get('reg', true);
		$reg = in_array($reg, ['all', 'completed', 'incomplete'], true) ? $reg : 'all';
		$q = trim((string) $this->input->get('q', true));

		$this->db->select('u.id, u.name, u.email, u.phone, u.cnic, u.status, u.created_at, s.registration_completed')
			->from('users u')
			->join('teacher_registration_steps s', 's.user_id = u.id', 'left')
			->where('u.role', 2)
			->order_by('u.id', 'DESC');

		if ($status === 'pending') {
			$this->db->where('u.status', 0);
		} elseif ($status === 'active') {
			$this->db->where('u.status', 1);
		}

		if ($reg === 'completed') {
			$this->db->where('s.registration_completed', 1);
		} elseif ($reg === 'incomplete') {
			$this->db->group_start()
				->where('s.registration_completed IS NULL', null, false)
				->or_where('s.registration_completed', 0)
				->group_end();
		}

		if ($q !== '') {
			$this->db->group_start()
				->like('u.name', $q)
				->or_like('u.email', $q)
				->or_like('u.phone', $q)
				->or_like('u.cnic', $q)
				->group_end();
		}

		$rows = $this->db->get()->result();

		// Attach doc presence counts (small N -> per-row queries acceptable)
		foreach ($rows as $r) {
			$r->has_security_doc = (int) $this->db->from('teacher_security_documents')->where('user_id', (int) $r->id)->count_all_results() > 0;
			$r->edu_docs = (int) $this->db->from('teacher_educations')->where('user_id', (int) $r->id)->count_all_results();
			$r->exp_docs = (int) $this->db->from('teacher_experiences')->where('user_id', (int) $r->id)->count_all_results();
		}

		$this->page_data['emarkers'] = $rows;
		$this->page_data['filters'] = ['status' => $status, 'reg' => $reg, 'q' => $q];
		$this->load->view('admin/emarkers/list', $this->page_data);
	}

	public function view($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$this->page_data['page']->title = 'E-Marker Profile';
		$this->page_data['page']->menu = 'emarkers';

		$this->page_data['user_row'] = $user;
		$this->page_data['steps_row'] = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		$this->page_data['address'] = $this->db->get_where('teacher_addresses', ['user_id' => $id])->row();
		$this->page_data['educations'] = $this->db->order_by('id', 'ASC')->get_where('teacher_educations', ['user_id' => $id])->result();
		$this->page_data['experiences'] = $this->db->order_by('id', 'ASC')->get_where('teacher_experiences', ['user_id' => $id])->result();
		$this->page_data['bank'] = $this->db->get_where('teacher_bank_details', ['user_id' => $id])->row();
		$this->page_data['specialization'] = $this->db->get_where('teacher_specializations', ['user_id' => $id])->row();
		$this->page_data['security'] = $this->db->get_where('teacher_security_documents', ['user_id' => $id])->row();
		$this->page_data['emarking'] = $this->db->order_by('id', 'ASC')->get_where('teacher_emarking_experiences', ['user_id' => $id])->result();

		$this->load->view('admin/emarkers/view', $this->page_data);
	}

	public function approve($id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) show_404();

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) show_404();

		$steps = $this->db->get_where('teacher_registration_steps', ['user_id' => $id])->row();
		if (!$steps || (int) ($steps->registration_completed ?? 0) !== 1) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Cannot approve: registration is not completed.');
			redirect('admin/emarkers/view/' . $id);
			return;
		}

		$security = $this->db->get_where('teacher_security_documents', ['user_id' => $id])->row();
		if (!$security || empty($security->document_file)) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Cannot approve: security document is missing.');
			redirect('admin/emarkers/view/' . $id);
			return;
		}

		$this->db->where('id', $id)->update('users', ['status' => 1]);
		$this->activity_model->add("Admin approved E-Marker user #{$id}", logged('id'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'E-Marker account approved and activated.');
		redirect('admin/emarkers/view/' . $id);
	}

	public function change_status($id = 0)
	{
		$id = (int) $id;
		$status = $this->input->get('status');
		$status = ($status === 'true' || $status === true || (string) $status === '1') ? 1 : 0;

		if ($id <= 0) {
			$this->output->set_output('error');
			return;
		}

		$user = $this->db->get_where('users', ['id' => $id, 'role' => 2])->row();
		if (!$user) {
			$this->output->set_output('error');
			return;
		}

		$this->db->where('id', $id)->update('users', ['status' => $status]);
		$this->activity_model->add("Admin changed E-Marker user #{$id} status to {$status}", logged('id'));
		$this->output->set_output('done');
	}
}
