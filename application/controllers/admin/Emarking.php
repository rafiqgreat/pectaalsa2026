<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking extends MY_Controller
{
	private $allowed_roles = [1, 17, 18, 19];
	private $subject_code_map = [
		1 => 'ENGLISH',
		2 => 'URDU',
		3 => 'MATH',
		4 => 'SCIENCE',
	];

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

	private function get_subject_specialist_subjects()
	{
		// Subject Specialist assigned subjects are stored in `users.subjects` as JSON array.
		if ($this->current_role() !== 18) return [];
		if (!$this->db->field_exists('subjects', 'users')) return [];

		// In this codebase, logged('id') is the most reliable across session/cookie login.
		$uid = (int) logged('id');
		if ($uid <= 0) return [];

		$row = $this->db->select('subjects')->get_where('users', ['id' => $uid])->row();
		$raw = trim((string) ($row->subjects ?? ''));
		if ($raw === '') return [];

		$decoded = json_decode($raw, true);
		// If JSON decode failed, fall back to comma-separated parsing.
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			$decoded = preg_split('/\s*,\s*/', trim($raw, "[]\"' \t\n\r\0\x0B"));
		}
		// Some rows may store JSON-as-string (double-encoded). Try decoding again.
		if (is_string($decoded)) {
			$decoded2 = json_decode($decoded, true);
			if (is_array($decoded2)) {
				$decoded = $decoded2;
			}
		}

		$subjects = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $raw);
		// Normalize entries like {"0":"ENGLISH","1":"URDU"} (assoc arrays) to values.
		if (is_array($subjects) && array_values($subjects) !== $subjects) {
			$subjects = array_values($subjects);
		}
		$subjects = array_values(array_unique(array_filter(array_map('trim', (array) $subjects), function ($v) { return $v !== ''; })));
		return $subjects;
	}

	private function ss_allowed_subject_codes()
	{
		$raw = $this->get_subject_specialist_subjects();
		$subjects = array_values(array_unique(array_filter(array_map('trim', (array) $raw), function ($v) { return (string) $v !== ''; })));

		$allowed = [];
		foreach ($subjects as $s) {
			// Accept numeric subject codes in JSON as well as names.
			if (preg_match('/^\\d+$/', (string) $s)) {
				$code = (int) $s;
				if (isset($this->subject_code_map[$code])) {
					$allowed[] = (string) $code;
				}
				continue;
			}

			$name = strtoupper((string) $s);
			foreach ($this->subject_code_map as $code => $mappedName) {
				if ($name === strtoupper((string) $mappedName)) {
					$allowed[] = (string) $code;
				}
			}
		}

		return array_values(array_unique($allowed));
	}

	private function ss_allowed_subject_names()
	{
		$raw = $this->get_subject_specialist_subjects();
		$subjects = array_values(array_unique(array_filter(array_map('trim', (array) $raw), function ($v) { return (string) $v !== ''; })));

		$out = [];
		foreach ($subjects as $s) {
			if (preg_match('/^\\d+$/', (string) $s)) {
				$code = (int) $s;
				if (isset($this->subject_code_map[$code])) {
					$out[] = strtoupper((string) $this->subject_code_map[$code]);
				}
				continue;
			}
			$out[] = strtoupper((string) $s);
		}

		return array_values(array_unique($out));
	}

	private function safe_unlink_relative($relativePath)
	{
		$relativePath = str_replace('\\', '/', (string) $relativePath);
		$relativePath = trim($relativePath);
		if ($relativePath === '') return false;
		if (strpos($relativePath, '..') !== false) return false;

		$base = rtrim((string) FCPATH, '\\/');
		$abs = $base . '/' . ltrim($relativePath, '/');
		$realBase = realpath($base);
		$realAbs = realpath($abs);
		if ($realBase === false || $realAbs === false) return false;

		$realBase = rtrim(str_replace('\\', '/', $realBase), '/') . '/';
		$realAbs = str_replace('\\', '/', $realAbs);
		if (strpos($realAbs, $realBase) !== 0) return false;

		if (!is_file($realAbs)) return false;
		return @unlink($realAbs);
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
		// Grade fixed to 4 for this module UI.
		$filters['grade'] = '4';
		$this->page_data['subject_options'] = $this->subject_code_map;
		if ($this->current_role() === 18) {
			$allowed = $this->ss_allowed_subject_codes();
			$opts = [];
			foreach ($this->subject_code_map as $code => $name) {
				if (in_array((string) $code, $allowed, true)) $opts[$code] = $name;
			}
			$this->page_data['subject_options'] = $opts;
		}

		$this->page_data['filters'] = $filters;
		$query_filters = $filters;
		if ($this->current_role() === 18) {
			$allowed_codes = $this->ss_allowed_subject_codes();
			$typed = trim((string) ($filters['subject_code'] ?? ''));
			if (!empty($allowed_codes)) {
				if ($typed !== '' && !in_array($typed, $allowed_codes, true)) {
					$query_filters['subject_code'] = ['-1'];
				} else {
					$query_filters['subject_code'] = ($typed !== '') ? $typed : $allowed_codes;
				}
			} else {
				// Fallback: if subjects are not configured properly for SS, do not apply a forced -1 filter.
				// This prevents false "No records" while still allowing explicit subject filtering.
				if ($typed !== '') {
					$query_filters['subject_code'] = $typed;
				}
			}
		}

		$this->page_data['questions'] = $this->emarking->get_questions($query_filters);
		$this->load->view('admin/emarking/questions', $this->page_data);
	}

	public function add_question()
	{
		$this->page_data['page']->submenu = 'questions';
		$this->page_data['page']->title = 'Add Question';
		$this->page_data['subject_options'] = $this->subject_code_map;
		if ($this->current_role() === 18) {
			$allowed = $this->ss_allowed_subject_codes();
			$opts = [];
			foreach ($this->subject_code_map as $code => $name) {
				if (in_array((string) $code, $allowed, true)) $opts[$code] = $name;
			}
			$this->page_data['subject_options'] = $opts;
		}

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
		$this->page_data['subject_options'] = $this->subject_code_map;
		if ($this->current_role() === 18) {
			$allowed = $this->ss_allowed_subject_codes();
			$opts = [];
			foreach ($this->subject_code_map as $code => $name) {
				if (in_array((string) $code, $allowed, true)) $opts[$code] = $name;
			}
			$this->page_data['subject_options'] = $opts;
		}

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
				$files_to_delete = [];

				// Allow removal/replacement of already uploaded files on edit
				$remove_sample = (int) $this->input->post('remove_sample_answer_file', true) === 1;
				$remove_guide = (int) $this->input->post('remove_guide_file', true) === 1;
				$remove_paper = (int) $this->input->post('remove_question_paper_file', true) === 1;

				if ($remove_sample && !empty($question->sample_answer_file)) {
					$payload['sample_answer_file'] = null;
					$files_to_delete[] = (string) $question->sample_answer_file;
				}
				if ($remove_guide && !empty($question->guide_file)) {
					$payload['guide_file'] = null;
					$files_to_delete[] = (string) $question->guide_file;
				}
				if ($remove_paper && !empty($question->question_paper_file)) {
					$payload['question_paper_file'] = null;
					$files_to_delete[] = (string) $question->question_paper_file;
				}

				// If new file uploaded, delete old one after successful save (avoid orphaned files)
				if (!empty($payload['sample_answer_file']) && !empty($question->sample_answer_file)) {
					$files_to_delete[] = (string) $question->sample_answer_file;
				}
				if (!empty($payload['guide_file']) && !empty($question->guide_file)) {
					$files_to_delete[] = (string) $question->guide_file;
				}
				if (!empty($payload['question_paper_file']) && !empty($question->question_paper_file)) {
					$files_to_delete[] = (string) $question->question_paper_file;
				}
				$files_to_delete = array_values(array_unique(array_filter($files_to_delete, function ($p) { return trim((string) $p) !== ''; })));

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
					foreach ($files_to_delete as $rel) {
						$this->safe_unlink_relative($rel);
					}
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

	public function remove_question_file()
	{
		postAllowed();
		$question_id = (int) $this->input->post('question_id', true);
		$field = trim((string) $this->input->post('field', true));
		$allowed = ['sample_answer_file', 'guide_file', 'question_paper_file'];

		if ($question_id <= 0 || !in_array($field, $allowed, true)) {
			$this->output
				->set_status_header(400)
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode(['ok' => false, 'message' => 'Invalid request.']));
			return;
		}

		$question = $this->emarking->get_question($question_id);
		if (!$question) show_404();

		$current = (string) ($question->{$field} ?? '');
		if (trim($current) === '') {
			$this->output
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode(['ok' => true, 'field' => $field]));
			return;
		}

		$ok = $this->emarking->clear_question_file($question_id, $field);
		if ($ok) {
			$this->safe_unlink_relative($current);
			$this->output
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode(['ok' => true, 'field' => $field]));
			return;
		}

		$this->output
			->set_status_header(500)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode(['ok' => false, 'message' => 'Unable to remove file.']));
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
			'interval' => $this->input->post('interval', true),
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
			$payload['interval'] = max(0, (float) $payload['interval']);
			if ($payload['interval'] <= 0) {
				$this->session->set_flashdata('message', 'Interval is required for RANGE and must be greater than 0.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/emarking/rubric_steps/' . $question_id);
				return;
			}
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
			$payload['interval'] = null;
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

	public function delete_rubric_steps_bulk()
	{
		postAllowed();

		$question_id = (int) $this->input->post('question_id', true);
		if ($question_id <= 0) show_404();

		$question = $this->emarking->get_question($question_id);
		if (!$question) show_404();

		$ids = $this->input->post('ids');
		if (!is_array($ids) || empty($ids)) {
			$this->session->set_flashdata('message', 'No steps selected.');
			$this->session->set_flashdata('message_type', 'warning');
			redirect('admin/emarking/rubric_steps/' . $question_id);
			return;
		}

		$ids = array_values(array_unique(array_filter(array_map(function ($v) {
			return (int) $v;
		}, $ids), function ($v) {
			return $v > 0;
		})));

		if (empty($ids)) {
			$this->session->set_flashdata('message', 'No valid steps selected.');
			$this->session->set_flashdata('message_type', 'warning');
			redirect('admin/emarking/rubric_steps/' . $question_id);
			return;
		}

		$deleted = 0;
		$skipped = 0;
		foreach ($ids as $id) {
			$step = $this->emarking->get_rubric_step((int) $id);
			if (!$step || (int) ($step->question_id ?? 0) !== $question_id) {
				$skipped++;
				continue;
			}
			$this->emarking->delete_rubric_step((int) $id);
			$deleted++;
		}

		if ($deleted > 0) {
			$msg = 'Deleted ' . $deleted . ' step(s).';
			if ($skipped > 0) $msg .= ' Skipped ' . $skipped . ' invalid step(s).';
			$this->session->set_flashdata('message', $msg);
			$this->session->set_flashdata('message_type', 'success');
		} else {
			$this->session->set_flashdata('message', 'No steps were deleted.');
			$this->session->set_flashdata('message_type', 'warning');
		}

		redirect('admin/emarking/rubric_steps/' . $question_id);
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
			if ($base_folder === '') $base_folder = 'storagebox/crqs';
			$batch_no = trim((string) $this->input->post('upload_batch_no', true));
			if ($batch_no === '') $batch_no = 'CRQ-' . date('Ymd-His');

			$result_crq = $this->emarking->import_images_from_folder($base_folder, 'CRQ', $batch_no);
		}

		$this->page_data['default_crq_path'] = 'storagebox/crqs';
		$this->page_data['default_dictation_path'] = 'storagebox/dictations';
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
			if ($base_folder === '') $base_folder = 'storagebox/dictations';
			$batch_no = trim((string) $this->input->post('upload_batch_no', true));
			if ($batch_no === '') $batch_no = 'DICT-' . date('Ymd-His');
			$result_dict = $this->emarking->import_images_from_folder($base_folder, 'DICTATION', $batch_no);
		}

		$this->page_data['default_crq_path'] = 'storagebox/crqs';
		$this->page_data['default_dictation_path'] = 'storagebox/dictations';
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
		// Grade is fixed to 4 on this screen.
		$get_filters['grade'] = '4';
		$this->page_data['subject_options'] = $this->subject_code_map;
		if ($this->current_role() === 18) {
			$allowed = $this->ss_allowed_subject_codes();
			$opts = [];
			foreach ($this->subject_code_map as $code => $name) {
				if (in_array((string) $code, $allowed, true)) $opts[$code] = $name;
			}
			$this->page_data['subject_options'] = $opts;
		}

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

			// Subject Specialist can only create batches for questions within assigned subjects,
			// and can only assign to eMarkers within those same subjects.
			if ($this->current_role() === 18) {
				$allowed_codes = $this->ss_allowed_subject_codes();
				$allowed_specs = $this->ss_allowed_subject_names();
				if (empty($allowed_codes) || empty($allowed_specs)) {
					$this->session->set_flashdata('message', 'No subjects are assigned to your account.');
					$this->session->set_flashdata('message_type', 'danger');
					redirect('admin/emarking/create_batch', 'refresh');
					return;
				}

				if (!in_array((string) $q->subject_code, $allowed_codes, true)) {
					$this->session->set_flashdata('message', 'You are not allowed to create batches for this subject.');
					$this->session->set_flashdata('message_type', 'danger');
					redirect('admin/emarking/create_batch', 'refresh');
					return;
				}

				// Ensure selected eMarker specialization matches the SS subject set AND the question subject.
				$specRow = $this->db->select('specialization')->get_where('teacher_specializations', ['user_id' => $emarker_id])->row();
				$emarkerSpec = strtoupper(trim((string) ($specRow->specialization ?? '')));
				$questionSpec = (string) ($this->subject_code_map[(int) $q->subject_code] ?? '');
				if ($emarkerSpec === '' || !in_array($emarkerSpec, $allowed_specs, true) || ($questionSpec !== '' && $emarkerSpec !== $questionSpec)) {
					$this->session->set_flashdata('message', 'Selected eMarker is not allowed for this subject.');
					$this->session->set_flashdata('message_type', 'danger');
					redirect('admin/emarking/create_batch', 'refresh');
					return;
				}
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

		// Do not show any questions/eMarkers until user applies required filters.
		$typed_subject_code = trim((string) ($get_filters['subject_code'] ?? ''));
		$typed_version = trim((string) ($get_filters['version'] ?? ''));
		$has_required_filters = ($typed_subject_code !== '' && $typed_version !== '');

		$questions = [];
		$emarkers = [];
		if ($has_required_filters) {
			$q_filters = array_merge($get_filters, ['status' => '1']);
			if ($this->current_role() === 18) {
				// SS should only see questions for their assigned subjects.
				$allowed_codes = $this->ss_allowed_subject_codes();
				// Validate typed subject_code filter is within allowed set.
				if ($typed_subject_code !== '' && in_array($typed_subject_code, $allowed_codes, true)) {
					// If SS typed a specific subject, restrict to that single subject.
					$q_filters['subject_code'] = $typed_subject_code;
				} else {
					// Otherwise, restrict to SS allowed subjects (or force empty).
					$q_filters['subject_code'] = !empty($allowed_codes) ? $allowed_codes : ['-1'];
				}
			}
			$questions = $this->emarking->get_questions($q_filters);

			// Filter eMarkers list by selected subject specialization.
			$subject_name = $this->subject_code_map[(int) $typed_subject_code] ?? null;
			if (!empty($subject_name)) {
				$specs = [strtoupper((string) $subject_name)];
				if ($this->current_role() === 18) {
					$allowed_specs = $this->ss_allowed_subject_names();
					$specs = array_values(array_intersect($specs, $allowed_specs));
				}
				$emarkers = !empty($specs) ? $this->emarking_batch->get_emarkers_by_specializations($specs) : [];
			} else {
				$emarkers = ($this->current_role() === 18)
					? $this->emarking_batch->get_emarkers_by_specializations($this->ss_allowed_subject_names())
					: $this->emarking_batch->get_emarkers();
			}
		}

		$this->page_data['questions'] = $questions;
		$this->page_data['emarkers'] = $emarkers;

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
		// Grade fixed to 4 for this module UI.
		$filters['grade'] = '4';
		$this->page_data['subject_options'] = $this->subject_code_map;
		if ($this->current_role() === 18) {
			$allowed = $this->ss_allowed_subject_codes();
			$opts = [];
			foreach ($this->subject_code_map as $code => $name) {
				if (in_array((string) $code, $allowed, true)) $opts[$code] = $name;
			}
			$this->page_data['subject_options'] = $opts;
		}

		$this->page_data['filters'] = $filters;
		$query_filters = $filters;
		if ($this->current_role() === 18) {
			$allowed_codes = $this->ss_allowed_subject_codes();
			$typed = trim((string) ($filters['subject_code'] ?? ''));
			if ($typed !== '' && !in_array($typed, $allowed_codes, true)) {
				$query_filters['subject_code'] = ['-1'];
			} else {
				$query_filters['subject_code'] = ($typed !== '') ? $typed : $allowed_codes;
			}
		}

		$this->page_data['batches'] = $this->emarking_batch->get_batches($query_filters);
		$this->load->view('admin/emarking/batches', $this->page_data);
	}

	public function emarker_timers()
	{
		// Only administrators can access eMarker timers.
		// This URL must not be accessible to Subject Specialist or other roles.
		if (!in_array($this->current_role(), [1, 17], true)) {
			redirect('errors/permission_denied');
			die;
		}

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
		// Backward compat: default to Question-wise Summary page.
		redirect('admin/emarking/reports_questions');
	}

	private function reports_filters_from_get()
	{
		return [
			'from' => trim((string) $this->input->get('from', true)),
			'to' => trim((string) $this->input->get('to', true)),
			'assessment_type' => trim((string) $this->input->get('assessment_type', true)),
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
		];
	}

	private function load_reports_tab($tab)
	{
		$this->page_data['page']->submenu = 'reports';
		$this->page_data['reports_tab'] = (string) $tab;

		$filters = $this->reports_filters_from_get();
		$this->page_data['filters'] = $filters;

		if ($tab === 'subjects') {
			$this->page_data['page']->title = 'Subject-wise Summary';
			$this->page_data['subject_summary'] = $this->emarking_report->get_subject_summary($filters);
		} elseif ($tab === 'emarkers') {
			$this->page_data['page']->title = 'eMarker-wise Summary';
			$this->page_data['emarker_summary'] = $this->emarking_report->get_emarker_summary($filters);
		} elseif ($tab === 'batches') {
			$this->page_data['page']->title = 'Batch-wise Summary';
			$this->page_data['batch_summary'] = $this->emarking_report->get_batch_summary($filters);
		} else {
			// questions (default)
			$this->page_data['page']->title = 'Question-wise Summary';
			$this->page_data['overall_summary'] = $this->emarking_report->get_overall_summary($filters);
			$this->page_data['question_summary'] = $this->emarking_report->get_reports_summary($filters);
		}

		$this->load->view('admin/emarking/reports_tab', $this->page_data);
	}

	public function reports_questions()
	{
		$this->load_reports_tab('questions');
	}

	public function reports_subjects()
	{
		$this->load_reports_tab('subjects');
	}

	public function reports_emarkers()
	{
		$this->load_reports_tab('emarkers');
	}

	public function reports_batches()
	{
		$this->load_reports_tab('batches');
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
