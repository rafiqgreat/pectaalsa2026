<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking extends MY_Controller
{
	private $allowed_roles = [1, 17, 18, 19];

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

	private function require_role_access()
	{
		if (!in_array($this->current_role(), $this->allowed_roles, true)) {
			redirect('errors/permission_denied');
			die;
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->require_role_access();

		$this->page_data['page']->title = 'E-Marking';
		$this->page_data['page']->menu = 'emarking';
		$this->load->model('Emarking_model', 'emarking');
		$this->load->model('Emarking_batch_model', 'emarking_batch');
		$this->load->model('Emarking_report_model', 'emarking_report');
	}

	public function questions()
	{
		$this->page_data['page']->submenu = 'questions';

		$filters = [
			'assessment_type' => trim((string) $this->input->get('assessment_type', true)),
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'version' => trim((string) $this->input->get('version', true)),
			'page_no' => trim((string) $this->input->get('page_no', true)),
			'status' => trim((string) $this->input->get('status', true)),
		];

		$this->page_data['filters'] = $filters;
		$this->page_data['questions'] = $this->emarking->get_questions($filters);
		$this->load->view('admin/emarking/questions', $this->page_data);
	}

	public function add_question()
	{
		$this->page_data['page']->submenu = 'questions';
		$this->page_data['page']->title = 'Add Question';

		if ($this->input->method(true) === 'POST') {
			$this->load->library('form_validation');

			$this->form_validation->set_rules('assessment_type', 'Assessment Type', 'trim|required|in_list[CRQ,DICTATION]');
			$this->form_validation->set_rules('grade', 'Grade', 'trim|required|integer');
			$this->form_validation->set_rules('subject_code', 'Subject Code', 'trim|required');
			$this->form_validation->set_rules('version', 'Version', 'trim|required|integer');
			$this->form_validation->set_rules('page_no', 'Page No', 'trim|required');
			$this->form_validation->set_rules('question_no', 'Question No', 'trim|required');
			$this->form_validation->set_rules('question_title', 'Question Title', 'trim|required');
			$this->form_validation->set_rules('max_marks', 'Max Marks', 'trim|required|numeric');

			if ($this->form_validation->run() !== false) {
				$payload = $this->emarking->build_question_payload_from_post($this->current_user_id());
				$id = $this->emarking->save_question($payload);
				if ($id) {
					$this->session->set_flashdata('message', 'Question saved.');
					$this->session->set_flashdata('message_type', 'success');
					redirect('admin/emarking/questions');
					return;
				}
				$this->session->set_flashdata('message', 'Unable to save question (duplicate or invalid).');
				$this->session->set_flashdata('message_type', 'danger');
			} else {
				$this->session->set_flashdata('message', validation_errors());
				$this->session->set_flashdata('message_type', 'danger');
			}
		}

		$this->page_data['question'] = null;
		$this->page_data['rubric_steps'] = [];
		$this->load->view('admin/emarking/question_form', $this->page_data);
	}

	public function edit_question($id)
	{
		$this->page_data['page']->submenu = 'questions';
		$this->page_data['page']->title = 'Edit Question';

		$question = $this->emarking->get_question((int) $id);
		if (!$question) show_404();

		if ($this->input->method(true) === 'POST') {
			$this->load->library('form_validation');

			$this->form_validation->set_rules('assessment_type', 'Assessment Type', 'trim|required|in_list[CRQ,DICTATION]');
			$this->form_validation->set_rules('grade', 'Grade', 'trim|required|integer');
			$this->form_validation->set_rules('subject_code', 'Subject Code', 'trim|required');
			$this->form_validation->set_rules('version', 'Version', 'trim|required|integer');
			$this->form_validation->set_rules('page_no', 'Page No', 'trim|required');
			$this->form_validation->set_rules('question_no', 'Question No', 'trim|required');
			$this->form_validation->set_rules('question_title', 'Question Title', 'trim|required');
			$this->form_validation->set_rules('max_marks', 'Max Marks', 'trim|required|numeric');

			if ($this->form_validation->run() !== false) {
				$payload = $this->emarking->build_question_payload_from_post($this->current_user_id());
				$ok = $this->emarking->save_question($payload, (int) $id);
				if ($ok) {
					$this->session->set_flashdata('message', 'Question updated.');
					$this->session->set_flashdata('message_type', 'success');
					redirect('admin/emarking/questions');
					return;
				}
				$this->session->set_flashdata('message', 'Unable to update question (duplicate or invalid).');
				$this->session->set_flashdata('message_type', 'danger');
			} else {
				$this->session->set_flashdata('message', validation_errors());
				$this->session->set_flashdata('message_type', 'danger');
			}
		}

		$this->page_data['question'] = $question;
		$this->page_data['rubric_steps'] = $this->emarking->get_rubric_steps((int) $id);
		$this->load->view('admin/emarking/question_form', $this->page_data);
	}

	public function rubric_steps($question_id)
	{
		$this->page_data['page']->submenu = 'questions';
		$this->page_data['page']->title = 'Rubric Steps';

		$question = $this->emarking->get_question((int) $question_id);
		if (!$question) show_404();

		$this->page_data['question'] = $question;
		$this->page_data['rubric_steps'] = $this->emarking->get_rubric_steps((int) $question_id);
		$this->load->view('admin/emarking/rubric_steps', $this->page_data);
	}

	public function save_rubric_step()
	{
		postAllowed();
		$question_id = (int) $this->input->post('question_id', true);
		$question = $this->emarking->get_question($question_id);
		if (!$question) show_404();

		$payload = [
			'question_id' => $question_id,
			'step_order' => (int) $this->input->post('step_order', true),
			'step_label' => trim((string) $this->input->post('step_label', true)),
			'step_title' => trim((string) $this->input->post('step_title', true)),
			'step_detail' => trim((string) $this->input->post('step_detail', true)),
			'step_marks' => (float) $this->input->post('step_marks', true),
			'marking_type' => trim((string) $this->input->post('marking_type', true)),
			'min_marks' => (float) $this->input->post('min_marks', true),
			'max_marks' => (float) $this->input->post('max_marks', true),
			'status' => (int) $this->input->post('status', true) ? 1 : 0,
		];
		$id = (int) $this->input->post('id', true);

		$ok = $this->emarking->save_rubric_step($payload, $id > 0 ? $id : null);
		$this->session->set_flashdata('message', $ok ? 'Rubric step saved.' : 'Unable to save rubric step.');
		$this->session->set_flashdata('message_type', $ok ? 'success' : 'danger');
		redirect('admin/emarking/rubric_steps/' . $question_id);
	}

	public function delete_rubric_step($id)
	{
		$step = $this->emarking->get_rubric_step((int) $id);
		if (!$step) show_404();

		$this->emarking->delete_rubric_step((int) $id);
		$this->session->set_flashdata('message', 'Rubric step deleted.');
		$this->session->set_flashdata('message_type', 'success');
		redirect('admin/emarking/rubric_steps/' . (int) $step->question_id);
	}

	public function import_crq_images()
	{
		$this->page_data['page']->submenu = 'import';
		$this->page_data['page']->title = 'Import CRQ Images';

		$result_crq = null;
		$result_dict = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			$base_folder = trim((string) $this->input->post('base_folder', true));
			if ($base_folder === '') $base_folder = 'processed_crqs';
			$batch_no = trim((string) $this->input->post('upload_batch_no', true));
			if ($batch_no === '') $batch_no = 'CRQ-' . date('Ymd-His');

			$result_crq = $this->emarking->import_images_from_folder($base_folder, 'CRQ', $batch_no);
		}

		$this->page_data['default_crq_path'] = 'processed_crqs';
		$this->page_data['default_dictation_path'] = 'processed_dictation';
		$this->page_data['result_crq'] = $result_crq;
		$this->page_data['result_dict'] = $result_dict;
		$this->load->view('admin/emarking/import_images', $this->page_data);
	}

	public function import_dictation_images()
	{
		$this->page_data['page']->submenu = 'import';
		$this->page_data['page']->title = 'Import Dictation Images';

		$result_crq = null;
		$result_dict = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			$base_folder = trim((string) $this->input->post('base_folder', true));
			if ($base_folder === '') $base_folder = 'processed_dictation';
			$batch_no = trim((string) $this->input->post('upload_batch_no', true));
			if ($batch_no === '') $batch_no = 'DICT-' . date('Ymd-His');
			$result_dict = $this->emarking->import_images_from_folder($base_folder, 'DICTATION', $batch_no);
		}

		$this->page_data['default_crq_path'] = 'processed_crqs';
		$this->page_data['default_dictation_path'] = 'processed_dictation';
		$this->page_data['result_crq'] = $result_crq;
		$this->page_data['result_dict'] = $result_dict;
		$this->load->view('admin/emarking/import_images', $this->page_data);
	}

	public function create_batch()
	{
		$this->page_data['page']->submenu = 'batches';
		$this->page_data['page']->title = 'Create Batch';

		// Optional filters for question list
		$get_filters = [
			'assessment_type' => trim((string) $this->input->get('assessment_type', true)),
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'version' => trim((string) $this->input->get('version', true)),
		];

		if ($this->input->method(true) === 'POST') {
			postAllowed();
			$assessment_type = trim((string) $this->input->post('assessment_type', true));
			$grade = trim((string) $this->input->post('grade', true));
			$subject_code = trim((string) $this->input->post('subject_code', true));
			$version = trim((string) $this->input->post('version', true));
			$question_id = (int) $this->input->post('question_id', true);
			$emarker_id = (int) $this->input->post('emarker_id', true);
			$batch_size = (int) $this->input->post('batch_size', true);
			$deadline = trim((string) $this->input->post('deadline', true));
			if ($batch_size <= 0) $batch_size = 100;
			$deadline_dt = $deadline !== '' ? date('Y-m-d H:i:s', strtotime($deadline)) : null;

			$q = $this->emarking->get_question($question_id);
			if (!$q) {
				$this->session->set_flashdata('message', 'Invalid question selected.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}
			if ($assessment_type !== '' && strtoupper((string) $q->assessment_type) !== strtoupper($assessment_type)) {
				$this->session->set_flashdata('message', 'Assessment type does not match selected question.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}
			if ($grade !== '' && (int) $q->grade !== (int) $grade) {
				$this->session->set_flashdata('message', 'Grade does not match selected question.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}
			if ($subject_code !== '' && (string) $q->subject_code !== (string) $subject_code) {
				$this->session->set_flashdata('message', 'Subject code does not match selected question.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}
			if ($version !== '' && (int) $q->version !== (int) $version) {
				$this->session->set_flashdata('message', 'Version does not match selected question.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}

			$out = $this->emarking_batch->create_batch([
				'assessment_type' => $assessment_type,
				'question_id' => $question_id,
				'emarker_id' => $emarker_id,
				'assigned_by' => $this->current_user_id(),
				'batch_size' => $batch_size,
				'deadline' => $deadline_dt,
			]);

			if (!empty($out['ok'])) {
				$this->session->set_flashdata('message', 'Batch created: ' . (string) $out['batch_code'] . ' (items: ' . (int) $out['items_created'] . ').');
				$this->session->set_flashdata('message_type', 'success');
				redirect('admin/emarking/batches');
				return;
			}
			$this->session->set_flashdata('message', (string) ($out['error'] ?? 'Unable to create batch.'));
			$this->session->set_flashdata('message_type', 'danger');
		}

		$this->page_data['filters'] = $get_filters;
		$q_filters = array_merge($get_filters, ['status' => '1']);
		$this->page_data['questions'] = $this->emarking->get_questions($q_filters);
		$this->page_data['emarkers'] = $this->emarking_batch->get_emarkers();
		$this->load->view('admin/emarking/create_batch', $this->page_data);
	}

	public function batches()
	{
		$this->page_data['page']->submenu = 'batches';
		$this->page_data['page']->title = 'Batches';

		$filters = [
			'status' => trim((string) $this->input->get('status', true)),
			'assessment_type' => trim((string) $this->input->get('assessment_type', true)),
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'assigned_to' => trim((string) $this->input->get('assigned_to', true)),
			'question_id' => trim((string) $this->input->get('question_id', true)),
		];

		$this->page_data['filters'] = $filters;
		$this->page_data['batches'] = $this->emarking_batch->get_batches($filters);
		$this->load->view('admin/emarking/batches', $this->page_data);
	}

	public function reports()
	{
		$this->page_data['page']->submenu = 'reports';
		$this->page_data['page']->title = 'Reports';

		$filters = [
			'from' => trim((string) $this->input->get('from', true)),
			'to' => trim((string) $this->input->get('to', true)),
			'assessment_type' => trim((string) $this->input->get('assessment_type', true)),
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
		];

		$this->page_data['filters'] = $filters;
		$this->page_data['overall_summary'] = $this->emarking_report->get_overall_summary($filters);
		$this->page_data['question_summary'] = $this->emarking_report->get_reports_summary($filters);
		$this->page_data['subject_summary'] = $this->emarking_report->get_subject_summary($filters);
		$this->page_data['emarker_summary'] = $this->emarking_report->get_emarker_summary($filters);
		$this->page_data['batch_summary'] = $this->emarking_report->get_batch_summary($filters);
		$this->load->view('admin/emarking/reports', $this->page_data);
	}

	public function billing()
	{
		$this->page_data['page']->submenu = 'billing';
		$this->page_data['page']->title = 'Billing';

		$filters = [
			'from' => trim((string) $this->input->get('from', true)),
			'to' => trim((string) $this->input->get('to', true)),
			'emarker_id' => trim((string) $this->input->get('emarker_id', true)),
		];

		$this->page_data['filters'] = $filters;
		$this->page_data['emarkers'] = $this->emarking_batch->get_emarkers();
		$this->page_data['billing_rows'] = $this->emarking_report->get_billing($filters);
		$this->load->view('admin/emarking/billing', $this->page_data);
	}

	public function skipped()
	{
		$this->page_data['page']->submenu = 'skipped';
		$this->page_data['page']->title = 'Skipped / Not Attempted';

		$filters = [
			'from' => trim((string) $this->input->get('from', true)),
			'to' => trim((string) $this->input->get('to', true)),
			'emarker_id' => trim((string) $this->input->get('emarker_id', true)),
			'status' => trim((string) $this->input->get('status', true)),
		];

		$this->page_data['filters'] = $filters;
		$this->page_data['emarkers'] = $this->emarking_batch->get_emarkers();
		$this->page_data['rows'] = $this->emarking_report->get_skipped($filters);
		$this->load->view('admin/emarking/reports', $this->page_data);
	}
}
