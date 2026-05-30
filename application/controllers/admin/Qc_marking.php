<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_marking extends MY_Controller
{
	private $subject_code_map = [
		'1' => 'ENGLISH',
		'2' => 'URDU',
		'3' => 'MATH',
		'4' => 'SCIENCE',
	];

	private function current_role()
	{
		return (int) logged('role');
	}

	private function require_admin_or_ss()
	{
		$role = $this->current_role();
		if (!in_array($role, [1, 18], true)) {
			redirect('errors/permission_denied');
			die;
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->require_admin_or_ss();

		$this->page_data['page']->menu = 'emarking';
		$this->page_data['page']->submenu = 'qc';
		$this->load->model('Qc_marking_model', 'qc_marking');
		$this->load->model('Qc_report_model', 'qc_report');
		$this->load->model('Emarking_settings_model', 'emarking_settings');
	}

	// Admin: create a QC batch (10 images per question)
	public function create_batch()
	{
		if ($this->current_role() !== 1) show_404();

		$this->page_data['page']->title = 'QC - Create Batch';
		$this->page_data['subject_options'] = $this->subject_code_map;

		$result = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			$params = [
				'assessment_type' => trim((string) $this->input->post('assessment_type', true)),
				'grade' => (int) $this->input->post('grade', true),
				'subject_code' => trim((string) $this->input->post('subject_code', true)),
				'version' => trim((string) $this->input->post('version', true)), // '' means ALL
				'assigned_to' => (int) $this->input->post('assigned_to', true),
				'assigned_by' => (int) logged('id'),
				'per_question' => 10,
			];

			$result = $this->qc_marking->create_qc_batch($params);
			if (!empty($result['ok'])) {
				$this->session->set_flashdata('alert-type', 'success');
				$this->session->set_flashdata('alert', 'QC batch created: ' . (string) ($result['batch_code'] ?? ''));
				redirect('admin/qc_marking/batches');
				return;
			}
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', (string) ($result['error'] ?? 'Unable to create QC batch.'));
		}

		$subject_code = (string) $this->input->post('subject_code', true);
		$this->page_data['ss_options'] = $this->qc_marking->get_subject_specialists($subject_code);
		$this->page_data['result'] = $result;
		$this->load->view('admin/qc_marking/create_batch', $this->page_data);
	}

	public function batches()
	{
		if ($this->current_role() !== 1) show_404();
		$this->page_data['page']->title = 'QC - Batches';
		$this->page_data['batches'] = $this->qc_marking->get_batches();
		$this->load->view('admin/qc_marking/batches', $this->page_data);
	}

	public function reports()
	{
		if ($this->current_role() !== 1) show_404();
		$this->page_data['page']->title = 'QC - SS Summary';
		$filters = [
			'from' => trim((string) $this->input->get('from', true)),
			'to' => trim((string) $this->input->get('to', true)),
		];
		$this->page_data['filters'] = $filters;
		$this->page_data['rows'] = $this->qc_report->get_ss_summary($filters);
		$this->load->view('admin/qc_marking/reports', $this->page_data);
	}

	// SS: dashboard
	public function my()
	{
		if ($this->current_role() !== 18) show_404();
		$this->page_data['page']->title = 'QC - My Batches';
		$ss_id = (int) logged('id');
		$this->page_data['stats'] = $this->qc_marking->get_qc_stats($ss_id);
		$this->page_data['batches'] = $this->qc_marking->get_batches(['assigned_to' => $ss_id]);
		$this->load->view('admin/qc_marking/my_dashboard', $this->page_data);
	}

	public function view_batch($batch_id)
	{
		if ($this->current_role() !== 18) show_404();
		$this->page_data['page']->title = 'QC - Batch';
		$ss_id = (int) logged('id');
		$batch = $this->qc_marking->get_batch_for_ss((int) $batch_id, $ss_id);
		if (!$batch) show_404();
		$this->page_data['batch'] = $batch;
		$this->page_data['items'] = $this->qc_marking->get_batch_items((int) $batch_id, $ss_id);
		$this->load->view('admin/qc_marking/view_batch', $this->page_data);
	}

	public function start($batch_id)
	{
		$this->marking_screen((int) $batch_id, null);
	}

	public function marking_screen($batch_id, $batch_item_id = null)
	{
		if ($this->current_role() !== 18) show_404();
		$this->page_data['page']->title = 'QC - Marking';
		$ss_id = (int) logged('id');
		$batch = $this->qc_marking->get_batch_for_ss((int) $batch_id, $ss_id);
		if (!$batch) show_404();

		if ($batch_item_id === null) {
			$next = $this->qc_marking->get_next_pending_item((int) $batch_id, $ss_id);
			if (!$next) {
				$this->session->set_flashdata('alert', 'No pending items in this batch.');
				$this->session->set_flashdata('alert-type', 'info');
				redirect('admin/qc_marking/view_batch/' . (int) $batch_id);
				return;
			}
			redirect('admin/qc_marking/marking_screen/' . (int) $batch_id . '/' . (int) $next->id);
			return;
		}

		$data = $this->qc_marking->get_marking_data((int) $batch_item_id, $ss_id);
		if (!$data) show_404();

		$this->page_data['batch'] = $batch;
		$this->page_data['marking'] = $data;
		$this->page_data['timer_seconds'] = (int) $this->emarking_settings->get_timer_seconds($ss_id, 15);
		$this->page_data['batch_total_items'] = (int) ($batch->total_items ?? 0);

		// Compute current index based on count of <= id
		$this->db->from('emarking_qc_batch_items i');
		$this->db->where('i.batch_id', (int) $batch_id);
		$this->db->where('i.id <=', (int) $batch_item_id);
		$this->page_data['batch_current_index'] = (int) $this->db->count_all_results();

		$this->load->view('admin/qc_marking/marking_screen', $this->page_data);
	}

	public function save_marks()
	{
		if ($this->current_role() !== 18) show_404();
		postAllowed();

		$ss_id = (int) logged('id');
		$batch_id = (int) $this->input->post('batch_id', true);
		$batch_item_id = (int) $this->input->post('batch_item_id', true);
		$action = strtoupper(trim((string) $this->input->post('action', true)));
		if (!in_array($action, ['MARKED', 'SKIPPED', 'NOT_ATTEMPTED', 'RECHECK'], true)) $action = 'MARKED';

		$payload = [
			'action' => $action,
			'marks_obtained' => (float) $this->input->post('marks_obtained', true),
			'remarks' => trim((string) $this->input->post('remarks', true)),
			'steps' => (array) $this->input->post('steps'),
		];

		$out = $this->qc_marking->save_mark($batch_item_id, $ss_id, $payload);
		if (empty($out['ok'])) {
			$this->session->set_flashdata('alert', (string) ($out['error'] ?? 'Unable to save.'));
			$this->session->set_flashdata('alert-type', 'danger');
			redirect('admin/qc_marking/marking_screen/' . $batch_id . '/' . $batch_item_id);
			return;
		}

		$this->session->set_flashdata('alert', 'Saved.');
		$this->session->set_flashdata('alert-type', 'success');
		redirect('admin/qc_marking/start/' . (int) $batch_id);
	}
}

