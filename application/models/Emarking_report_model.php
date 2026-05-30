<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking_report_model extends CI_Model
{
	private $rate_cache = [];

	private function parse_date($s, $defaultTime)
	{
		$s = trim((string) $s);
		if ($s === '') return null;
		$ts = strtotime($s);
		if ($ts === false) return null;
		return date('Y-m-d ' . $defaultTime, $ts);
	}

	private function get_rate_row($assessment_type, $grade, $subject_code, $question_id = null)
	{
		$key = strtoupper((string) $assessment_type) . '|' . (int) $grade . '|' . (string) $subject_code . '|' . (int) $question_id;
		if (array_key_exists($key, $this->rate_cache)) return $this->rate_cache[$key];

		$this->db->from('emarking_rates');
		$this->db->where('assessment_type', strtoupper((string) $assessment_type));
		$this->db->where('grade', (int) $grade);
		$this->db->where('subject_code', (string) $subject_code);
		$this->db->where('status', 1);
		if ($question_id !== null) {
			$this->db->where('question_id', (int) $question_id);
		} else {
			$this->db->where('question_id IS NULL', null, false);
		}
		$this->db->order_by('id', 'DESC');
		$row = $this->db->limit(1)->get()->row();
		$this->rate_cache[$key] = $row ?: null;
		return $this->rate_cache[$key];
	}

	private function resolve_rate($assessment_type, $grade, $subject_code, $question_id)
	{
		// Prefer question-specific, fallback to subject/grade.
		$r = $this->get_rate_row($assessment_type, $grade, $subject_code, $question_id);
		if ($r) return $r;
		return $this->get_rate_row($assessment_type, $grade, $subject_code, null);
	}

	public function get_reports_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			q.assessment_type, q.grade, q.subject_code, q.version, q.page_no, q.question_no, q.question_title,
			COUNT(DISTINCT qi.id) AS total_images,
			SUM(CASE WHEN qi.status='UPLOADED' THEN 1 ELSE 0 END) AS uploaded,
			SUM(CASE WHEN qi.status='ASSIGNED' THEN 1 ELSE 0 END) AS assigned,
			SUM(CASE WHEN qi.status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN qi.status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN qi.status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(CASE WHEN qi.status='RECHECK' THEN 1 ELSE 0 END) AS recheck
		", false);
		$this->db->from('emarking_questions q');
		$this->db->join('emarking_question_images qi', 'qi.question_id = q.id', 'left');

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('q.assessment_type', $assessment_type);

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);

		if ($from) $this->db->where('qi.created_at >=', $from);
		if ($to) $this->db->where('qi.created_at <=', $to);

		$this->db->group_by(['q.id']);
		$this->db->order_by('q.assessment_type', 'ASC');
		$this->db->order_by('q.grade', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$this->db->order_by('q.version', 'ASC');
		$this->db->order_by('q.page_no', 'ASC');
		$this->db->order_by('q.question_no', 'ASC');
		return $this->db->get()->result();
	}

	public function get_overall_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			COUNT(*) AS total_images,
			SUM(CASE WHEN status='UPLOADED' THEN 1 ELSE 0 END) AS uploaded,
			SUM(CASE WHEN status='ASSIGNED' THEN 1 ELSE 0 END) AS assigned,
			SUM(CASE WHEN status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(CASE WHEN status='RECHECK' THEN 1 ELSE 0 END) AS recheck,
			SUM(CASE WHEN status='FINALIZED' THEN 1 ELSE 0 END) AS finalized
		", false);
		$this->db->from('emarking_question_images');
		if ($from) $this->db->where('created_at >=', $from);
		if ($to) $this->db->where('created_at <=', $to);
		return $this->db->get()->row();
	}

	public function get_subject_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			q.assessment_type, q.grade, q.subject_code,
			COUNT(DISTINCT qi.id) AS total_images,
			SUM(CASE WHEN qi.status='UPLOADED' THEN 1 ELSE 0 END) AS uploaded,
			SUM(CASE WHEN qi.status='ASSIGNED' THEN 1 ELSE 0 END) AS assigned,
			SUM(CASE WHEN qi.status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN qi.status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN qi.status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(CASE WHEN qi.status='RECHECK' THEN 1 ELSE 0 END) AS recheck
		", false);
		$this->db->from('emarking_questions q');
		$this->db->join('emarking_question_images qi', 'qi.question_id = q.id', 'left');

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('q.assessment_type', $assessment_type);
		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);

		if ($from) $this->db->where('qi.created_at >=', $from);
		if ($to) $this->db->where('qi.created_at <=', $to);

		$this->db->group_by(['q.assessment_type', 'q.grade', 'q.subject_code']);
		$this->db->order_by('q.assessment_type', 'ASC');
		$this->db->order_by('q.grade', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		return $this->db->get()->result();
	}

	public function get_emarker_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			u.id AS emarker_id, u.name AS emarker_name, u.username AS emarker_username,
			COUNT(m.id) AS total_actions,
			SUM(CASE WHEN m.marking_status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN m.marking_status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN m.marking_status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(m.marks_obtained) AS total_marks
		", false);
		$this->db->from('emarking_marks m');
		$this->db->join('users u', 'u.id = m.emarker_id', 'left');
		if ($from) $this->db->where('m.marked_at >=', $from);
		if ($to) $this->db->where('m.marked_at <=', $to);
		$this->db->group_by(['m.emarker_id']);
		$this->db->order_by('marked', 'DESC', false);
		return $this->db->get()->result();
	}

	public function get_emarker_payment_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			u.id AS emarker_id, u.name AS emarker_name, u.username AS emarker_username,
			COUNT(m.id) AS total_actions,
			SUM(CASE WHEN m.marking_status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN m.marking_status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN m.marking_status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(CASE WHEN m.marking_status='MARKED' THEN m.marks_obtained ELSE 0 END) AS total_marks,
			SUM(CASE WHEN m.marking_status='MARKED' THEN m.max_marks ELSE 0 END) AS total_max_marks,
			ROUND(TIMESTAMPDIFF(SECOND, MIN(m.marked_at), MAX(m.marked_at)) / 3600, 2) AS duration_hours
		", false);
		$this->db->from('emarking_marks m');
		$this->db->join('emarking_questions q', 'q.id = m.question_id', 'inner');
		$this->db->join('users u', 'u.id = m.emarker_id', 'left');

		if ($from) $this->db->where('m.marked_at >=', $from);
		if ($to) $this->db->where('m.marked_at <=', $to);

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('q.assessment_type', $assessment_type);
		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);

		$this->db->group_by(['m.emarker_id']);
		$this->db->order_by('marked', 'DESC', false);
		return $this->db->get()->result();
	}

	public function get_batch_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			b.id, b.batch_code, b.assessment_type, b.grade, b.subject_code, b.question_id, b.assigned_to,
			b.status, b.created_at, b.deadline, u.name AS emarker_name,
			COUNT(i.id) AS total_items,
			SUM(CASE WHEN i.status='PENDING' THEN 1 ELSE 0 END) AS pending_items,
			SUM(CASE WHEN i.status='MARKED' THEN 1 ELSE 0 END) AS marked_items,
			SUM(CASE WHEN i.status='SKIPPED' THEN 1 ELSE 0 END) AS skipped_items,
			SUM(CASE WHEN i.status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted_items,
			SUM(CASE WHEN i.status='RECHECK' THEN 1 ELSE 0 END) AS recheck_items,
			SUM(CASE WHEN i.status='FINALIZED' THEN 1 ELSE 0 END) AS finalized_items
		", false);
		$this->db->from('emarking_batches b');
		$this->db->join('emarking_batch_items i', 'i.batch_id = b.id', 'left');
		$this->db->join('users u', 'u.id = b.assigned_to', 'left');
		if ($from) $this->db->where('b.created_at >=', $from);
		if ($to) $this->db->where('b.created_at <=', $to);

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('b.assessment_type', $assessment_type);
		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('b.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('b.subject_code', $subject_code);

		$this->db->group_by(['b.id']);
		$this->db->order_by('b.id', 'DESC');
		return $this->db->get()->result();
	}

	public function get_billing($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');
		$emarker_id = trim((string) ($filters['emarker_id'] ?? ''));

		// Payable statuses: MARKED + NOT_ATTEMPTED (SKIPPED is not payable by default)
		$this->db->select("
			m.emarker_id,
			u.name as emarker_name,
			u.username as emarker_username,
			q.assessment_type, q.grade, q.subject_code,
			q.id as question_id, q.question_no, q.question_title,
			COUNT(m.id) AS checked_count,
			SUM(m.max_marks) AS sum_max_marks
		", false);
		$this->db->from('emarking_marks m');
		$this->db->join('emarking_questions q', 'q.id = m.question_id', 'inner');
		$this->db->join('users u', 'u.id = m.emarker_id', 'left');
		$this->db->where_in('m.marking_status', ['MARKED', 'NOT_ATTEMPTED']);
		if ($from) $this->db->where('m.marked_at >=', $from);
		if ($to) $this->db->where('m.marked_at <=', $to);
		if ($emarker_id !== '') $this->db->where('m.emarker_id', (int) $emarker_id);
		$this->db->group_by(['m.emarker_id', 'q.assessment_type', 'q.grade', 'q.subject_code', 'q.id']);
		$this->db->order_by('u.name', 'ASC');
		$this->db->order_by('q.assessment_type', 'ASC');
		$this->db->order_by('q.grade', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$this->db->order_by('q.question_no', 'ASC');
		$qry = $this->db->get();
		if ($qry === false) {
			// Fail-safe: return empty and let caller show "No records".
			return [];
		}
		$rows = $qry->result();

		foreach ($rows as $r) {
			$rateRow = $this->resolve_rate($r->assessment_type, (int) $r->grade, (string) $r->subject_code, (int) $r->question_id);
			$r->rate_type = $rateRow ? (string) $rateRow->rate_type : 'PER_QUESTION';
			$r->rate = $rateRow ? (float) $rateRow->rate : 0.0;

			if ($r->rate_type === 'PER_MARK') {
				// As per requirement: sum(max_marks) * rate
				$r->amount = (float) $r->sum_max_marks * (float) $r->rate;
			} else {
				// PER_QUESTION: count checked * rate
				$r->amount = (float) $r->checked_count * (float) $r->rate;
			}
		}

		return $rows;
	}

	public function get_skipped($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');
		$emarker_id = trim((string) ($filters['emarker_id'] ?? ''));
		$status = strtoupper(trim((string) ($filters['status'] ?? '')));
		if (!in_array($status, ['SKIPPED', 'NOT_ATTEMPTED'], true)) $status = '';

		$this->db->select('m.*, q.assessment_type, q.grade, q.subject_code, q.question_no, q.question_title, qi.paper_barcode, qi.roll_no, u.name as emarker_name');
		$this->db->from('emarking_marks m');
		$this->db->join('emarking_questions q', 'q.id = m.question_id', 'inner');
		$this->db->join('emarking_question_images qi', 'qi.id = m.question_image_id', 'left');
		$this->db->join('users u', 'u.id = m.emarker_id', 'left');
		if ($status !== '') {
			$this->db->where('m.marking_status', $status);
		} else {
			$this->db->where_in('m.marking_status', ['SKIPPED', 'NOT_ATTEMPTED']);
		}
		if ($from) $this->db->where('m.marked_at >=', $from);
		if ($to) $this->db->where('m.marked_at <=', $to);
		if ($emarker_id !== '') $this->db->where('m.emarker_id', (int) $emarker_id);
		$this->db->order_by('m.marked_at', 'DESC');
		return $this->db->get()->result();
	}
}
