<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Marking_model extends CI_Model
{
	public function get_batch_total_items($batch_id, $emarker_id)
	{
		$this->db->from('emarking_batch_items i');
		$this->db->join('emarking_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->where('i.batch_id', (int) $batch_id);
		$this->db->where('b.assigned_to', (int) $emarker_id);
		return (int) $this->db->count_all_results();
	}

	public function get_batch_item_index($batch_id, $batch_item_id, $emarker_id)
	{
		// Index based on ascending id position within the batch.
		$this->db->from('emarking_batch_items i');
		$this->db->join('emarking_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->where('i.batch_id', (int) $batch_id);
		$this->db->where('b.assigned_to', (int) $emarker_id);
		$this->db->where('i.id <=', (int) $batch_item_id);
		return (int) $this->db->count_all_results();
	}

	public function get_emarker_stats($emarker_id)
	{
		$emarker_id = (int) $emarker_id;

		$batches = $this->db->select('status, COUNT(*) as cnt', false)
			->from('emarking_batches')
			->where('assigned_to', $emarker_id)
			->group_by('status')
			->get()
			->result();

		$out = [
			'batches_total' => 0,
			'batches_pending' => 0,
			'batches_in_progress' => 0,
			'batches_completed' => 0,
			'batches_finalized' => 0,
			'items_pending' => 0,
			'items_marked' => 0,
			'items_skipped' => 0,
			'items_not_attempted' => 0,
			'items_recheck' => 0,
		];

		foreach ($batches as $r) {
			$out['batches_total'] += (int) $r->cnt;
			$key = 'batches_' . strtolower((string) $r->status);
			if (isset($out[$key])) $out[$key] = (int) $r->cnt;
		}

		$items = $this->db->select('i.status, COUNT(*) as cnt', false)
			->from('emarking_batch_items i')
			->join('emarking_batches b', 'b.id = i.batch_id', 'inner')
			->where('b.assigned_to', $emarker_id)
			->group_by('i.status')
			->get()
			->result();

		foreach ($items as $r) {
			$key = 'items_' . strtolower((string) $r->status);
			if (isset($out[$key])) $out[$key] = (int) $r->cnt;
		}

		return $out;
	}

	public function get_emarker_batches($emarker_id)
	{
		$emarker_id = (int) $emarker_id;

		$this->db->select("b.*, q.question_no, q.question_title, q.max_marks", false);
		$this->db->select("COUNT(bi.id) AS total_questions", false);
		$this->db->select("SUM(CASE WHEN bi.status IN ('MARKED','NOT_ATTEMPTED','FINALIZED') THEN 1 ELSE 0 END) AS checked_questions", false);
		$this->db->select("SUM(CASE WHEN bi.status = 'PENDING' THEN 1 ELSE 0 END) AS pending_questions", false);
		$this->db->select("SUM(CASE WHEN bi.status = 'SKIPPED' THEN 1 ELSE 0 END) AS skipped_questions", false);
		$this->db->from('emarking_batches b');
		$this->db->join('emarking_questions q', 'q.id = b.question_id', 'left');
		$this->db->join('emarking_batch_items bi', 'bi.batch_id = b.id', 'left');
		$this->db->where('b.assigned_to', $emarker_id);
		$this->db->group_by('b.id');
		$this->db->order_by('b.id', 'DESC');
		return $this->db->get()->result();
	}

	public function get_batch_for_emarker($batch_id, $emarker_id)
	{
		$this->db->select('b.*, q.question_no, q.question_title, q.max_marks');
		$this->db->from('emarking_batches b');
		$this->db->join('emarking_questions q', 'q.id = b.question_id', 'left');
		$this->db->where('b.id', (int) $batch_id);
		$this->db->where('b.assigned_to', (int) $emarker_id);
		return $this->db->get()->row();
	}

	public function get_batch_items($batch_id, $emarker_id)
	{
		$this->db->select('i.*, qi.paper_barcode, qi.roll_no, qi.image_path, qi.status as image_status, m.marks_obtained, m.marking_status, m.marked_at');
		$this->db->from('emarking_batch_items i');
		$this->db->join('emarking_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->join('emarking_question_images qi', 'qi.id = i.question_image_id', 'inner');
		$this->db->join('emarking_marks m', 'm.batch_item_id = i.id AND m.emarker_id = b.assigned_to', 'left');
		$this->db->where('i.batch_id', (int) $batch_id);
		$this->db->where('b.assigned_to', (int) $emarker_id);
		$this->db->order_by('i.id', 'ASC');
		return $this->db->get()->result();
	}

	public function get_next_pending_item($batch_id, $emarker_id)
	{
		$this->db->select('i.id');
		$this->db->from('emarking_batch_items i');
		$this->db->join('emarking_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->where('i.batch_id', (int) $batch_id);
		$this->db->where('b.assigned_to', (int) $emarker_id);
		$this->db->where('i.status', 'PENDING');
		$this->db->order_by('i.id', 'ASC');
		return $this->db->get()->row();
	}

	public function get_marking_data($batch_item_id, $emarker_id)
	{
		$this->db->select('i.*, b.batch_code, b.status as batch_status, b.question_id, q.assessment_type, q.grade, q.subject_code, q.version, q.page_no, q.question_no, q.question_title, q.question_type, q.max_marks, q.rubric_title, q.rubric_detail, q.sample_answer, q.sample_answer_file, q.guide_text, q.guide_file, qi.paper_barcode, qi.roll_no, qi.image_path, qi.id as question_image_id');
		$this->db->from('emarking_batch_items i');
		$this->db->join('emarking_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->join('emarking_questions q', 'q.id = b.question_id', 'inner');
		$this->db->join('emarking_question_images qi', 'qi.id = i.question_image_id', 'inner');
		$this->db->where('i.id', (int) $batch_item_id);
		$this->db->where('b.assigned_to', (int) $emarker_id);
		$row = $this->db->get()->row();
		if (!$row) return null;

		$steps = $this->db->from('emarking_question_rubric_steps')
			->where('question_id', (int) $row->question_id)
			->where('status', 1)
			->order_by('step_order', 'ASC')
			->order_by('id', 'ASC')
			->get()
			->result();

		$mark = $this->db->get_where('emarking_marks', [
			'question_image_id' => (int) $row->question_image_id,
			'emarker_id' => (int) $emarker_id,
		])->row();

		$mark_steps = [];
		if ($mark) {
			$mark_steps_rows = $this->db->from('emarking_marks_steps')
				->where('mark_id', (int) $mark->id)
				->get()
				->result();
			foreach ($mark_steps_rows as $ms) {
				$mark_steps[(int) $ms->rubric_step_id] = $ms;
			}
		}

		return [
			'item' => $row,
			'steps' => $steps,
			'mark' => $mark,
			'mark_steps' => $mark_steps,
		];
	}

	private function clamp_marks($value, $min, $max)
	{
		$value = (float) $value;
		if ($value < (float) $min) return (float) $min;
		if ($value > (float) $max) return (float) $max;
		return $value;
	}

	public function save_mark($batch_item_id, $emarker_id, $payload)
	{
		$batch_item_id = (int) $batch_item_id;
		$emarker_id = (int) $emarker_id;

		$data = $this->get_marking_data($batch_item_id, $emarker_id);
		if (!$data) return ['ok' => false, 'error' => 'Invalid batch item'];

		$item = $data['item'];
		$steps = $data['steps'];

		// Prevent duplicate marking of same batch item
		if ((string) $item->status !== 'PENDING') {
			return ['ok' => false, 'error' => 'This batch item is already processed.' ];
		}

		$action = strtoupper(trim((string) ($payload['action'] ?? 'MARKED')));
		if (!in_array($action, ['MARKED', 'SKIPPED', 'NOT_ATTEMPTED', 'RECHECK'], true)) $action = 'MARKED';

		$max_marks = (float) $item->max_marks;
		$steps_input = isset($payload['steps']) && is_array($payload['steps']) ? $payload['steps'] : [];

		$stepAwardRows = [];
		$totalFromSteps = 0.0;
		$rubricStepById = [];
		foreach ($steps as $s) $rubricStepById[(int) $s->id] = $s;

		// For NOT_ATTEMPTED / SKIPPED: force all step marks to 0 (still record steps)
		if (in_array($action, ['NOT_ATTEMPTED', 'SKIPPED'], true)) {
			foreach ($steps as $s) {
				$stepAwardRows[] = [
					'rubric_step_id' => (int) $s->id,
					'selected_value' => '0',
					'marks_awarded' => 0.0,
				];
			}
		} else {
			foreach ($steps_input as $rubric_step_id => $selected_value) {
				$sid = (int) $rubric_step_id;
				if (empty($rubricStepById[$sid])) continue;
				$step = $rubricStepById[$sid];

				$marks_awarded = 0.0;
				$sv = is_array($selected_value) ? '' : (string) $selected_value;
				$type = (string) $step->marking_type;

				if ($type === 'ZERO_ONE') {
					$marks_awarded = ((string) $sv === '1' || strtolower($sv) === 'correct') ? (float) $step->step_marks : 0.0;
				} elseif ($type === 'FIXED') {
					$marks_awarded = (float) $step->step_marks;
				} elseif ($type === 'RANGE') {
					$marks_awarded = $this->clamp_marks($sv, (float) $step->min_marks, (float) $step->max_marks);
				} else {
					$marks_awarded = 0.0;
				}

				// Validate step marks do not exceed step max_marks (if provided)
				$stepMax = isset($step->max_marks) ? (float) $step->max_marks : null;
				if ($stepMax !== null && $stepMax > 0) {
					$marks_awarded = $this->clamp_marks($marks_awarded, 0, $stepMax);
				}

				$totalFromSteps += (float) $marks_awarded;
				$stepAwardRows[] = [
					'rubric_step_id' => $sid,
					'selected_value' => $sv,
					'marks_awarded' => (float) $marks_awarded,
				];
			}
		}

		$marks_obtained = 0.0;
		if ($action === 'MARKED') {
			if (!empty($stepAwardRows)) {
				$marks_obtained = $totalFromSteps;
			} else {
				$marks_obtained = (float) ($payload['marks_obtained'] ?? 0);
			}
			$marks_obtained = $this->clamp_marks($marks_obtained, 0, $max_marks);
		}

		$remarks = trim((string) ($payload['remarks'] ?? ''));
		if ($action === 'RECHECK') {
			$remarks = $remarks !== '' ? ('RECHECK: ' . $remarks) : 'RECHECK';
		}

		$this->db->trans_begin();

		// Ensure batch is in progress
		if ((string) $item->batch_status === 'PENDING') {
			$this->db->where('id', (int) $item->batch_id)->update('emarking_batches', ['status' => 'IN_PROGRESS']);
		}

		$existing = $this->db->get_where('emarking_marks', [
			'question_image_id' => (int) $item->question_image_id,
			'emarker_id' => $emarker_id,
		])->row();

		$markRow = [
			'batch_item_id' => $batch_item_id,
			'question_image_id' => (int) $item->question_image_id,
			'question_id' => (int) $item->question_id,
			'emarker_id' => $emarker_id,
			'marks_obtained' => (float) $marks_obtained,
			'max_marks' => (float) $max_marks,
			'marking_status' => $action === 'RECHECK' ? 'SKIPPED' : $action, // emarking_marks enum lacks RECHECK
			'remarks' => $remarks !== '' ? $remarks : null,
			'marked_at' => date('Y-m-d H:i:s'),
		];

		if ($existing) {
			$this->db->where('id', (int) $existing->id)->update('emarking_marks', $markRow);
			$mark_id = (int) $existing->id;
		} else {
			$this->db->insert('emarking_marks', $markRow);
			$mark_id = (int) $this->db->insert_id();
		}

		$err = $this->db->error();
		if (!empty($err['code']) || $mark_id <= 0) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'Unable to save mark'];
		}

		// Upsert step marks
		if (!empty($stepAwardRows)) {
			foreach ($stepAwardRows as $sr) {
				$row = [
					'mark_id' => $mark_id,
					'rubric_step_id' => (int) $sr['rubric_step_id'],
					'selected_value' => $sr['selected_value'],
					'marks_awarded' => (float) $sr['marks_awarded'],
				];
				$existsStep = $this->db->get_where('emarking_marks_steps', [
					'mark_id' => $mark_id,
					'rubric_step_id' => (int) $sr['rubric_step_id'],
				])->row();
				if ($existsStep) {
					$this->db->where('id', (int) $existsStep->id)->update('emarking_marks_steps', $row);
				} else {
					$this->db->insert('emarking_marks_steps', $row);
				}
			}
		}

		$batchItemStatus = $action;
		if ($batchItemStatus === 'MARKED') $batchItemStatus = 'MARKED';
		if ($batchItemStatus === 'RECHECK') $batchItemStatus = 'RECHECK';

		$this->db->where('id', $batch_item_id)->update('emarking_batch_items', ['status' => $batchItemStatus]);

		$imgStatus = $action === 'MARKED' ? 'MARKED' : ($action === 'RECHECK' ? 'RECHECK' : $action);
		$this->db->where('id', (int) $item->question_image_id)->update('emarking_question_images', ['status' => $imgStatus]);

		// If batch fully done, mark completed
		$pendingLeft = (int) $this->db->from('emarking_batch_items')->where('batch_id', (int) $item->batch_id)->where('status', 'PENDING')->count_all_results();
		if ($pendingLeft === 0) {
			$this->db->where('id', (int) $item->batch_id)->update('emarking_batches', [
				'status' => 'COMPLETED',
				'completed_at' => date('Y-m-d H:i:s'),
			]);
		}

		$err = $this->db->error();
		if (!empty($err['code'])) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'Database error'];
		}

		$this->db->trans_commit();
		return ['ok' => true, 'mark_id' => $mark_id];
	}
}
