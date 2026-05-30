<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_report_model extends CI_Model
{
	public function get_ss_summary($filters = [])
	{
		$from = trim((string) ($filters['from'] ?? ''));
		$to = trim((string) ($filters['to'] ?? ''));

		$fromDt = $from !== '' ? ($from . ' 00:00:00') : null;
		$toDt = $to !== '' ? ($to . ' 23:59:59') : null;

		$this->db->select("
			u.id AS ss_id, u.name AS ss_name, u.username AS ss_username,
			COUNT(m.id) AS total_actions,
			SUM(CASE WHEN m.marking_status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN m.marking_status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN m.marking_status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(CASE WHEN m.marking_status='MARKED' THEN m.marks_obtained ELSE 0 END) AS total_marks,
			SUM(CASE WHEN m.marking_status='MARKED' THEN m.max_marks ELSE 0 END) AS total_max_marks,
			ROUND(TIMESTAMPDIFF(SECOND, MIN(m.marked_at), MAX(m.marked_at)) / 3600, 2) AS duration_hours
		", false);
		$this->db->from('emarking_qc_marks m');
		$this->db->join('users u', 'u.id = m.ss_id', 'left');
		if ($fromDt) $this->db->where('m.marked_at >=', $fromDt);
		if ($toDt) $this->db->where('m.marked_at <=', $toDt);
		$this->db->group_by(['m.ss_id']);
		$this->db->order_by('marked', 'DESC', false);
		return $this->db->get()->result();
	}
}

