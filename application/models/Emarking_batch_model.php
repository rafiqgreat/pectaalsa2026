<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking_batch_model extends CI_Model
{
	private function role_column()
	{
		if ($this->db->field_exists('role', 'users')) return 'role';
		if ($this->db->field_exists('role_id', 'users')) return 'role_id';
		return 'role';
	}

	public function get_emarkers()
	{
		$role_col = $this->role_column();
		$select = ['id', 'name', 'username', 'email', 'phone'];
		if ($this->db->field_exists('cnic', 'users')) {
			$select[] = 'cnic';
		}
		$this->db->select(implode(',', $select));
		$this->db->from('users');
		$this->db->where($role_col, 2);
		if ($this->db->field_exists('status', 'users')) {
			$this->db->where('status', 1);
		}
		if ($this->db->field_exists('blacklisted', 'users')) {
			$this->db->where('blacklisted', 0);
		}
		$this->db->order_by('name', 'ASC');
		return $this->db->get()->result();
	}

	public function get_emarkers_by_specializations($specializations)
	{
		// Filter active eMarkers by subject specialization(s) (case-insensitive).
		$specializations = is_array($specializations) ? $specializations : [];
		$specializations = array_values(array_unique(array_filter(array_map('trim', $specializations), function ($v) { return (string) $v !== ''; })));
		if (empty($specializations)) return [];

		$role_col = $this->role_column();
		$norm = array_map('strtoupper', $specializations);
		$escaped = array_map([$this->db, 'escape'], $norm);

		$select = ['u.id', 'u.name', 'u.username', 'u.email', 'u.phone'];
		if ($this->db->field_exists('cnic', 'users')) {
			$select[] = 'u.cnic';
		}
		$this->db->select(implode(',', $select));
		$this->db->from('users u');
		$this->db->join('teacher_specializations sp', 'sp.user_id = u.id', 'left');
		$this->db->where('u.' . $role_col, 2);
		if ($this->db->field_exists('status', 'users')) {
			$this->db->where('u.status', 1);
		}
		if ($this->db->field_exists('blacklisted', 'users')) {
			$this->db->where('u.blacklisted', 0);
		}

		$this->db->where('UPPER(sp.specialization) IN (' . implode(',', $escaped) . ')', null, false);
		$this->db->group_by('u.id');
		$this->db->order_by('u.name', 'ASC');
		return $this->db->get()->result();
	}

	private function get_emarker_user($id)
	{
		$role_col = $this->role_column();
		return $this->db->get_where('users', ['id' => (int) $id, $role_col => 2])->row();
	}

	private function generate_batch_code($assessment_type)
	{
		$assessment_type = strtoupper((string) $assessment_type);
		$prefix = $assessment_type === 'DICTATION' ? 'DICT' : 'CRQ';
		$ts = date('YmdHis');

		// Make it CRQ-YYYYMMDDHHIISS-2 style (sequence per timestamp)
		$like = $prefix . '-' . $ts . '-%';
		$cnt = (int) $this->db->from('emarking_batches')->like('batch_code', $like, 'after')->count_all_results();
		$seq = $cnt + 1;

		return $prefix . '-' . $ts . '-' . $seq;
	}

	public function create_batch($params)
	{
		$assessment_type = strtoupper(trim((string) ($params['assessment_type'] ?? '')));
		$question_id = (int) ($params['question_id'] ?? 0);
		$emarker_id = (int) ($params['emarker_id'] ?? 0);
		if ($emarker_id <= 0) $emarker_id = (int) ($params['assigned_to'] ?? 0); // backward compat
		$assigned_by = (int) ($params['assigned_by'] ?? 0);
		$batch_size = (int) ($params['batch_size'] ?? 100);
		$deadline = $params['deadline'] ?? null;

		if ($question_id <= 0 || $emarker_id <= 0) {
			return ['ok' => false, 'error' => 'Invalid request'];
		}
		if (!$this->get_emarker_user($emarker_id)) {
			return ['ok' => false, 'error' => 'Invalid eMarker user'];
		}

		$question = $this->db->get_where('emarking_questions', ['id' => $question_id, 'status' => 1])->row();
		if (!$question) return ['ok' => false, 'error' => 'Question not found or inactive'];

		if ($assessment_type !== '' && strtoupper((string) $question->assessment_type) !== $assessment_type) {
			return ['ok' => false, 'error' => 'Assessment type mismatch'];
		}
		$assessment_type = strtoupper((string) $question->assessment_type);

		$batch_code = $this->generate_batch_code($assessment_type);

		$this->db->trans_begin();

		// 1) Pick UPLOADED images for question_id (limit batch_size)
		$available = $this->db->select('id')
			->from('emarking_question_images')
			->where('question_id', $question_id)
			->where('status', 'UPLOADED')
			->order_by('id', 'ASC')
			->limit($batch_size)
			->get()
			->result();

		if (empty($available)) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'No UPLOADED images available for this question'];
		}

		$batchRow = [
			'batch_code' => $batch_code,
			'assessment_type' => $assessment_type,
			'grade' => (int) $question->grade,
			'subject_code' => (string) $question->subject_code,
			'version' => (int) $question->version,
			'question_id' => (int) $question->id,
			'batch_size' => (int) $batch_size,
			'assigned_to' => $emarker_id,
			'assigned_by' => $assigned_by > 0 ? $assigned_by : null,
			'deadline' => $deadline,
			'status' => 'PENDING',
		];

		$this->db->insert('emarking_batches', $batchRow);
		$batch_id = (int) $this->db->insert_id();
		$err = $this->db->error();
		if (!empty($err['code']) || $batch_id <= 0) {
			$this->db->trans_rollback();
			return ['ok' => false, 'error' => 'Unable to create batch'];
		}

		$items = [];
		$imageIds = [];
		foreach ($available as $row) {
			$qid = (int) $row->id;
			$imageIds[] = $qid;
			$items[] = [
				'batch_id' => $batch_id,
				'question_image_id' => $qid,
				'status' => 'PENDING',
			];
		}

		if (!empty($items)) {
			$this->db->insert_batch('emarking_batch_items', $items);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$this->db->trans_rollback();
				return ['ok' => false, 'error' => 'Unable to add batch items'];
			}
		}

		if (!empty($imageIds)) {
			$this->db->where_in('id', $imageIds)->update('emarking_question_images', ['status' => 'ASSIGNED']);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$this->db->trans_rollback();
				return ['ok' => false, 'error' => 'Unable to update image status'];
			}
		}

		$this->db->trans_commit();
		return ['ok' => true, 'batch_id' => $batch_id, 'batch_code' => $batch_code, 'items_created' => count($items)];
	}

	public function get_batches($filters = [])
	{
		$this->db->select('b.*, q.question_no, q.question_title, u.name as emarker_name, u.username as emarker_username');
		$this->db->from('emarking_batches b');
		$this->db->join('emarking_questions q', 'q.id = b.question_id', 'left');
		$this->db->join('users u', 'u.id = b.assigned_to', 'left');

		$status = isset($filters['status']) ? trim((string) $filters['status']) : '';
		if ($status !== '' && $status !== 'all') $this->db->where('b.status', $status);

		$assessment_type = isset($filters['assessment_type']) ? trim((string) $filters['assessment_type']) : '';
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('b.assessment_type', $assessment_type);

		$grade = isset($filters['grade']) ? trim((string) $filters['grade']) : '';
		if ($grade !== '') $this->db->where('b.grade', (int) $grade);

		// Support both single subject_code and an array for role-based filtering (e.g. Subject Specialist).
		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('b.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('b.subject_code', $subject_code);
		}

		$assigned_to = isset($filters['assigned_to']) ? trim((string) $filters['assigned_to']) : '';
		if ($assigned_to !== '') $this->db->where('b.assigned_to', (int) $assigned_to);

		$question_id = isset($filters['question_id']) ? trim((string) $filters['question_id']) : '';
		if ($question_id !== '') $this->db->where('b.question_id', (int) $question_id);

		$this->db->order_by('b.id', 'DESC');
		return $this->db->get()->result();
	}
}
