<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_marking_model extends CI_Model
{
	private function role_column()
	{
		if ($this->db->field_exists('role', 'users')) return 'role';
		if ($this->db->field_exists('role_id', 'users')) return 'role_id';
		return 'role';
	}

	private function generate_batch_code()
	{
		$prefix = 'QC';
		$ts = date('YmdHis');
		$like = $prefix . '-' . $ts . '-%';
		$cnt = (int) $this->db->from('emarking_qc_batches')->like('batch_code', $like, 'after')->count_all_results();
		$seq = $cnt + 1;
		return $prefix . '-' . $ts . '-' . $seq;
	}

	public function get_subject_specialists($subject_code = '')
	{
		$subject_code = trim((string) $subject_code);
		$role_col = $this->role_column();

		$this->db->select('id, name, username, subjects');
		$this->db->from('users');
		$this->db->where($role_col, 18);
		$this->db->order_by('name', 'ASC');
		$rows = $this->db->get()->result();

		if ($subject_code === '') return $rows;

		$out = [];
		foreach ($rows as $u) {
			$raw = trim((string) ($u->subjects ?? ''));
			$list = [];
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) $list = $decoded;
			else if ($raw !== '') $list = preg_split('/\s*,\s*/', $raw);

			$norm = array_values(array_unique(array_filter(array_map('trim', (array) $list), static function ($v) {
				return (string) $v !== '';
			})));

			$has = false;
			foreach ($norm as $s) {
				if ((string) $s === (string) $subject_code) { $has = true; break; }
				// allow names too
				$name = strtoupper((string) $s);
				if ($subject_code === '1' && $name === 'ENGLISH') { $has = true; break; }
				if ($subject_code === '2' && $name === 'URDU') { $has = true; break; }
				if ($subject_code === '3' && $name === 'MATH') { $has = true; break; }
				if ($subject_code === '4' && $name === 'SCIENCE') { $has = true; break; }
			}

			if ($has) $out[] = $u;
		}

		return $out;
	}

	public function create_qc_batch($params)
	{
		$assessment_type = strtoupper(trim((string) ($params['assessment_type'] ?? '')));
		if (!in_array($assessment_type, ['CRQ', 'DICTATION'], true)) return ['ok' => false, 'error' => 'Invalid assessment type'];

		$grade = (int) ($params['grade'] ?? 0);
		$subject_code = trim((string) ($params['subject_code'] ?? ''));
		$version = trim((string) ($params['version'] ?? '')); // '' means ALL
		$assigned_to = (int) ($params['assigned_to'] ?? 0);
		$assigned_by = (int) ($params['assigned_by'] ?? 0);
		$per_question = (int) ($params['per_question'] ?? 10);
		if ($per_question <= 0) $per_question = 10;

		if ($grade <= 0 || $subject_code === '' || $assigned_to <= 0) return ['ok' => false, 'error' => 'Missing required fields'];

		$batch_code = $this->generate_batch_code();

		$this->db->trans_begin();

		// Select questions (all questions of subject, all versions or specific version)
		$this->db->select('id, version');
		$this->db->from('emarking_questions');
		$this->db->where('assessment_type', $assessment_type);
		$this->db->where('grade', $grade);
		$this->db->where('subject_code', $subject_code);
		$this->db->where('status', 1);
		if ($version !== '') $this->db->where('version', (int) $version);
		$this->db->order_by('version', 'ASC');
		$this->db->order_by('id', 'ASC');
		$questions = $this->db->get()->result();

		if (empty($questions)) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'No active questions found for selected filters'];
		}

		$batchRow = [
			'batch_code' => $batch_code,
			'assessment_type' => $assessment_type,
			'grade' => $grade,
			'subject_code' => $subject_code,
			'version' => ($version === '') ? null : (int) $version,
			'per_question' => $per_question,
			'assigned_to' => $assigned_to,
			'assigned_by' => $assigned_by > 0 ? $assigned_by : null,
			'status' => 'PENDING',
			'created_at' => date('Y-m-d H:i:s'),
		];

		$this->db->insert('emarking_qc_batches', $batchRow);
		$batch_id = (int) $this->db->insert_id();
		$err = $this->db->error();
		if (!empty($err['code']) || $batch_id <= 0) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'Unable to create QC batch'];
		}

		$totalItems = 0;
		$items = [];

		foreach ($questions as $q) {
			$qid = (int) ($q->id ?? 0);
			if ($qid <= 0) continue;

			$imgs = $this->db->select('id')
				->from('emarking_question_images')
				->where('question_id', $qid)
				->order_by('id', 'DESC')
				->limit($per_question)
				->get()
				->result();

			foreach ($imgs as $im) {
				$imgId = (int) ($im->id ?? 0);
				if ($imgId <= 0) continue;
				$items[] = [
					'batch_id' => $batch_id,
					'question_image_id' => $imgId,
					'question_id' => $qid,
					'status' => 'PENDING',
					'created_at' => date('Y-m-d H:i:s'),
				];
				$totalItems++;
			}

			// Insert in chunks to avoid memory/packet limits
			if (count($items) >= 1000) {
				$this->db->insert_batch('emarking_qc_batch_items', $items);
				$items = [];
			}
		}

		if (!empty($items)) {
			$this->db->insert_batch('emarking_qc_batch_items', $items);
		}

		$err = $this->db->error();
		if (!empty($err['code'])) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'Unable to create QC batch items'];
		}

		$this->db->where('id', $batch_id)->update('emarking_qc_batches', ['total_items' => (int) $totalItems]);
		$err = $this->db->error();
		if (!empty($err['code'])) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'Unable to finalize QC batch'];
		}

		$this->db->trans_commit();
		return ['ok' => true, 'batch_id' => $batch_id, 'batch_code' => $batch_code, 'total_items' => $totalItems];
	}

	public function get_batches($filters = [])
	{
		$this->db->select('b.*, u.name as ss_name, u.username as ss_username');
		$this->db->select('(SELECT COUNT(*) FROM emarking_qc_batch_items i WHERE i.batch_id = b.id) AS total_items', false);
		$this->db->select("(SELECT COUNT(*) FROM emarking_qc_batch_items i WHERE i.batch_id = b.id AND i.status='PENDING') AS pending_items", false);
		$this->db->select("(SELECT COUNT(*) FROM emarking_qc_batch_items i WHERE i.batch_id = b.id AND i.status IN ('MARKED','NOT_ATTEMPTED','SKIPPED','FINALIZED','RECHECK')) AS done_items", false);
		$this->db->from('emarking_qc_batches b');
		$this->db->join('users u', 'u.id = b.assigned_to', 'left');

		if (!empty($filters['assigned_to'])) $this->db->where('b.assigned_to', (int) $filters['assigned_to']);
		if (!empty($filters['status'])) $this->db->where('b.status', (string) $filters['status']);

		$this->db->order_by('b.id', 'DESC');
		return $this->db->get()->result();
	}

	public function get_batch_for_ss($batch_id, $ss_id)
	{
		return $this->db->from('emarking_qc_batches')
			->where('id', (int) $batch_id)
			->where('assigned_to', (int) $ss_id)
			->limit(1)
			->get()
			->row();
	}

	public function get_batch_items($batch_id, $ss_id)
	{
		$this->db->select('i.*, qi.paper_barcode, qi.roll_no, qi.image_path, qi.status as image_status, q.assessment_type, q.grade, q.subject_code, q.version, q.page_no, q.question_no, q.question_title, q.max_marks');
		$this->db->from('emarking_qc_batch_items i');
		$this->db->join('emarking_qc_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->join('emarking_question_images qi', 'qi.id = i.question_image_id', 'inner');
		$this->db->join('emarking_questions q', 'q.id = i.question_id', 'inner');
		$this->db->where('i.batch_id', (int) $batch_id);
		$this->db->where('b.assigned_to', (int) $ss_id);
		$this->db->order_by('i.id', 'ASC');
		return $this->db->get()->result();
	}

	public function get_next_pending_item($batch_id, $ss_id)
	{
		return $this->db->select('i.id')
			->from('emarking_qc_batch_items i')
			->join('emarking_qc_batches b', 'b.id = i.batch_id', 'inner')
			->where('i.batch_id', (int) $batch_id)
			->where('b.assigned_to', (int) $ss_id)
			->where('i.status', 'PENDING')
			->order_by('i.id', 'ASC')
			->limit(1)
			->get()
			->row();
	}

	public function get_qc_stats($ss_id)
	{
		$ss_id = (int) $ss_id;
		$out = [
			'batches_total' => 0,
			'batches_pending' => 0,
			'batches_in_progress' => 0,
			'batches_completed' => 0,
			'items_pending' => 0,
			'items_marked' => 0,
			'items_skipped' => 0,
			'items_not_attempted' => 0,
			'items_recheck' => 0,
		];

		$batches = $this->db->select('status, COUNT(*) as cnt', false)
			->from('emarking_qc_batches')
			->where('assigned_to', $ss_id)
			->group_by('status')
			->get()
			->result();
		foreach ($batches as $r) {
			$out['batches_total'] += (int) $r->cnt;
			$key = 'batches_' . strtolower((string) $r->status);
			if (isset($out[$key])) $out[$key] = (int) $r->cnt;
		}

		$items = $this->db->select('i.status, COUNT(*) as cnt', false)
			->from('emarking_qc_batch_items i')
			->join('emarking_qc_batches b', 'b.id = i.batch_id', 'inner')
			->where('b.assigned_to', $ss_id)
			->group_by('i.status')
			->get()
			->result();
		foreach ($items as $r) {
			$key = 'items_' . strtolower((string) $r->status);
			if (isset($out[$key])) $out[$key] = (int) $r->cnt;
		}

		return $out;
	}

	public function get_marking_data($batch_item_id, $ss_id)
	{
		$this->db->select('i.*, b.batch_code, b.status as batch_status, q.assessment_type, q.grade, q.subject_code, q.version, q.page_no, q.question_no, q.question_title, q.question_type, q.max_marks, q.rubric_title, q.rubric_detail, q.sample_answer, q.sample_answer_file, q.guide_text, q.guide_file, qi.paper_barcode, qi.roll_no, qi.image_path, qi.id as question_image_id');
		$this->db->from('emarking_qc_batch_items i');
		$this->db->join('emarking_qc_batches b', 'b.id = i.batch_id', 'inner');
		$this->db->join('emarking_questions q', 'q.id = i.question_id', 'inner');
		$this->db->join('emarking_question_images qi', 'qi.id = i.question_image_id', 'inner');
		$this->db->where('i.id', (int) $batch_item_id);
		$this->db->where('b.assigned_to', (int) $ss_id);
		$item = $this->db->get()->row();
		if (!$item) return null;

		$steps = $this->db->from('emarking_question_rubric_steps')->where('question_id', (int) $item->question_id)->order_by('id', 'ASC')->get()->result();
		$mark = $this->db->from('emarking_qc_marks')->where('batch_item_id', (int) $batch_item_id)->where('ss_id', (int) $ss_id)->limit(1)->get()->row();
		$mark_steps = [];
		if ($mark) {
			$mark_steps = $this->db->from('emarking_qc_marks_steps')->where('mark_id', (int) $mark->id)->get()->result();
		}

		return [
			'item' => $item,
			'steps' => $steps,
			'mark' => $mark,
			'mark_steps' => $mark_steps,
		];
	}

	public function save_mark($batch_item_id, $ss_id, $payload)
	{
		$batch_item_id = (int) $batch_item_id;
		$ss_id = (int) $ss_id;
		if ($batch_item_id <= 0 || $ss_id <= 0) return ['ok' => false, 'error' => 'Invalid request'];

		$action = strtoupper((string) ($payload['action'] ?? 'MARKED'));
		if (!in_array($action, ['MARKED', 'SKIPPED', 'NOT_ATTEMPTED', 'RECHECK'], true)) $action = 'MARKED';

		$item = $this->db->from('emarking_qc_batch_items i')
			->join('emarking_qc_batches b', 'b.id = i.batch_id', 'inner')
			->where('i.id', $batch_item_id)
			->where('b.assigned_to', $ss_id)
			->limit(1)
			->get()
			->row();
		if (!$item) return ['ok' => false, 'error' => 'Item not found'];

		$q = $this->db->get_where('emarking_questions', ['id' => (int) $item->question_id])->row();
		if (!$q) return ['ok' => false, 'error' => 'Question not found'];

		$marks_obtained = (float) ($payload['marks_obtained'] ?? 0);
		$remarks = trim((string) ($payload['remarks'] ?? ''));
		$steps = is_array($payload['steps'] ?? null) ? $payload['steps'] : [];

		$this->db->trans_begin();

		$mark = $this->db->from('emarking_qc_marks')
			->where('batch_item_id', $batch_item_id)
			->where('ss_id', $ss_id)
			->limit(1)
			->get()
			->row();

		$markRow = [
			'batch_item_id' => $batch_item_id,
			'question_image_id' => (int) $item->question_image_id,
			'question_id' => (int) $item->question_id,
			'ss_id' => $ss_id,
			'marks_obtained' => $action === 'MARKED' ? $marks_obtained : 0,
			'max_marks' => (float) ($q->max_marks ?? 0),
			'marking_status' => $action,
			'remarks' => $remarks,
			'marked_at' => date('Y-m-d H:i:s'),
		];

		if ($mark) {
			$this->db->where('id', (int) $mark->id)->update('emarking_qc_marks', $markRow);
			$mark_id = (int) $mark->id;
			$this->db->delete('emarking_qc_marks_steps', ['mark_id' => $mark_id]);
		} else {
			$this->db->insert('emarking_qc_marks', $markRow);
			$mark_id = (int) $this->db->insert_id();
		}

		if ($action === 'MARKED') {
			$stepRows = [];
			foreach ($steps as $rubric_step_id => $val) {
				$rid = (int) $rubric_step_id;
				if ($rid <= 0) continue;
				$stepRows[] = [
					'mark_id' => $mark_id,
					'rubric_step_id' => $rid,
					'selected_value' => is_scalar($val) ? (string) $val : '',
					'marks_awarded' => 0,
				];
			}
			if (!empty($stepRows)) {
				$this->db->insert_batch('emarking_qc_marks_steps', $stepRows);
			}
		}

		// Update item status
		$itemStatus = $action === 'RECHECK' ? 'RECHECK' : $action;
		$this->db->where('id', $batch_item_id)->update('emarking_qc_batch_items', ['status' => $itemStatus]);

		// Update batch status
		$pendingLeft = (int) $this->db->from('emarking_qc_batch_items')
			->where('batch_id', (int) $item->batch_id)
			->where('status', 'PENDING')
			->count_all_results();
		if ($pendingLeft === 0) {
			$this->db->where('id', (int) $item->batch_id)->update('emarking_qc_batches', [
				'status' => 'COMPLETED',
				'completed_at' => date('Y-m-d H:i:s'),
			]);
		} else {
			$this->db->where('id', (int) $item->batch_id)->update('emarking_qc_batches', [
				'status' => 'IN_PROGRESS',
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

