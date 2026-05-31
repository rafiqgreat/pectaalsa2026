<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Marking extends MY_Controller
{
	private function current_user_id()
	{
		$id = (int) $this->session->userdata('id');
		if ($id > 0) return $id;
		$logged = $this->session->userdata('logged');
		if (is_array($logged) && !empty($logged['id'])) return (int) $logged['id'];
		return (int) logged('id');
	}

	private function current_role()
	{
		$role = (int) $this->session->userdata('role');
		if ($role > 0) return $role;
		$logged = $this->session->userdata('logged');
		if (is_array($logged) && !empty($logged['role'])) return (int) $logged['role'];
		return (int) logged('role');
	}

	private function require_emarker()
	{
		if ($this->current_role() !== 2) {
			$this->session->set_flashdata('message', 'Access denied.');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/dashboard', 'refresh');
			die;
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->require_emarker();

		$this->page_data['page']->title = 'Marking';
		$this->page_data['page']->menu = 'emarking';
		$this->load->model('Marking_model', 'marking');
		$this->load->model('Emarking_settings_model', 'emarking_settings');
	}

	public function index()
	{
		$this->dashboard();
	}

	public function dashboard()
	{
		$this->page_data['page']->submenu = 'dashboard';
		$user_id = $this->current_user_id();

		$this->page_data['stats'] = $this->marking->get_emarker_stats($user_id);
		$this->page_data['batches'] = $this->marking->get_emarker_batches($user_id);
		$this->load->view('emarker/dashboard', $this->page_data);
	}

	public function view_batch($batch_id)
	{
		$this->page_data['page']->submenu = 'batches';

		$user_id = $this->current_user_id();
		$batch = $this->marking->get_batch_for_emarker((int) $batch_id, $user_id);
		if (!$batch) show_404();

		$this->page_data['batch'] = $batch;
		$this->page_data['items'] = $this->marking->get_batch_items((int) $batch_id, $user_id);
		$this->load->view('emarker/view_batch', $this->page_data);
	}

	// Required: start($batch_id)
	public function start($batch_id)
	{
		$this->marking_screen((int) $batch_id, null);
	}

	public function marking_screen($batch_id, $batch_item_id = null)
	{
		$this->page_data['page']->submenu = 'marking';

		$user_id = $this->current_user_id();
		$batch = $this->marking->get_batch_for_emarker((int) $batch_id, $user_id);
		if (!$batch) show_404();

		if ($batch_item_id === null) {
			$next = $this->marking->get_next_pending_item((int) $batch_id, $user_id);
			if (!$next) {
				$this->session->set_flashdata('message', 'No pending items in this batch.');
				$this->session->set_flashdata('message_type', 'info');
				redirect('emarker/marking/view_batch/' . (int) $batch_id);
				return;
			}
			redirect('emarker/marking/marking_screen/' . (int) $batch_id . '/' . (int) $next->id);
			return;
		}

		$data = $this->marking->get_marking_data((int) $batch_item_id, $user_id);
		if (!$data) show_404();

		$this->page_data['batch'] = $batch;
		$this->page_data['marking'] = $data;
		$this->page_data['timer_seconds'] = (int) $this->emarking_settings->get_timer_seconds($user_id, 15);
		$this->page_data['batch_total_items'] = $this->marking->get_batch_total_items((int) $batch_id, $user_id);
		$this->page_data['batch_current_index'] = $this->marking->get_batch_item_index((int) $batch_id, (int) $batch_item_id, $user_id);
		$this->page_data['preload_image_paths'] = $this->marking->get_preload_image_paths((int) $batch_id, (int) $batch_item_id, $user_id, 3);
		$this->load->view('emarker/marking_screen', $this->page_data);
	}

	// Required: save_marks()
	public function save_marks()
	{
		postAllowed();
		$user_id = $this->current_user_id();

		$batch_id = (int) $this->input->post('batch_id', true);
		$batch_item_id = (int) $this->input->post('batch_item_id', true);
		$action = strtoupper(trim((string) $this->input->post('action', true)));
		if (!in_array($action, ['MARKED', 'SKIPPED', 'NOT_ATTEMPTED', 'RECHECK'], true)) {
			$action = 'MARKED';
		}

		$payload = [
			'action' => $action,
			'marks_obtained' => (float) $this->input->post('marks_obtained', true),
			'remarks' => trim((string) $this->input->post('remarks', true)),
			'steps' => (array) $this->input->post('steps'),
		];

		$out = $this->marking->save_mark($batch_item_id, $user_id, $payload);
		if (empty($out['ok'])) {
			$this->session->set_flashdata('message', (string) ($out['error'] ?? 'Unable to save.'));
			$this->session->set_flashdata('message_type', 'danger');
			redirect('emarker/marking/marking_screen/' . $batch_id . '/' . $batch_item_id);
			return;
		}

		$this->session->set_flashdata('message', 'Saved.');
		$this->session->set_flashdata('message_type', 'success');

		// Always return to start() to load next pending item
		redirect('emarker/marking/start/' . (int) $batch_id);
	}

	// Backward-compatible alias
	public function save_mark()
	{
		$this->save_marks();
	}

	public function get_batch_for_checking()
	{
		$user_id = $this->current_user_id();
		if ($user_id <= 0 || $this->current_role() !== 2) {
			$this->session->set_flashdata('alert', 'Access denied.');
			$this->session->set_flashdata('alert-type', 'error');
			redirect('user/login', 'refresh');
			return;
		}

		if ($this->marking->has_incomplete_assigned_batches($user_id)) {
			$this->session->set_flashdata('alert', 'Complete marking first to get new batch.');
			$this->session->set_flashdata('alert-type', 'error');
			redirect('emarker/marking/dashboard', 'refresh');
			return;
		}

		$out = $this->marking->create_auto_batch_for_emarker($user_id);
		if (!empty($out['ok'])) {
			$this->session->set_flashdata('alert', 'New batch created and assigned successfully.');
			$this->session->set_flashdata('alert-type', 'success');
			redirect('emarker/marking/dashboard', 'refresh');
			return;
		}

		$code = (string) ($out['code'] ?? '');
		if ($code === 'no_subjects') {
			$this->session->set_flashdata('alert', 'No allowed subjects are configured for your account.');
			$this->session->set_flashdata('alert-type', 'error');
		} elseif ($code === 'no_available') {
			$this->session->set_flashdata('alert', 'No questions are currently available for your subject.');
			$this->session->set_flashdata('alert-type', 'info');
		} else {
			$this->session->set_flashdata('alert', (string) ($out['error'] ?? 'No batch is currently available for marking.'));
			$this->session->set_flashdata('alert-type', 'error');
		}

		redirect('emarker/marking/dashboard', 'refresh');
	}
}
