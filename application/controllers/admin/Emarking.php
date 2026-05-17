<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking extends MY_Controller
{
	private $allowed_roles = [1, 17, 18, 19];

	private function rubric_active_max_total($question_id)
	{
		$total = 0.0;
		$steps = $this->emarking->get_rubric_steps((int) $question_id);
		foreach ($steps as $s) {
			if ((int) ($s->status ?? 0) !== 1) continue;
			$type = (string) ($s->marking_type ?? 'ZERO_ONE');
			if ($type === 'RANGE') {
				$total += (float) ($s->max_marks ?? 0);
			} else {
				$total += (float) ($s->step_marks ?? 0);
			}
		}
		return (float) $total;
	}

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
		$this->load->model('Emarking_settings_model', 'emarking_settings');
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
				$rubric_warning = null;
				if ((int) ($payload['status'] ?? 0) === 1) {
					$rubric_total = (float) $this->rubric_active_max_total(0); // new question has no steps yet
					$question_max = (float) ($payload['max_marks'] ?? 0);
					if ($question_max > 0) {
						// Allow activation when rubric total is <= question max (warn if not equal),
						// but prevent activation when rubric total exceeds question max.
						if ($rubric_total - $question_max > 0.0001) {
							$this->session->set_flashdata('message', 'Cannot activate question: rubric total (' . number_format($rubric_total, 2) . ') exceeds max marks (' . number_format($question_max, 2) . ').');
							$this->session->set_flashdata('message_type', 'danger');
							redirect('admin/emarking/add_question');
							return;
						}
						if (abs($rubric_total - $question_max) > 0.0001) {
							$rubric_warning = 'Warning: rubric total (' . number_format($rubric_total, 2) . ') does not match max marks (' . number_format($question_max, 2) . '). You can continue, but please review rubric steps.';
						}
					}
				}
				$id = $this->emarking->save_question($payload);
				if ($id) {
					$msg = 'Question saved.';
					$type = 'success';
					if (!empty($rubric_warning)) {
						$msg .= ' ' . $rubric_warning;
						$type = 'warning';
					}
					$this->session->set_flashdata('message', $msg);
					$this->session->set_flashdata('message_type', $type);
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
				$rubric_warning = null;
				if ((int) ($payload['status'] ?? 0) === 1) {
					$rubric_total = (float) $this->rubric_active_max_total((int) $id);
					$question_max = (float) ($payload['max_marks'] ?? 0);
					if ($question_max > 0) {
						// Allow activation when rubric total is <= question max (warn if not equal),
						// but prevent activation when rubric total exceeds question max.
						if ($rubric_total - $question_max > 0.0001) {
							$this->session->set_flashdata('message', 'Cannot activate question: rubric total (' . number_format($rubric_total, 2) . ') exceeds max marks (' . number_format($question_max, 2) . ').');
							$this->session->set_flashdata('message_type', 'danger');
							redirect('admin/emarking/edit_question/' . (int) $id);
							return;
						}
						if (abs($rubric_total - $question_max) > 0.0001) {
							$rubric_warning = 'Warning: rubric total (' . number_format($rubric_total, 2) . ') does not match max marks (' . number_format($question_max, 2) . '). You can continue, but please review rubric steps.';
						}
					}
				}
				$ok = $this->emarking->save_question($payload, (int) $id);
				if ($ok) {
					$msg = 'Question updated.';
					$type = 'success';
					if (!empty($rubric_warning)) {
						$msg .= ' ' . $rubric_warning;
						$type = 'warning';
					}
					$this->session->set_flashdata('message', $msg);
					$this->session->set_flashdata('message_type', $type);
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

		$id = (int) $this->input->post('id', true);

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

		$payload['marking_type'] = strtoupper(trim((string) $payload['marking_type']));
		if (!in_array($payload['marking_type'], ['ZERO_ONE', 'RANGE', 'FIXED'], true)) {
			$payload['marking_type'] = 'ZERO_ONE';
		}

		// Basic normalization / validation
		$payload['step_order'] = max(1, (int) $payload['step_order']);
		$payload['step_marks'] = max(0, (float) $payload['step_marks']);
		$payload['min_marks'] = max(0, (float) $payload['min_marks']);
		$payload['max_marks'] = max(0, (float) $payload['max_marks']);

		if ($payload['marking_type'] === 'RANGE') {
			if ($payload['step_marks'] <= 0) {
				// Not used for RANGE, but keep a sensible value for display/consistency
				$payload['step_marks'] = $payload['max_marks'];
			}
			if ($payload['max_marks'] < $payload['min_marks']) {
				$this->session->set_flashdata('message', 'Invalid RANGE: Max Marks must be greater than or equal to Min Marks.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/rubric_steps/' . $question_id);
				return;
			}
		} else {
			// For ZERO_ONE/FIXED, keep range fields aligned to step_marks for clarity
			$payload['min_marks'] = 0.0;
			$payload['max_marks'] = (float) $payload['step_marks'];
		}

		// Enforce max 15 ACTIVE steps per question
		$active_steps = array_filter($this->emarking->get_rubric_steps($question_id), function ($s) {
			return (int) ($s->status ?? 0) === 1;
		});
		$active_count_excluding_current = 0;
		$max_total_excluding_current = 0.0;

		foreach ($active_steps as $s) {
			if ($id > 0 && (int) $s->id === $id) continue;
			$active_count_excluding_current++;
			$type = (string) ($s->marking_type ?? 'ZERO_ONE');
			if ($type === 'RANGE') {
				$max_total_excluding_current += (float) ($s->max_marks ?? 0);
			} else {
				$max_total_excluding_current += (float) ($s->step_marks ?? 0);
			}
		}

		$new_active_count = $active_count_excluding_current + ((int) $payload['status'] === 1 ? 1 : 0);
		if ($new_active_count > 15) {
			$this->session->set_flashdata('message', 'You can configure a maximum of 15 active rubric steps per question.');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('admin/emarking/rubric_steps/' . $question_id);
			return;
		}

		$new_step_max = 0.0;
		if ($payload['status'] === 1) {
			$new_step_max = ($payload['marking_type'] === 'RANGE') ? (float) $payload['max_marks'] : (float) $payload['step_marks'];
		}
		$new_max_total = $max_total_excluding_current + $new_step_max;
		$question_max = (float) ($question->max_marks ?? 0);
		$rubric_warning = null;
		if ($question_max > 0 && $new_max_total - $question_max > 0.0001) {
			$this->session->set_flashdata('message', 'Rubric maximum total (' . number_format($new_max_total, 2) . ') exceeds question max marks (' . number_format($question_max, 2) . ').');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('admin/emarking/rubric_steps/' . $question_id);
			return;
		}
		if ((int) ($question->status ?? 0) === 1 && $question_max > 0 && abs($new_max_total - $question_max) > 0.0001) {
			// Allow editing while ACTIVE as long as rubric total stays <= question max.
			$rubric_warning = 'Warning: question is ACTIVE and rubric total (' . number_format($new_max_total, 2) . ') does not match question max marks (' . number_format($question_max, 2) . ').';
		}

		$ok = $this->emarking->save_rubric_step($payload, $id > 0 ? $id : null);
		if ($ok) {
			$msg = 'Rubric step saved.';
			$type = 'success';
			if (!empty($rubric_warning)) {
				$msg .= ' ' . $rubric_warning;
				$type = 'warning';
			}
			$this->session->set_flashdata('message', $msg);
			$this->session->set_flashdata('message_type', $type);
		} else {
			$this->session->set_flashdata('message', 'Unable to save rubric step.');
			$this->session->set_flashdata('message_type', 'danger');
		}
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

		// Default deadline is 3 days from now (prefilled in form)
		$default_deadline_dt = date('Y-m-d H:i:s', time() + (3 * 24 * 60 * 60));
		$this->page_data['default_deadline_dt'] = $default_deadline_dt;

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
			$deadline_ts = $deadline !== '' ? strtotime($deadline) : false;
			$deadline_dt = $deadline_ts !== false ? date('Y-m-d H:i:s', $deadline_ts) : $default_deadline_dt;
			if ($deadline !== '' && $deadline_ts === false) {
				$this->session->set_flashdata('message', 'Warning: invalid deadline entered, defaulted to +3 days.');
				$this->session->set_flashdata('message_type', 'warning');
			}

			$q = $this->emarking->get_question($question_id);
			if (!$q) {
				$this->session->set_flashdata('message', 'Invalid question selected.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}

			// Enforce rubric total must match question max marks before allowing marking
			$rubric_total = (float) $this->rubric_active_max_total((int) $question_id);
			$question_max = (float) ($q->max_marks ?? 0);
			if ($question_max > 0 && $rubric_total - $question_max > 0.0001) {
				$this->session->set_flashdata('message', 'Cannot create batch: rubric total (' . number_format($rubric_total, 2) . ') exceeds question max marks (' . number_format($question_max, 2) . ').');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/create_batch', 'refresh');
				return;
			}
			if ($question_max > 0 && abs($rubric_total - $question_max) > 0.0001) {
				$this->session->set_flashdata('message', 'Warning: rubric total (' . number_format($rubric_total, 2) . ') does not match question max marks (' . number_format($question_max, 2) . ').');
				$this->session->set_flashdata('message_type', 'warning');
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
		$questions = $this->emarking->get_questions($q_filters);
		$this->page_data['questions'] = $questions;
		$this->page_data['emarkers'] = $this->emarking_batch->get_emarkers();

		// Count images per question (for the dropdown display)
		$uploaded_counts = [];
		$total_counts = [];
		$qids = [];
		foreach (($questions ?? []) as $q) {
			if (!empty($q->id)) $qids[] = (int) $q->id;
		}
		$qids = array_values(array_unique(array_filter($qids, function ($v) { return (int) $v > 0; })));
		if (!empty($qids)) {
			$rows = $this->db->select('question_id, COUNT(*) as total_cnt, SUM(UPPER(TRIM(status)) = \'UPLOADED\') as uploaded_cnt')
				->from('emarking_question_images')
				->where_in('question_id', $qids)
				->group_by('question_id')
				->get()
				->result();
			foreach (($rows ?? []) as $r) {
				$qid = (int) ($r->question_id ?? 0);
				if ($qid <= 0) continue;
				$total_counts[$qid] = (int) ($r->total_cnt ?? 0);
				$uploaded_counts[$qid] = (int) ($r->uploaded_cnt ?? 0);
			}
		}
		$this->page_data['uploaded_counts'] = $uploaded_counts;
		$this->page_data['total_counts'] = $total_counts;

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

	public function emarker_timers()
	{
		$this->page_data['page']->submenu = 'batches';
		$this->page_data['page']->title = 'eMarker Timers';

		$emarkers = $this->emarking_batch->get_emarkers();
		$this->page_data['emarkers'] = $emarkers;

		$ids = [];
		foreach (($emarkers ?? []) as $u) {
			if (!empty($u->id)) $ids[] = (int) $u->id;
		}
		$this->page_data['timer_map'] = $this->emarking_settings->get_timer_map($ids);

		if ($this->input->method(true) === 'POST') {
			postAllowed();
			$timers = (array) $this->input->post('timers');
			$saved = 0;
			foreach ($timers as $uid => $sec) {
				$uid = (int) $uid;
				if ($uid <= 0) continue;
				if ($this->emarking_settings->set_timer_seconds($uid, (int) $sec)) {
					$saved++;
				}
			}
			$this->session->set_flashdata('message', 'Timers updated for ' . $saved . ' eMarkers.');
			$this->session->set_flashdata('message_type', 'success');
			redirect('admin/emarking/emarker_timers');
			return;
		}

		$this->load->view('admin/emarking/emarker_timers', $this->page_data);
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
