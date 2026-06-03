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

	private function ensure_english_subject_access()
	{
		if ($this->current_role() !== 18) return;

		$allowed_codes = $this->ss_allowed_subject_codes();
		if (!in_array('1', $allowed_codes, true)) {
			redirect('errors/permission_denied');
			die;
		}
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

	private function import_progress_dir()
	{
		$dir = rtrim((string) FCPATH, '\\/') . '/storagebox/import_progress';
		$dir = str_replace(['\\', '//'], ['/', '/'], $dir);
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
			@chmod($dir, 0777);
		}
		if (!is_dir($dir) || !is_writable($dir)) {
			// Try to relax permissions; if still not writable, caller will return an error.
			@chmod($dir, 0777);
		}
		return $dir;
	}

	private function sanitize_batch_key($s)
	{
		$s = trim((string) $s);
		$s = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $s);
		$s = trim($s, '._-');
		return $s !== '' ? $s : ('batch_' . date('YmdHis'));
	}

	private function progress_paths($assessment_type, $upload_batch_no)
	{
		$assessment_type = strtoupper((string) $assessment_type);
		$key = $this->sanitize_batch_key($assessment_type . '_' . $upload_batch_no);
		$dir = $this->import_progress_dir();
		return [
			'key' => $key,
			'progress' => $dir . '/' . $key . '.json',
			'manifest' => $dir . '/' . $key . '.manifest.txt',
		];
	}

	private function json($payload, $http_code = 200)
	{
		$this->output
			->set_status_header((int) $http_code)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload));
	}

	public function import_async_start()
	{
		postAllowed();
		@set_time_limit(0);

		$assessment_type = strtoupper(trim((string) $this->input->post('assessment_type', true)));
		if (!in_array($assessment_type, ['CRQ', 'DICTATION'], true)) {
			$this->json(['ok' => false, 'error' => 'Invalid assessment_type'], 422);
			return;
		}

		$base_folder = trim((string) $this->input->post('base_folder', true));
		if ($base_folder === '') {
			$base_folder = $assessment_type === 'CRQ' ? 'storagebox/crqs' : 'storagebox/dictations';
		}

		$upload_batch_no = trim((string) $this->input->post('upload_batch_no', true));
		if ($upload_batch_no === '') {
			$upload_batch_no = ($assessment_type === 'CRQ' ? 'CRQ-' : 'DICT-') . date('Ymd-His');
		}

		$chunk_size = (int) $this->input->post('chunk_size', true);
		if ($chunk_size <= 0) $chunk_size = 400;
		if ($chunk_size > 5000) $chunk_size = 5000;

		$paths = $this->progress_paths($assessment_type, $upload_batch_no);
		$progress_dir = $this->import_progress_dir();
		if (!is_dir($progress_dir) || !is_writable($progress_dir)) {
			$this->json(['ok' => false, 'error' => 'Import progress directory is not writable', 'dir' => $progress_dir], 500);
			return;
		}

		// Resolve absolute base folder
		$abs_base = str_replace('\\', '/', rtrim($base_folder, '/'));
		if (strpos($abs_base, ':') === false && strpos($abs_base, '/') !== 0) {
			$abs_base = rtrim(FCPATH, '\\/') . '/' . ltrim($base_folder, '/');
		}
		$abs_base = str_replace(['\\', '//'], ['/', '/'], $abs_base);

		if (!is_dir($abs_base)) {
			$this->json(['ok' => false, 'error' => 'Base folder not found', 'base_folder' => $abs_base], 422);
			return;
		}

		// Build manifest (absolute paths), deterministic order.
		$manifest_fp = @fopen($paths['manifest'], 'wb');
		if (!$manifest_fp) {
			$this->json(['ok' => false, 'error' => 'Unable to create manifest file', 'manifest' => $paths['manifest']], 500);
			return;
		}

		$total = 0;
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($abs_base, FilesystemIterator::SKIP_DOTS)
			);
			foreach ($it as $fileInfo) {
				/** @var SplFileInfo $fileInfo */
				if (!$fileInfo->isFile()) continue;
				$ext = strtolower((string) $fileInfo->getExtension());
				if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) continue;

				$abs_path = str_replace('\\', '/', $fileInfo->getPathname());
				@fwrite($manifest_fp, $abs_path . "\n");
				$total++;
			}
		} catch (Exception $e) {
			@fclose($manifest_fp);
			$this->json(['ok' => false, 'error' => 'Failed to scan base folder', 'message' => $e->getMessage(), 'base_folder' => $abs_base], 500);
			return;
		}
		@fclose($manifest_fp);

		$progress = [
			'ok' => true,
			'status' => 'running',
			'assessment_type' => $assessment_type,
			'base_folder' => $base_folder,
			'abs_base' => $abs_base,
			'upload_batch_no' => $upload_batch_no,
			'chunk_size' => $chunk_size,
			'cursor' => 0,
			'cursor_offset' => 0,
			'total' => $total,
			'inserted' => 0,
			'skipped' => 0,
			'errors_count' => 0,
			'last_errors' => [],
			'started_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		@file_put_contents($paths['progress'], json_encode($progress));

		$this->json(['ok' => true, 'key' => $paths['key'], 'upload_batch_no' => $upload_batch_no]);
	}

	public function import_async_status()
	{
		$key = $this->sanitize_batch_key($this->input->get('key', true));
		if ($key === '') {
			$this->json(['ok' => false, 'error' => 'Missing key'], 422);
			return;
		}

		$dir = $this->import_progress_dir();
		$progressPath = $dir . '/' . $key . '.json';
		if (!is_file($progressPath)) {
			$this->json(['ok' => false, 'error' => 'Not found'], 404);
			return;
		}

		$raw = @file_get_contents($progressPath);
		$data = $raw ? json_decode($raw, true) : null;
		if (!is_array($data)) {
			$this->json(['ok' => false, 'error' => 'Invalid progress file'], 500);
			return;
		}

		$this->json(['ok' => true, 'progress' => $data]);
	}

	public function import_async_tick()
	{
		postAllowed();
		$key = $this->sanitize_batch_key($this->input->post('key', true));
		if ($key === '') {
			$this->json(['ok' => false, 'error' => 'Missing key'], 422);
			return;
		}

		$dir = $this->import_progress_dir();
		$progressPath = $dir . '/' . $key . '.json';
		$manifestPath = $dir . '/' . $key . '.manifest.txt';
		if (!is_file($progressPath) || !is_file($manifestPath)) {
			$this->json(['ok' => false, 'error' => 'Not found'], 404);
			return;
		}

		$raw = @file_get_contents($progressPath);
		$progress = $raw ? json_decode($raw, true) : null;
		if (!is_array($progress) || empty($progress['ok'])) {
			$this->json(['ok' => false, 'error' => 'Invalid progress file'], 500);
			return;
		}
		if (($progress['status'] ?? '') === 'done') {
			$this->json(['ok' => true, 'progress' => $progress]);
			return;
		}

		$cursor = (int) ($progress['cursor'] ?? 0);
		$cursor_offset = isset($progress['cursor_offset']) ? (int) $progress['cursor_offset'] : null;
		$total = (int) ($progress['total'] ?? 0);
		$chunk = (int) ($progress['chunk_size'] ?? 400);
		$chunk = max(1, min(5000, $chunk));

		$abs_base = (string) ($progress['abs_base'] ?? '');
		$base_folder = (string) ($progress['base_folder'] ?? '');
		$assessment_type = (string) ($progress['assessment_type'] ?? '');
		$upload_batch_no = (string) ($progress['upload_batch_no'] ?? '');

		$paths = [];
		$read = 0;
		$advance_offset = 0;

		// Faster manifest reading: keep byte offset to avoid O(n) line seek on every tick.
		// If cursor_offset is missing (older progress file), compute it once from cursor.
		if ($cursor_offset === null && $cursor > 0) {
			$fp2 = @fopen($manifestPath, 'rb');
			if ($fp2) {
				$lineNo = 0;
				while (!feof($fp2) && $lineNo < $cursor) {
					$line = fgets($fp2);
					if ($line === false) break;
					$lineNo++;
				}
				$cursor_offset = (int) @ftell($fp2);
				fclose($fp2);
			} else {
				$cursor_offset = null;
			}
		}

		if ($cursor_offset !== null) {
			$fp = @fopen($manifestPath, 'rb');
			if ($fp) {
				@fseek($fp, max(0, $cursor_offset));
				while (!feof($fp) && $read < $chunk) {
					$line = fgets($fp);
					if ($line === false) break;
					$line = trim((string) $line);
					if ($line === '') continue;
					$paths[] = $line;
					$read++;
				}
				$advance_offset = (int) @ftell($fp);
				fclose($fp);
			}
		} else {
			// Backward compatibility for old progress files.
			$f = new SplFileObject($manifestPath, 'r');
			$f->seek($cursor);
			while (!$f->eof() && $read < $chunk) {
				$line = trim((string) $f->current());
				$f->next();
				if ($line === '') continue;
				$paths[] = $line;
				$read++;
			}
		}

		if (empty($paths)) {
			$progress['status'] = 'done';
			$progress['cursor'] = $total;
			$progress['updated_at'] = date('Y-m-d H:i:s');
			@file_put_contents($progressPath, json_encode($progress));
			$this->json(['ok' => true, 'progress' => $progress]);
			return;
		}

		$out = $this->emarking->import_images_from_abs_paths($abs_base, $base_folder, $assessment_type, $upload_batch_no, $paths);

		$progress['cursor'] = $cursor + $read;
		if ($cursor_offset !== null) {
			$progress['cursor_offset'] = $advance_offset;
		}
		$progress['inserted'] = (int) ($progress['inserted'] ?? 0) + (int) ($out['inserted'] ?? 0);
		$progress['skipped'] = (int) ($progress['skipped'] ?? 0) + (int) ($out['skipped'] ?? 0);
		$errs = is_array($out['errors'] ?? null) ? $out['errors'] : [];
		$progress['errors_count'] = (int) ($progress['errors_count'] ?? 0) + count($errs);
		if (!empty($errs)) {
			$last = is_array($progress['last_errors'] ?? null) ? $progress['last_errors'] : [];
			foreach ($errs as $e) {
				$last[] = [
					'file' => (string) ($e['file'] ?? ''),
					'reason' => (string) ($e['reason'] ?? ''),
				];
			}
			// keep only last 50
			if (count($last) > 50) {
				$last = array_slice($last, -50);
			}
			$progress['last_errors'] = $last;
		}

		if ($progress['cursor'] >= $total) {
			$progress['status'] = 'done';
		}
		$progress['updated_at'] = date('Y-m-d H:i:s');

		@file_put_contents($progressPath, json_encode($progress));
		$this->json(['ok' => true, 'progress' => $progress]);
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
		$this->load->library('pagination');

		$per_page = (int) $this->input->get('per_page', true);
		$allowed_per_page = [100, 200, 500];
		if (!in_array($per_page, $allowed_per_page, true)) {
			$per_page = 100;
		}
		$page = (int) $this->input->get('page', true);
		$page = $page > 0 ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$filters = [
			'status' => trim((string) $this->input->get('status', true)),
			'assessment_type' => trim((string) $this->input->get('assessment_type', true)),
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'assigned_to' => trim((string) $this->input->get('assigned_to', true)),
			'question_id' => trim((string) $this->input->get('question_id', true)),
			'per_page' => $per_page,
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

		$total = $this->emarking_batch->count_batches($query_filters);
		$config = [
			'base_url' => url('admin/emarking/batches'),
			'total_rows' => $total,
			'per_page' => $per_page,
			'page_query_string' => true,
			'query_string_segment' => 'page',
			'use_page_numbers' => true,
			'reuse_query_string' => true,
			'full_tag_open' => '<ul class="pagination pagination-sm mb-0">',
			'full_tag_close' => '</ul>',
			'first_link' => 'First',
			'first_tag_open' => '<li class="page-item">',
			'first_tag_close' => '</li>',
			'last_link' => 'Last',
			'last_tag_open' => '<li class="page-item">',
			'last_tag_close' => '</li>',
			'next_link' => '&raquo;',
			'next_tag_open' => '<li class="page-item">',
			'next_tag_close' => '</li>',
			'prev_link' => '&laquo;',
			'prev_tag_open' => '<li class="page-item">',
			'prev_tag_close' => '</li>',
			'cur_tag_open' => '<li class="page-item active"><a class="page-link" href="#">',
			'cur_tag_close' => '</a></li>',
			'num_tag_open' => '<li class="page-item">',
			'num_tag_close' => '</li>',
			'attributes' => ['class' => 'page-link'],
		];
		$this->pagination->initialize($config);

		$this->page_data['batches'] = $this->emarking_batch->get_batches($query_filters, $per_page, $offset);
		$subject_transfer_emarkers = [];
		if ($this->current_role() === 1) {
			foreach ($this->subject_code_map as $subject_code => $subject_name) {
				$subject_transfer_emarkers[(string) $subject_code] = $this->emarking_batch->get_emarkers_for_subject_code($subject_code);
			}
		}
		$this->page_data['subject_transfer_emarkers'] = $subject_transfer_emarkers;
		$this->page_data['pagination_links'] = $this->pagination->create_links();
		$this->page_data['total_rows'] = $total;
		$this->page_data['offset'] = $offset;
		$this->page_data['per_page'] = $per_page;
		$this->page_data['current_page'] = $page;
		$this->load->view('admin/emarking/batches', $this->page_data);
	}

	public function transfer_batch()
	{
		if ((int) logged('role') !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		if (strtoupper((string) $this->input->method()) !== 'POST') {
			redirect('admin/emarking/batches');
			return;
		}

		$batch_id = (int) $this->input->post('batch_id', true);
		$new_emarker_id = (int) $this->input->post('new_emarker_id', true);
		$remarks = trim((string) $this->input->post('remarks', true));

		$result = $this->emarking_batch->transfer_batch(
			$batch_id,
			$new_emarker_id,
			$this->current_user_id(),
			$remarks
		);

		if (!empty($result['ok'])) {
			$this->session->set_flashdata('message', 'Batch transferred successfully');
			$this->session->set_flashdata('message_type', 'success');
		} else {
			$this->session->set_flashdata('message', (string) ($result['error'] ?? 'Unable to transfer batch'));
			$this->session->set_flashdata('message_type', 'danger');
		}

		redirect('admin/emarking/batches?' . http_build_query($this->input->get(null, true)));
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
			$set_all_seconds_raw = $this->input->post('set_all_seconds', true);
			$set_all_seconds_raw = trim((string) $set_all_seconds_raw);
			$set_all_seconds = null;
			if ($set_all_seconds_raw !== '' && preg_match('/^\\d+$/', $set_all_seconds_raw)) {
				$set_all_seconds = (int) $set_all_seconds_raw;
				if ($set_all_seconds < 0) $set_all_seconds = 0;
			}

			$timers = (array) $this->input->post('timers');
			if ($set_all_seconds !== null) {
				$timers = [];
				foreach (($emarkers ?? []) as $u) {
					$uid = (int) ($u->id ?? 0);
					if ($uid <= 0) continue;
					$timers[$uid] = $set_all_seconds;
				}
			}
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

	private function dictation_csv_filters_from_get()
	{
		return [
			'assessment_type' => 'DICTATION',
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'version' => trim((string) $this->input->get('version', true)),
			'district_id' => trim((string) $this->input->get('district_id', true)),
			'school_query' => trim((string) $this->input->get('school_query', true)),
		];
	}

	private function crq_csv_filters_from_get()
	{
		return [
			'assessment_type' => 'CRQ',
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'version' => trim((string) $this->input->get('version', true)),
			'district_id' => trim((string) $this->input->get('district_id', true)),
			'school_query' => trim((string) $this->input->get('school_query', true)),
		];
	}

	private function mcq_csv_filters_from_get()
	{
		return [
			'assessment_type' => 'MCQ',
			'grade' => trim((string) $this->input->get('grade', true)),
			'subject_code' => trim((string) $this->input->get('subject_code', true)),
			'version' => trim((string) $this->input->get('version', true)),
			'district_id' => trim((string) $this->input->get('district_id', true)),
			'school_query' => trim((string) $this->input->get('school_query', true)),
		];
	}

	private function dictation_csv_has_narrowing_filters($filters)
	{
		return
			trim((string) ($filters['subject_code'] ?? '')) !== '' ||
			trim((string) ($filters['version'] ?? '')) !== '' ||
			trim((string) ($filters['district_id'] ?? '')) !== '' ||
			trim((string) ($filters['school_query'] ?? '')) !== '';
	}

	private function load_reports_tab($tab)
	{
		$this->page_data['page']->submenu = 'reports';
		$this->page_data['reports_tab'] = (string) $tab;

		$filters = $this->reports_filters_from_get();

		// Subject Specialist: restrict reports to assigned subjects and tabs.
		if ($this->current_role() === 18) {
			if (!in_array((string) $tab, ['questions', 'emarkers'], true)) {
				redirect('errors/permission_denied');
				die;
			}

			$allowed_codes = $this->ss_allowed_subject_codes();
			if (empty($allowed_codes)) {
				$this->session->set_flashdata('message', 'No subjects are assigned to your account.');
				$this->session->set_flashdata('message_type', 'danger');
				redirect('admin/dashboard/subject_specialist');
				return;
			}

			// Limit filter dropdown options to SS assigned subjects.
			$opts = [];
			foreach ($this->subject_code_map as $code => $name) {
				if (in_array((string) $code, $allowed_codes, true)) $opts[$code] = $name;
			}
			$this->page_data['subject_options'] = $opts;

			$typed = trim((string) ($filters['subject_code'] ?? ''));
			if ($typed !== '' && in_array($typed, $allowed_codes, true)) {
				$filters['subject_code'] = $typed;
			} else {
				// When SS does not select a subject, show all of their subjects.
				$filters['subject_code'] = $allowed_codes;
			}
		}
		$this->page_data['filters'] = $filters;

		if ($tab === 'subjects') {
			$this->page_data['page']->title = 'Subject-wise Summary';
			$this->page_data['subject_summary'] = $this->emarking_report->get_subject_summary($filters);
		} elseif ($tab === 'emarkers_payment') {
			// Admin-only report
			if ((int) logged('role') !== 1) {
				redirect('errors/permission_denied');
				die;
			}
			$this->page_data['page']->title = 'eMarker-wise Payment Summary';
			$this->page_data['emarker_payment_summary'] = $this->emarking_report->get_emarker_payment_summary($filters);
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

	public function reports_emarkers_payment_summary()
	{
		$this->load_reports_tab('emarkers_payment');
	}

	public function reports_batches()
	{
		$this->load_reports_tab('batches');
	}

	public function reports_eng_crqs_barcodes()
	{
		$this->ensure_english_subject_access();
		$this->load->library('pagination');
		$show_image_barcode = ((int) logged('role') === 1);

		$this->page_data['page']->submenu = 'reports';
		$this->page_data['reports_tab'] = 'eng_crqs_barcodes';
		$this->page_data['page']->title = 'ENG CRQs Barcodes';

		$selected_version = trim((string) $this->input->get('version', true));
		if ($selected_version !== '' && !ctype_digit($selected_version)) {
			$selected_version = '';
		}
		$selected_status = strtolower(trim((string) $this->input->get('status', true)));
		if (!in_array($selected_status, ['exist', 'missing'], true)) {
			$selected_status = '';
		}
		$per_page = (int) $this->input->get('per_page', true);
		$allowed_per_page = [100, 200, 500];
		if (!in_array($per_page, $allowed_per_page, true)) {
			$per_page = 100;
		}
		$page = (int) $this->input->get('page', true);
		$page = $page > 0 ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$versions = $this->emarking->get_eng_crq_barcode_versions();
		if ($selected_version !== '' && !in_array($selected_version, $versions, true)) {
			$selected_version = '';
		}

		$total = $this->emarking->count_eng_crq_barcodes($selected_version, $show_image_barcode, $selected_status);
		$config = [
			'base_url' => url('admin/emarking/reports_eng_crqs_barcodes'),
			'total_rows' => $total,
			'per_page' => $per_page,
			'page_query_string' => true,
			'query_string_segment' => 'page',
			'use_page_numbers' => true,
			'reuse_query_string' => true,
		];
		$this->pagination->initialize($config);

		$rows = $this->emarking->get_eng_crq_barcodes_page($selected_version, $per_page, $offset, $show_image_barcode, $selected_status);

		$this->page_data['barcode_versions'] = $versions;
		$this->page_data['selected_version'] = $selected_version;
		$this->page_data['selected_status'] = $selected_status;
		$this->page_data['barcode_rows'] = $rows;
		$this->page_data['show_image_barcode'] = $show_image_barcode;
		$this->page_data['barcode_total'] = $total;
		$this->page_data['barcode_page'] = $page;
		$this->page_data['barcode_per_page'] = $per_page;
		$this->page_data['pagination_links'] = $this->pagination->create_links();
		$this->load->view('admin/emarking/reports_eng_crqs_barcodes', $this->page_data);
	}

	public function export_eng_crqs_barcodes_csv()
	{
		$this->ensure_english_subject_access();
		$show_image_barcode = ((int) logged('role') === 1);

		$selected_version = trim((string) $this->input->get('version', true));
		if ($selected_version !== '' && !ctype_digit($selected_version)) {
			$selected_version = '';
		}
		$selected_status = strtolower(trim((string) $this->input->get('status', true)));
		if (!in_array($selected_status, ['exist', 'missing'], true)) {
			$selected_status = '';
		}

		$versions = $this->emarking->get_eng_crq_barcode_versions();
		if ($selected_version !== '' && !in_array($selected_version, $versions, true)) {
			$selected_version = '';
		}

		$ts = date('Ymd_His');
		$suffix = ($selected_version === '') ? 'all_versions' : ('version_' . $selected_version);
		$filename = 'eng_crqs_barcodes_' . $suffix . '_' . $ts . '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		$out = fopen('php://output', 'w');
		if ($out === false) {
			show_error('Unable to create export output stream.', 500);
			return;
		}

		$headers = ['Sr', 'Grade', 'Subject', 'Version', 'Type', 'Barcode', 'Status'];
		if ($show_image_barcode) {
			$headers[] = 'question_no';
			$headers[] = 'Image_Barcode';
		}
		fputcsv($out, $headers);

		$sr = 1;
		$chunk = 5000;
		$offset = 0;
		while (true) {
			$rows = $this->emarking->get_eng_crq_barcodes_page($selected_version, $chunk, $offset, $show_image_barcode, $selected_status);
			if (empty($rows)) {
				break;
			}

			foreach ((array) $rows as $row) {
				$line = [
					$sr++,
					(string) ($row->grade ?? '4'),
					'ENGLISH',
					(string) ($row->version ?? ''),
					'CRQ',
					(string) ($row->barcode ?? ''),
					(string) ($row->status ?? 'Missing'),
				];
				if ($show_image_barcode) {
					$line[] = (string) ($row->question_no ?? 'q1');
					$line[] = (string) ($row->image_barcode ?? '');
				}
				fputcsv($out, $line);
			}

			$offset += $chunk;
			if (function_exists('ob_get_level')) {
				while (ob_get_level() > 0) {
					@ob_end_flush();
				}
			}
			flush();

			if (count($rows) < $chunk) {
				break;
			}
		}

		fclose($out);
		die;
	}

	public function reports_dictation_csv()
	{
		if ($this->current_role() !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$this->page_data['page']->menu = 'results';
		$this->page_data['page']->submenu = 'dictation_csv';
		$this->page_data['page']->title = 'Dictation Result CSV';
		$this->page_data['reports_tab'] = 'dictation_csv';

		$filters = $this->dictation_csv_filters_from_get();
		if ($filters['grade'] === '') {
			$filters['grade'] = '4';
		}

		$this->load->model('admin/Location_model', 'location_model');
		$this->page_data['filters'] = $filters;
		$this->page_data['subject_options'] = [
			1 => 'ENGLISH',
			2 => 'URDU',
		];
		$this->page_data['districts'] = $this->location_model->get_districts();
		$this->page_data['version_options'] = ['1', '2'];
		$this->page_data['csv_headers'] = $this->emarking_report->get_dictation_csv_headers();
		$this->page_data['show_preview'] = $this->dictation_csv_has_narrowing_filters($filters);
		$this->page_data['preview_rows'] = $this->page_data['show_preview']
			? $this->emarking_report->get_dictation_csv_rows($filters, 50)
			: [];

		$this->load->view('admin/results/dictation_result_csv', $this->page_data);
	}

	public function export_dictation_results_csv()
	{
		if ($this->current_role() !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$filters = $this->dictation_csv_filters_from_get();
		$headers = $this->emarking_report->get_dictation_csv_headers();
		$filename = 'dictation_results_' . date('Ymd_His') . '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		$out = fopen('php://output', 'w');
		if ($out === false) {
			show_error('Unable to create CSV output stream.', 500);
			return;
		}

		fputcsv($out, $headers);
		$row_count = 0;
		$this->emarking_report->stream_dictation_csv_export($filters, function ($row) use ($out, $headers, &$row_count) {
			$line = [];
			foreach ($headers as $header_text) {
				$line[] = (string) ($row[$header_text] ?? '');
			}
			fputcsv($out, $line);
			$row_count++;

			if (($row_count % 500) === 0) {
				if (function_exists('ob_get_level')) {
					while (ob_get_level() > 0) {
						@ob_end_flush();
					}
				}
				flush();
			}
		});

		fclose($out);
		die;
	}

	public function reports_crq_csv()
	{
		if ($this->current_role() !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$this->page_data['page']->menu = 'results';
		$this->page_data['page']->submenu = 'crq_csv';
		$this->page_data['page']->title = 'CRQ Result CSV';

		$filters = $this->crq_csv_filters_from_get();
		if ($filters['grade'] === '') {
			$filters['grade'] = '4';
		}

		$this->load->model('admin/Location_model', 'location_model');
		$this->page_data['filters'] = $filters;
		$this->page_data['subject_options'] = [
			1 => 'ENGLISH',
			2 => 'URDU',
			3 => 'MATH',
			4 => 'SCIENCE',
		];
		$this->page_data['districts'] = $this->location_model->get_districts();
		$this->page_data['version_options'] = ['1', '2'];
		$this->page_data['csv_headers'] = $this->emarking_report->get_crq_csv_headers($filters);
		$this->page_data['show_preview'] = $this->dictation_csv_has_narrowing_filters($filters);
		$this->page_data['preview_rows'] = $this->page_data['show_preview']
			? $this->emarking_report->get_crq_csv_rows($filters, 50)
			: [];

		$this->load->view('admin/results/crq_result_csv', $this->page_data);
	}

	public function export_crq_results_csv()
	{
		if ($this->current_role() !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$filters = $this->crq_csv_filters_from_get();
		$headers = $this->emarking_report->get_crq_csv_headers($filters);
		$filename = 'crq_results_' . date('Ymd_His') . '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		$out = fopen('php://output', 'w');
		if ($out === false) {
			show_error('Unable to create CSV output stream.', 500);
			return;
		}

		fputcsv($out, $headers);
		$row_count = 0;
		$this->emarking_report->stream_crq_csv_export($filters, function ($row) use ($out, $headers, &$row_count) {
			$line = [];
			foreach ($headers as $header_text) {
				$line[] = (string) ($row[$header_text] ?? '');
			}
			fputcsv($out, $line);
			$row_count++;

			if (($row_count % 500) === 0) {
				if (function_exists('ob_get_level')) {
					while (ob_get_level() > 0) {
						@ob_end_flush();
					}
				}
				flush();
			}
		});

		fclose($out);
		die;
	}

	public function reports_mcq_csv()
	{
		if ($this->current_role() !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$this->page_data['page']->menu = 'results';
		$this->page_data['page']->submenu = 'mcq_csv';
		$this->page_data['page']->title = 'MCQ Result CSV';

		$filters = $this->mcq_csv_filters_from_get();
		if ($filters['grade'] === '') {
			$filters['grade'] = '4';
		}

		$this->load->model('admin/Location_model', 'location_model');
		$this->page_data['filters'] = $filters;
		$this->page_data['subject_options'] = [
			1 => 'ENGLISH',
			2 => 'URDU',
			3 => 'MATH',
			4 => 'SCIENCE',
		];
		$this->page_data['districts'] = $this->location_model->get_districts();
		$this->page_data['version_options'] = ['1', '2'];
		$this->page_data['show_preview'] = $this->dictation_csv_has_narrowing_filters($filters);
		$this->page_data['csv_headers'] = $this->page_data['show_preview']
			? $this->emarking_report->get_mcq_csv_headers($filters)
			: [
				'Unique Identifier',
				'School ID',
				'Student ID',
				'EMIS Code',
				'School Name',
				'District',
				'Tehsil',
				'School Admin',
				'School Level',
				'School Type',
				'Gender',
				'Grade',
				'Exam ID',
				'Subject',
				'Version',
				'Obtained Marks in Each Question',
			];
		$this->page_data['preview_rows'] = $this->page_data['show_preview']
			? $this->emarking_report->get_mcq_csv_rows($filters, 50)
			: [];

		$this->load->view('admin/results/mcq_result_csv', $this->page_data);
	}

	public function export_mcq_results_csv()
	{
		if ($this->current_role() !== 1) {
			redirect('errors/permission_denied');
			die;
		}

		$filters = $this->mcq_csv_filters_from_get();
		$headers = $this->emarking_report->get_mcq_csv_headers($filters);
		$filename = 'mcq_results_' . date('Ymd_His') . '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		$out = fopen('php://output', 'w');
		if ($out === false) {
			show_error('Unable to create CSV output stream.', 500);
			return;
		}

		fputcsv($out, $headers);
		$row_count = 0;
		$this->emarking_report->stream_mcq_csv_export($filters, function ($row) use ($out, $headers, &$row_count) {
			$line = [];
			foreach ($headers as $header_text) {
				$line[] = (string) ($row[$header_text] ?? '');
			}
			fputcsv($out, $line);
			$row_count++;

			if (($row_count % 500) === 0) {
				if (function_exists('ob_get_level')) {
					while (ob_get_level() > 0) {
						@ob_end_flush();
					}
				}
				flush();
			}
		});

		fclose($out);
		die;
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
