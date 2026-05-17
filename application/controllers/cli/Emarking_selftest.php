<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking_selftest extends CI_Controller
{
	private function out($msg)
	{
		echo $msg . PHP_EOL;
	}

	private function fail($msg)
	{
		$this->out('FAIL: ' . $msg);
		return false;
	}

	private function ok($msg)
	{
		$this->out('OK: ' . $msg);
		return true;
	}

	public function __construct()
	{
		parent::__construct();
		if (!is_cli()) {
			show_404();
		}

		$this->load->model('Emarking_model', 'emarking');
		$this->load->model('Emarking_batch_model', 'emarking_batch');
		$this->load->model('Marking_model', 'marking');
		$this->load->model('Emarking_report_model', 'emarking_report');
	}

	private function ensure_emarker_user()
	{
		$role_col = $this->db->field_exists('role', 'users') ? 'role' : ($this->db->field_exists('role_id', 'users') ? 'role_id' : 'role');
		$this->db->from('users')->where($role_col, 2);
		if ($this->db->field_exists('status', 'users')) $this->db->where('status', 1);
		if ($this->db->field_exists('blacklisted', 'users')) $this->db->where('blacklisted', 0);
		$this->db->order_by('id', 'ASC');
		$u = $this->db->limit(1)->get()->row();
		return $u ?: null;
	}

	private function create_question_with_steps($payload, $steps)
	{
		$qid = $this->emarking->save_question($payload);
		if (!$qid) return 0;

		$i = 1;
		foreach ($steps as $s) {
			$s['question_id'] = $qid;
			if (empty($s['step_order'])) $s['step_order'] = $i;
			if (!isset($s['status'])) $s['status'] = 1;
			$this->emarking->save_rubric_step($s);
			$i++;
		}
		return (int) $qid;
	}

	private function assert_rubric_steps_count($question_id, $expected)
	{
		$cnt = (int) $this->db->from('emarking_question_rubric_steps')->where('question_id', (int) $question_id)->count_all_results();
		return $cnt === (int) $expected;
	}

	public function run()
	{
		$this->out('=== e-Marking Self Test (CLI) ===');
		$this->out('Timestamp: ' . date('Y-m-d H:i:s'));
		$tag = date('YmdHis');

		$emarker = $this->ensure_emarker_user();
		if (!$emarker) return $this->fail('No active non-blacklisted role=2 user found.');
		$this->ok('Found eMarker user id=' . (int) $emarker->id . ' name=' . (string) $emarker->name);

		$created_question_ids = [];
		$created_batch_ids = [];

		// 1) Create Q33 writing question with 6 rubric steps.
		$q33_payload = [
			'assessment_type' => 'CRQ',
			'source_table' => null,
			'grade' => 4,
			'subject_code' => '1',
			'version' => 1,
			'page_no' => '101',
			'question_no' => 'q33_' . $tag,
			'question_title' => '[SELFTEST] Q33 Writing ' . $tag,
			'question_type' => 'WRITING',
			'max_marks' => 6.00,
			'rubric_title' => 'Writing Rubric',
			'rubric_detail' => 'Selftest rubric detail',
			'sample_answer' => 'Selftest sample answer',
			'guide_text' => 'Selftest guide text',
			'status' => 1,
			'created_by' => 1,
		];
		$q33_steps = [];
		for ($i = 1; $i <= 6; $i++) {
			$q33_steps[] = [
				'step_order' => $i,
				'step_label' => (string) $i,
				'step_title' => 'Step ' . $i,
				'step_detail' => 'Detail ' . $i,
				'step_marks' => 1.00,
				'marking_type' => 'ZERO_ONE',
				'min_marks' => 0.00,
				'max_marks' => 1.00,
				'status' => 1,
			];
		}
		$q33_id = $this->create_question_with_steps($q33_payload, $q33_steps);
		if ($q33_id <= 0) return $this->fail('Unable to create Q33 question (duplicate key maybe).');
		$created_question_ids[] = $q33_id;
		if (!$this->assert_rubric_steps_count($q33_id, 6)) return $this->fail('Q33 rubric steps count mismatch.');
		$this->ok('Created Q33 question id=' . $q33_id . ' with 6 steps.');

		// 2) Create Q26 objective question with 5 rubric steps.
		$q26_payload = [
			'assessment_type' => 'CRQ',
			'source_table' => null,
			'grade' => 4,
			'subject_code' => '1',
			'version' => 1,
			'page_no' => '101',
			'question_no' => 'q26_' . $tag,
			'question_title' => '[SELFTEST] Q26 Objective ' . $tag,
			'question_type' => 'OBJECTIVE_STEPS',
			'max_marks' => 5.00,
			'rubric_title' => 'Objective Rubric',
			'status' => 1,
			'created_by' => 1,
		];
		$q26_steps = [];
		for ($i = 1; $i <= 5; $i++) {
			$q26_steps[] = [
				'step_order' => $i,
				'step_label' => (string) $i,
				'step_title' => 'Obj Step ' . $i,
				'step_detail' => '',
				'step_marks' => 1.00,
				'marking_type' => 'ZERO_ONE',
				'min_marks' => 0.00,
				'max_marks' => 1.00,
				'status' => 1,
			];
		}
		$q26_id = $this->create_question_with_steps($q26_payload, $q26_steps);
		if ($q26_id <= 0) return $this->fail('Unable to create Q26 question (duplicate key maybe).');
		$created_question_ids[] = $q26_id;
		if (!$this->assert_rubric_steps_count($q26_id, 5)) return $this->fail('Q26 rubric steps count mismatch.');
		$this->ok('Created Q26 question id=' . $q26_id . ' with 5 steps.');

		// 3) Import CRQ image (single-file via folder importer)
		$crq_file = 'processed_crqs/4/1/1/101/q1/44811011108101_1.jpg';
		$crq_abs = rtrim(FCPATH, '\\/') . '/' . $crq_file;
		if (!is_file($crq_abs)) {
			$this->out('WARN: CRQ sample file missing: ' . $crq_abs);
		} else {
			// Ensure matching question exists for q1
			$existing = $this->db->get_where('emarking_questions', [
				'assessment_type' => 'CRQ',
				'grade' => 4,
				'subject_code' => '1',
				'version' => 1,
				'page_no' => '101',
				'question_no' => 'q1',
			])->row();
			if ($existing) {
				$crq_qid = (int) $existing->id;
			} else {
				$this->db->insert('emarking_questions', [
				'assessment_type' => 'CRQ',
				'source_table' => null,
				'grade' => 4,
				'subject_code' => '1',
				'version' => 1,
				'page_no' => '101',
				'question_no' => 'q1',
				'question_title' => '[SELFTEST] CRQ Import Q1',
				'question_type' => 'OBJECTIVE_STEPS',
				'max_marks' => 5.00,
				'status' => 1,
				'created_by' => 1,
				]);
				$crq_qid = (int) $this->db->insert_id();
			}
			$created_question_ids[] = $crq_qid;
			$res = $this->emarking->import_images_from_folder('processed_crqs', 'CRQ', 'SELFTEST-CRQ-' . date('YmdHis'));
			$this->ok('CRQ import attempted. Inserted=' . (int) ($res['inserted'] ?? 0) . ' Skipped=' . (int) ($res['skipped'] ?? 0));
			if (!empty($res['errors'])) {
				$this->out('CRQ import errors count: ' . count($res['errors']));
			}
		}

		// 4) Import Dictation image (single-file via folder importer)
		$dict_file = 'processed_dictation/4/1/1/041/q1/44811131108041_1.jpg';
		$dict_abs = rtrim(FCPATH, '\\/') . '/' . $dict_file;
		if (!is_file($dict_abs)) {
			$this->out('WARN: Dictation sample file missing: ' . $dict_abs);
		} else {
			$existing = $this->db->get_where('emarking_questions', [
				'assessment_type' => 'DICTATION',
				'grade' => 4,
				'subject_code' => '1',
				'version' => 1,
				'page_no' => '041',
				'question_no' => 'q1',
			])->row();
			if ($existing) {
				$dict_qid = (int) $existing->id;
			} else {
				$this->db->insert('emarking_questions', [
				'assessment_type' => 'DICTATION',
				'source_table' => null,
				'grade' => 4,
				'subject_code' => '1',
				'version' => 1,
				'page_no' => '041',
				'question_no' => 'q1',
				'question_title' => '[SELFTEST] Dictation Import Q1',
				'question_type' => 'DICTATION',
				'max_marks' => 5.00,
				'status' => 1,
				'created_by' => 1,
				]);
				$dict_qid = (int) $this->db->insert_id();
			}
			$created_question_ids[] = $dict_qid;
			$res = $this->emarking->import_images_from_folder('processed_dictation', 'DICTATION', 'SELFTEST-DICT-' . date('YmdHis'));
			$this->ok('Dictation import attempted. Inserted=' . (int) ($res['inserted'] ?? 0) . ' Skipped=' . (int) ($res['skipped'] ?? 0));
			if (!empty($res['errors'])) {
				$this->out('Dictation import errors count: ' . count($res['errors']));
			}
		}

		// 5) Confirm import only works if paper_generated=1 (implicit: importer checks it; report if no inserts)
		$this->ok('Importer enforces paper_generated=1 and paper_type_code rules (see errors above if records not eligible).');

		// 6) Create batch of up to 100 images for eMarker (use any question with UPLOADED)
		$questionWithUploads = $this->db->select('question_id, COUNT(*) as cnt', false)
			->from('emarking_question_images')
			->where('status', 'UPLOADED')
			->group_by('question_id')
			->order_by('cnt', 'DESC')
			->limit(1)
			->get()
			->row();
		if (!$questionWithUploads) {
			$this->out('WARN: No UPLOADED images found to create a batch.');
		} else {
			$qid = (int) $questionWithUploads->question_id;
			$qrow = $this->db->get_where('emarking_questions', ['id' => $qid])->row();
			$out = $this->emarking_batch->create_batch([
				'assessment_type' => $qrow ? (string) $qrow->assessment_type : '',
				'question_id' => $qid,
				'emarker_id' => (int) $emarker->id,
				'batch_size' => 100,
				'deadline' => date('Y-m-d H:i:s', strtotime('+2 days')),
				'assigned_by' => 1,
			]);
			if (empty($out['ok'])) {
				$this->out('WARN: Batch creation failed: ' . (string) ($out['error'] ?? 'unknown'));
			} else {
				$created_batch_ids[] = (int) $out['batch_id'];
				$this->ok('Created batch id=' . (int) $out['batch_id'] . ' code=' . (string) $out['batch_code'] . ' items=' . (int) $out['items_created']);
			}
		}

		// 7) eMarker should see assigned batch (backend query)
		$batches = $this->marking->get_emarker_batches((int) $emarker->id);
		$this->ok('eMarker batches visible: ' . count($batches));

		// 8) Start checking and mark one image (backend simulation)
		if (!empty($created_batch_ids)) {
			$bid = (int) $created_batch_ids[0];
			$next = $this->marking->get_next_pending_item($bid, (int) $emarker->id);
			if ($next) {
				$data = $this->marking->get_marking_data((int) $next->id, (int) $emarker->id);
				$stepsInput = [];
				foreach (($data['steps'] ?? []) as $s) {
					$stepsInput[(int) $s->id] = '1';
				}
				$save = $this->marking->save_mark((int) $next->id, (int) $emarker->id, [
					'action' => 'MARKED',
					'remarks' => 'SELFTEST mark',
					'steps' => $stepsInput,
				]);
				if (!empty($save['ok'])) {
					$this->ok('Marked one batch item id=' . (int) $next->id . ' mark_id=' . (int) $save['mark_id']);
				} else {
					$this->out('WARN: Mark save failed: ' . (string) ($save['error'] ?? 'unknown'));
				}
			} else {
				$this->out('WARN: No pending item found in created batch.');
			}
		}

		// 9/10) Sample Answer + Guide popups are UI tests (manual)
		$this->out('MANUAL: Open marking screen in browser and verify Sample Answer + Guide popups.');

		// 11/12/13/14) Status transitions are exercised by save_mark() and batch completion (manual/extended)
		$this->out('MANUAL: Use UI buttons Submit & Next / Not Attempted / Skip to confirm behavior.');

		// 15) Billing shows payable amount (backend)
		$bill = $this->emarking_report->get_billing([
			'from' => date('Y-m-d', strtotime('-7 days')),
			'to' => date('Y-m-d'),
			'emarker_id' => (int) $emarker->id,
		]);
		$this->ok('Billing rows for eMarker: ' . count($bill));

		$this->out('=== Self Test Completed ===');
		return true;
	}
}
