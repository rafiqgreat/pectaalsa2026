<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking_report_model extends CI_Model
{
	private $rate_cache = [];
	private $dictation_csv_headers = [
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
		'Q1',
		'Q2',
		'Q3',
		'Q4',
		'Q5',
		'Total Obtained',
	];
	private $result_csv_base_headers = [
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
	private $bq_csv_base_headers = [
		'Unique Identifier',
		'School ID',
		'Student / Teacher ID',
		'EMIS Code',
		'School Name',
		'District',
		'Tehsil',
		'School Admin',
		'School Level',
		'School Type',
		'Gender',
		'Grade',
		'Subject Taught (if teacher)',
	];

	private function bq_allowed_tables()
	{
		return [
			'sheet_02',
			'sheet_03',
			'sheet_04',
			'sheet_05',
			'sheet_06',
			'sheet_07',
			'sheet_08',
			'sheet_09',
			'sheet_1011',
		];
	}

	private function bq_decode_barcode_sql($column)
	{
		return [
			'grade' => "SUBSTRING({$column}, 1, 1)",
			'lsa_numeric' => "SUBSTRING({$column}, 2, 4)",
			'family_code' => "SUBSTRING({$column}, 6, 2)",
			'version' => "CAST(SUBSTRING({$column}, 8, 2) AS UNSIGNED)",
			'student_roll' => "SUBSTRING({$column}, 10, 2)",
		];
	}

	private function bq_barcode_grade_expr($column)
	{
		$parts = $this->bq_decode_barcode_sql($column);
		return $parts['grade'];
	}

	private function bq_barcode_version_expr($column)
	{
		$parts = $this->bq_decode_barcode_sql($column);
		return $parts['version'];
	}

	private function bq_barcode_lsa_expr($column)
	{
		$parts = $this->bq_decode_barcode_sql($column);
		return $parts['lsa_numeric'];
	}

	private function bq_barcode_student_expr($column)
	{
		$parts = $this->bq_decode_barcode_sql($column);
		return $parts['student_roll'];
	}

	private function get_bq_question_labels($table)
	{
		$table = trim((string) $table);
		if ($table === '' || !in_array($table, $this->bq_allowed_tables(), true) || !$this->db->table_exists($table)) {
			return [];
		}

		$fields = $this->db->list_fields($table);
		$labels = [];
		foreach ($fields as $field) {
			if (preg_match('/^Q(\d{3})$/', (string) $field, $m)) {
				$labels[] = 'Q' . (int) $m[1];
			}
		}

		return $this->sort_question_labels($labels);
	}

	private function bq_export_where_sql($table, $filters = [])
	{
		$where = ['1 = 1'];

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$where[] = $this->bq_barcode_grade_expr('src.Student_Barcode') . ' = ' . $this->db->escape($grade);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$where[] = $this->bq_barcode_version_expr('src.Student_Barcode') . ' = ' . (int) $version;
		}

		$district_id = trim((string) ($filters['district_id'] ?? ''));
		if ($district_id !== '') {
			$where[] = 'sch.school_district_id = ' . (int) $district_id;
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$like = $this->db->escape_like_str($school_query);
			$where[] = '('
				. "sch.school_name LIKE '%{$like}%' ESCAPE '!'"
				. " OR sch.school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR sch.school_lsacode LIKE '%{$like}%' ESCAPE '!'"
				. " OR src.Student_Barcode LIKE '%{$like}%' ESCAPE '!'"
				. ')';
		}

		return implode(' AND ', $where);
	}

	private function build_bq_export_sql($table, $filters = [], $limit = null)
	{
		$table = trim((string) $table);
		if (!in_array($table, $this->bq_allowed_tables(), true)) {
			throw new InvalidArgumentException('Invalid BQ source table selected.');
		}

		$question_labels = $this->get_bq_question_labels($table);
		$question_selects = [];
		foreach ($question_labels as $label) {
			$num = (int) preg_replace('/\D+/', '', $label);
			$source_col = 'Q' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
			$question_selects[] = "TRIM(COALESCE(src.`{$source_col}`, '')) AS `" . strtolower($label) . "`";
		}
		$question_select_sql = '';
		if (!empty($question_selects)) {
			$question_select_sql = ",
				" . implode(",
				", $question_selects);
		}

		$where_sql = $this->bq_export_where_sql($table, $filters);
		$grade_expr = $this->bq_barcode_grade_expr('src.Student_Barcode');
		$lsa_expr = $this->bq_barcode_lsa_expr('src.Student_Barcode');
		$version_expr = $this->bq_barcode_version_expr('src.Student_Barcode');
		$student_expr = $this->bq_barcode_student_expr('src.Student_Barcode');

		$limit_sql = '';
		if ($limit !== null) {
			$limit_sql = "\nLIMIT " . max(0, (int) $limit);
		}

		return "
			SELECT
				TRIM(COALESCE(src.Student_Barcode, '')) AS unique_identifier,
				COALESCE(sch.school_id, '') AS school_id,
				{$student_expr} AS student_teacher_id,
				COALESCE(sch.school_code, sch.school_lsacode, '') AS emis_code,
				COALESCE(sch.school_name, '') AS school_name,
				COALESCE(d.district_name_en, sch.school_district, '') AS district,
				COALESCE(t.tehsil_name_en, sch.school_tehsil, '') AS tehsil,
				COALESCE(sch.username, '') AS school_admin,
				COALESCE(sch.school_level, '') AS school_level,
				COALESCE(sch.school_department, '') AS school_type,
				COALESCE(NULLIF(TRIM(src.Gender), ''), sch.school_gender, '') AS gender,
				{$grade_expr} AS grade,
				'' AS subject_taught{$question_select_sql},
				{$version_expr} AS version
			FROM {$table} src
			LEFT JOIN schools sch
				ON sch.school_lsacode COLLATE utf8mb4_unicode_ci = CONCAT('LSA-', {$grade_expr}, '-', {$lsa_expr}) COLLATE utf8mb4_unicode_ci
			LEFT JOIN districts d ON d.district_id = sch.school_district_id
			LEFT JOIN tehsils t ON t.tehsil_id = sch.school_tehsil_id
			WHERE {$where_sql}
			ORDER BY src.Student_Barcode ASC{$limit_sql}
		";
	}

	private function build_bq_csv_row_from_sql_row(array $row, array $question_labels)
	{
		$out = [
			'Unique Identifier' => (string) ($row['unique_identifier'] ?? ''),
			'School ID' => (string) ($row['school_id'] ?? ''),
			'Student / Teacher ID' => (string) ($row['student_teacher_id'] ?? ''),
			'EMIS Code' => (string) ($row['emis_code'] ?? ''),
			'School Name' => (string) ($row['school_name'] ?? ''),
			'District' => (string) ($row['district'] ?? ''),
			'Tehsil' => (string) ($row['tehsil'] ?? ''),
			'School Admin' => (string) ($row['school_admin'] ?? ''),
			'School Level' => (string) ($row['school_level'] ?? ''),
			'School Type' => (string) ($row['school_type'] ?? ''),
			'Gender' => (string) ($row['gender'] ?? ''),
			'Grade' => (string) ($row['grade'] ?? ''),
			'Subject Taught (if teacher)' => (string) ($row['subject_taught'] ?? ''),
		];

		foreach ($question_labels as $label) {
			$out[$label] = (string) ($row[strtolower($label)] ?? '');
		}

		return $out;
	}

	private function mcq_source_table_where_sql($alias, $filters = [])
	{
		$where = ['1 = 1'];

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$where[] = "{$alias}.paper_grade = " . (int) $grade;
		}

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$where[] = "{$alias}.paper_subject_code = " . $this->db->escape($subject_code);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$where[] = "{$alias}.paper_version = " . (int) $version;
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$like = $this->db->escape_like_str($school_query);
			$where[] = "("
				. "{$alias}.paper_school_name LIKE '%{$like}%' ESCAPE '!'"
				. " OR {$alias}.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR {$alias}.paper_barcode LIKE '%{$like}%' ESCAPE '!'"
				. " OR {$alias}.paper_sr_roll LIKE '%{$like}%' ESCAPE '!'"
				. ")";
		}

		return implode(' AND ', $where);
	}

	private function mcq_source_union_sql($filters = [])
	{
		$tables = [
			'digital_papers_booklets1',
			'digital_papers_booklets2',
			'digital_papers_booklets3',
			'digital_papers_booklets4',
		];

		$parts = [];
		foreach ($tables as $table) {
			$where_sql = $this->mcq_source_table_where_sql($table, $filters);
			$parts[] = "
				SELECT
					'{$table}' AS source_table,
					paper_id,
					paper_grade AS grade,
					paper_school_id AS school_id,
					paper_school_code,
					paper_school_name,
					paper_district,
					paper_tehsil,
					paper_subject_code AS subject_code,
					paper_version AS version,
					paper_sr_roll AS student_id,
					paper_page_no,
					paper_barcode
				FROM {$table}
				WHERE {$where_sql}
			";
		}

		return implode("\nUNION ALL\n", $parts);
	}

	private function mcq_export_where_sql($filters = [])
	{
		$where = ['1 = 1'];

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$where[] = 'src.grade = ' . (int) $grade;
		}

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$where[] = 'src.subject_code = ' . $this->db->escape($subject_code);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$where[] = 'src.version = ' . (int) $version;
		}

		$district_id = trim((string) ($filters['district_id'] ?? ''));
		if ($district_id !== '') {
			$where[] = 's.school_district_id = ' . (int) $district_id;
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$like = $this->db->escape_like_str($school_query);
			$where[] = "("
				. "s.school_name LIKE '%{$like}%' ESCAPE '!'"
				. " OR s.school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR s.school_lsacode LIKE '%{$like}%' ESCAPE '!'"
				. " OR src.paper_school_name LIKE '%{$like}%' ESCAPE '!'"
				. " OR src.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR src.paper_barcode LIKE '%{$like}%' ESCAPE '!'"
				. " OR src.student_id LIKE '%{$like}%' ESCAPE '!'"
				. ")";
		}

		return implode(' AND ', $where);
	}

	private function build_mcq_export_sql($filters = [])
	{
		$where_sql = $this->mcq_export_where_sql($filters);
		$source_union_sql = $this->mcq_source_union_sql($filters);

		return "
			SELECT
				src.source_table,
				src.paper_id,
				src.grade,
				src.school_id,
				src.paper_school_code,
				src.paper_school_name,
				src.paper_district,
				src.paper_tehsil,
				src.subject_code,
				src.version,
				src.student_id,
				src.paper_page_no,
				src.paper_barcode,
				s.school_code,
				s.school_lsacode,
				s.school_name,
				s.school_district,
				s.school_tehsil,
				s.username AS school_admin,
				s.school_level,
				s.school_department AS school_type,
				s.school_gender,
				d.district_name_en,
				t.tehsil_name_en,
				r.Q1 AS page_q1,
				r.Q2 AS page_q2,
				r.Q3 AS page_q3,
				r.Q4 AS page_q4
			FROM ({$source_union_sql}) src
			INNER JOIN crq_mcq_results r ON r.barcode = src.paper_barcode
			LEFT JOIN schools s ON s.school_id = src.school_id
			LEFT JOIN districts d ON d.district_id = s.school_district_id
			LEFT JOIN tehsils t ON t.tehsil_id = s.school_tehsil_id
			WHERE {$where_sql}
			ORDER BY
				COALESCE(src.school_id, 0) ASC,
				TRIM(COALESCE(src.student_id, '')) ASC,
				TRIM(COALESCE(src.subject_code, '')) ASC,
				COALESCE(src.version, 0) ASC,
				CAST(COALESCE(NULLIF(src.paper_page_no, ''), '0') AS UNSIGNED) ASC,
				src.paper_barcode ASC
		";
	}

	private function get_mcq_question_labels($filters = [])
	{
		$where_sql = $this->mcq_export_where_sql($filters);
		$source_union_sql = $this->mcq_source_union_sql($filters);

		$sql = "
			SELECT MAX(group_question_count) AS max_question_count
			FROM (
				SELECT
					SUM(
						CASE WHEN NULLIF(TRIM(COALESCE(r.Q1, '')), '') IS NULL THEN 0 ELSE 1 END +
						CASE WHEN NULLIF(TRIM(COALESCE(r.Q2, '')), '') IS NULL THEN 0 ELSE 1 END +
						CASE WHEN NULLIF(TRIM(COALESCE(r.Q3, '')), '') IS NULL THEN 0 ELSE 1 END +
						CASE WHEN NULLIF(TRIM(COALESCE(r.Q4, '')), '') IS NULL THEN 0 ELSE 1 END
					) AS group_question_count
				FROM ({$source_union_sql}) src
				INNER JOIN crq_mcq_results r ON r.barcode = src.paper_barcode
				LEFT JOIN schools s ON s.school_id = src.school_id
				WHERE {$where_sql}
				GROUP BY src.school_id, src.student_id, src.subject_code, src.version, src.grade
			) grouped_counts
		";

		$row = $this->db->query($sql)->row_array();
		$max_question_count = (int) ($row['max_question_count'] ?? 0);
		if ($max_question_count <= 0) {
			return [];
		}

		$labels = [];
		for ($i = 1; $i <= $max_question_count; $i++) {
			$labels[] = 'Q' . $i;
		}
		return $labels;
	}

	private function mcq_group_key_from_row(array $row)
	{
		return implode('|', [
			trim((string) ($row['school_id'] ?? '')),
			trim((string) ($row['student_id'] ?? '')),
			trim((string) ($row['subject_code'] ?? '')),
			trim((string) ($row['version'] ?? '')),
			trim((string) ($row['grade'] ?? '')),
		]);
	}

	private function build_mcq_base_row(array $row)
	{
		$district = trim((string) ($row['district_name_en'] ?? ''));
		if ($district === '') $district = trim((string) ($row['school_district'] ?? ''));
		if ($district === '') $district = trim((string) ($row['paper_district'] ?? ''));

		$tehsil = trim((string) ($row['tehsil_name_en'] ?? ''));
		if ($tehsil === '') $tehsil = trim((string) ($row['school_tehsil'] ?? ''));
		if ($tehsil === '') $tehsil = trim((string) ($row['paper_tehsil'] ?? ''));

		$school_name = trim((string) ($row['school_name'] ?? ''));
		if ($school_name === '') $school_name = trim((string) ($row['paper_school_name'] ?? ''));

		$emis_code = trim((string) ($row['school_code'] ?? ''));
		if ($emis_code === '') $emis_code = trim((string) ($row['paper_school_code'] ?? ''));
		if ($emis_code === '') $emis_code = trim((string) ($row['school_lsacode'] ?? ''));

		return [
			'Unique Identifier' => trim((string) ($row['paper_barcode'] ?? '')),
			'School ID' => trim((string) ($row['school_id'] ?? '')),
			'Student ID' => trim((string) ($row['student_id'] ?? '')),
			'EMIS Code' => $emis_code,
			'School Name' => $school_name,
			'District' => $district,
			'Tehsil' => $tehsil,
			'School Admin' => trim((string) ($row['school_admin'] ?? '')),
			'School Level' => trim((string) ($row['school_level'] ?? '')),
			'School Type' => trim((string) ($row['school_type'] ?? '')),
			'Gender' => trim((string) ($row['school_gender'] ?? '')),
			'Grade' => trim((string) ($row['grade'] ?? '')),
			// For merged multi-page rows, keep the first encountered source paper id as the representative exam id.
			'Exam ID' => trim((string) ($row['paper_id'] ?? '')),
			'Subject' => $this->dictation_subject_name($row['subject_code'] ?? ''),
			'Version' => trim((string) ($row['version'] ?? '')),
			'Obtained Marks in Each Question' => '',
		];
	}

	private function build_mcq_row_from_accumulator(array $current, array $question_labels)
	{
		$parts = [];
		foreach ($question_labels as $label) {
			$value = trim((string) ($current['questions'][$label] ?? ''));
			if ($value === '') {
				continue;
			}
			$parts[] = $label . '=' . $value;
		}

		$row = $current['base'];
		$row['Obtained Marks in Each Question'] = implode(', ', $parts);
		foreach ($question_labels as $label) {
			$row[$label] = (string) ($current['questions'][$label] ?? '');
		}

		foreach ($this->result_csv_base_headers as $header) {
			if (!isset($row[$header])) {
				$row[$header] = '';
			}
		}

		return $row;
	}

	private function collect_mcq_rows($filters = [], $limit = null, $question_labels_only = false)
	{
		$sql = $this->build_mcq_export_sql($filters);
		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to query MCQ CSV export rows: ' . $mysqli->error);
		}

		$question_labels = [];
		$rows = [];
		$current_key = null;
		$current = null;
		$group_count = 0;

		try {
			while ($row = $result->fetch_assoc()) {
				$group_key = $this->mcq_group_key_from_row($row);
				if ($current_key !== $group_key) {
					if ($current !== null) {
						$rows[] = $this->build_mcq_row_from_accumulator($current, array_keys($question_labels));
						$group_count++;
						if ($limit !== null && $group_count >= (int) $limit) {
							break;
						}
					}

					$current_key = $group_key;
					$current = [
						'base' => $this->build_mcq_base_row($row),
						'questions' => [],
						'question_index' => 1,
					];
				}

				foreach (['page_q1', 'page_q2', 'page_q3', 'page_q4'] as $page_key) {
					$value = $this->format_mark_value($row[$page_key] ?? null);
					if ($value === '') {
						continue;
					}
					$label = 'Q' . $current['question_index'];
					$current['questions'][$label] = $value;
					$question_labels[$label] = true;
					$current['question_index']++;
				}
			}

			if (!($limit !== null && $group_count >= (int) $limit) && $current !== null) {
				$rows[] = $this->build_mcq_row_from_accumulator($current, array_keys($question_labels));
			}
		} finally {
			$result->free();
		}

		$labels = $this->sort_question_labels(array_keys($question_labels));
		if ($question_labels_only) {
			return $labels;
		}

		// Rebuild rows with final normalized label order so preview/export stay aligned.
		$normalized_rows = [];
		foreach ($rows as $row) {
			$normalized = [];
			foreach ($this->result_csv_base_headers as $header) {
				$normalized[$header] = (string) ($row[$header] ?? '');
			}
			foreach ($labels as $label) {
				$normalized[$label] = (string) ($row[$label] ?? '');
			}
			$normalized_rows[] = $normalized;
		}

		return [
			'question_labels' => $labels,
			'rows' => $normalized_rows,
		];
	}

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

	private function dictation_subject_name($subject_code)
	{
		$map = [
			'1' => 'English',
			'2' => 'Urdu',
			'3' => 'Math',
			'4' => 'Science',
		];
		$code = trim((string) $subject_code);
		return $map[$code] ?? $code;
	}

	private function format_mark_value($value)
	{
		if ($value === null || $value === '') return '';

		$num = (float) $value;
		if (abs($num - round($num)) < 0.00001) {
			return (string) (int) round($num);
		}

		$out = number_format($num, 2, '.', '');
		return rtrim(rtrim($out, '0'), '.');
	}

	private function normalize_dictation_question_no($question_no)
	{
		$q = strtoupper(trim((string) $question_no));
		if (preg_match('/^Q([1-5])$/', $q, $m)) {
			return 'Q' . $m[1];
		}
		return '';
	}

	private function normalize_result_question_no($question_no)
	{
		$q = strtoupper(trim((string) $question_no));
		if (preg_match('/^Q(\d+)$/', $q, $m)) {
			return 'Q' . (int) $m[1];
		}
		return '';
	}

	private function sort_question_labels(array $labels)
	{
		$labels = array_values(array_unique(array_filter(array_map(function ($label) {
			return $this->normalize_result_question_no($label);
		}, $labels))));

		usort($labels, function ($a, $b) {
			$an = (int) preg_replace('/\D+/', '', $a);
			$bn = (int) preg_replace('/\D+/', '', $b);
			if ($an === $bn) return strcmp($a, $b);
			return $an <=> $bn;
		});

		return $labels;
	}

	private function assessment_export_where_sql($assessment_type, $filters = [])
	{
		$assessment_type = strtoupper(trim((string) $assessment_type));
		$where = [
			"q.assessment_type = " . $this->db->escape($assessment_type),
			"qi.assessment_type = " . $this->db->escape($assessment_type),
		];

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$where[] = 'qi.grade = ' . (int) $grade;
		}

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$where[] = 'qi.subject_code = ' . $this->db->escape($subject_code);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$where[] = 'qi.version = ' . (int) $version;
		}

		$district_id = trim((string) ($filters['district_id'] ?? ''));
		if ($district_id !== '') {
			$where[] = 's.school_district_id = ' . (int) $district_id;
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$like = $this->db->escape_like_str($school_query);
			$where[] = "("
				. "s.school_name LIKE '%{$like}%' ESCAPE '!'"
				. " OR s.school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR s.school_lsacode LIKE '%{$like}%' ESCAPE '!'"
				. " OR qi.lsacode LIKE '%{$like}%' ESCAPE '!'"
				. " OR qi.paper_barcode LIKE '%{$like}%' ESCAPE '!'"
				. " OR src1.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR src2.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR src3.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR src4.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. ")";
		}

		return implode(' AND ', $where);
	}

	private function get_assessment_question_labels($assessment_type, $filters = [])
	{
		$assessment_type = strtoupper(trim((string) $assessment_type));

		$this->db->distinct();
		$this->db->select('q.question_no');
		$this->db->from('emarking_questions q');
		$this->db->join('emarking_question_images qi', 'qi.question_id = q.id', 'inner');
		$this->db->where('q.assessment_type', $assessment_type);
		$this->db->where('qi.assessment_type', $assessment_type);

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('qi.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('qi.subject_code', $subject_code);
		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') $this->db->where('qi.version', (int) $version);

		$rows = $this->db->get()->result();
		$labels = [];
		foreach ($rows as $row) {
			$label = $this->normalize_result_question_no($row->question_no ?? '');
			if ($label !== '') $labels[] = $label;
		}
		return $this->sort_question_labels($labels);
	}

	private function get_assessment_csv_headers($assessment_type, $filters = [])
	{
		$question_labels = $this->get_assessment_question_labels($assessment_type, $filters);
		return array_merge($this->result_csv_base_headers, $question_labels, ['Total Obtained']);
	}

	private function get_crq_question_sequence($filters = [])
	{
		$this->db->select('q.id, q.page_no, q.question_no');
		$this->db->from('emarking_questions q');
		$this->db->where('q.assessment_type', 'CRQ');

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') $this->db->where('q.version', (int) $version);

		$this->db->order_by('CAST(COALESCE(NULLIF(q.page_no, \'\'), \'0\') AS UNSIGNED)', 'ASC', false);
		$this->db->order_by('CAST(REPLACE(UPPER(q.question_no), \'Q\', \'\') AS UNSIGNED)', 'ASC', false);
		$this->db->order_by('q.id', 'ASC');

		$rows = $this->db->get()->result();
		$out = [];
		$seq = 1;
		foreach ($rows as $row) {
			$qid = (int) ($row->id ?? 0);
			if ($qid <= 0) {
				continue;
			}
			$out[] = [
				'question_id' => $qid,
				'label' => 'Q' . $seq,
			];
			$seq++;
		}

		return $out;
	}

	private function get_crq_group_keys_page($filters = [], $limit = 50, $offset = 0)
	{
		$where_sql = $this->assessment_export_where_sql('CRQ', $filters);
		$source_tables = ['digital_papers_booklets1', 'digital_papers_booklets2', 'digital_papers_booklets3', 'digital_papers_booklets4'];
		$limit = max(1, (int) $limit);
		$offset = max(0, (int) $offset);

		$sql = "
			SELECT
				qi.school_id,
				qi.roll_no,
				qi.subject_code,
				qi.version,
				qi.grade
			FROM emarking_question_images qi
			INNER JOIN emarking_questions q ON q.id = qi.question_id
			LEFT JOIN schools s ON s.school_id = qi.school_id
			LEFT JOIN {$source_tables[0]} src1 ON qi.source_table = '{$source_tables[0]}' AND src1.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[1]} src2 ON qi.source_table = '{$source_tables[1]}' AND src2.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[2]} src3 ON qi.source_table = '{$source_tables[2]}' AND src3.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[3]} src4 ON qi.source_table = '{$source_tables[3]}' AND src4.paper_id = qi.source_paper_id
			WHERE {$where_sql}
			GROUP BY qi.school_id, qi.roll_no, qi.subject_code, qi.version, qi.grade
			ORDER BY qi.school_id ASC, qi.roll_no ASC
			LIMIT {$limit} OFFSET {$offset}
		";

		return $this->db->query($sql)->result_array();
	}

	private function build_crq_group_keys_where_sql(array $group_keys)
	{
		$clauses = [];
		foreach ($group_keys as $key) {
			$school_id = array_key_exists('school_id', $key) && $key['school_id'] !== null ? (int) $key['school_id'] : null;
			$roll_no = array_key_exists('roll_no', $key) ? trim((string) $key['roll_no']) : '';
			$subject_code = array_key_exists('subject_code', $key) ? trim((string) $key['subject_code']) : '';
			$version = array_key_exists('version', $key) && $key['version'] !== null ? (int) $key['version'] : null;
			$grade = array_key_exists('grade', $key) && $key['grade'] !== null ? (int) $key['grade'] : null;

			$clauses[] = '('
				. ($school_id === null ? 'qi.school_id IS NULL' : ('qi.school_id = ' . $school_id))
				. ' AND '
				. "qi.roll_no = " . $this->db->escape($roll_no)
				. ' AND '
				. "qi.subject_code = " . $this->db->escape($subject_code)
				. ' AND '
				. ($version === null ? 'qi.version IS NULL' : ('qi.version = ' . $version))
				. ' AND '
				. ($grade === null ? 'qi.grade IS NULL' : ('qi.grade = ' . $grade))
				. ')';
		}

		return empty($clauses) ? '' : '(' . implode(' OR ', $clauses) . ')';
	}

	private function build_crq_csv_headers($filters = [])
	{
		$sequence = $this->get_crq_question_sequence($filters);
		$labels = array_map(function ($item) {
			return (string) ($item['label'] ?? '');
		}, $sequence);
		return array_merge($this->result_csv_base_headers, $labels, ['Total Obtained']);
	}

	private function build_crq_export_sql(array $question_sequence, $filters = [], $limit = null, array $group_keys = [])
	{
		$where_sql = $this->assessment_export_where_sql('CRQ', $filters);
		$source_tables = ['digital_papers_booklets1', 'digital_papers_booklets2', 'digital_papers_booklets3', 'digital_papers_booklets4'];
		$group_keys_sql = $this->build_crq_group_keys_where_sql($group_keys);
		if ($group_keys_sql !== '') {
			$where_sql .= ' AND ' . $group_keys_sql;
		}

		$question_selects = [];
		foreach ($question_sequence as $item) {
			$question_id = (int) ($item['question_id'] ?? 0);
			$label = strtolower((string) ($item['label'] ?? ''));
			if ($question_id <= 0 || $label === '') {
				continue;
			}
			$question_selects[] = "MAX(CASE WHEN q.id = {$question_id} THEN sm.marks_obtained END) AS `{$label}`";
		}
		$question_select_sql = empty($question_selects) ? '' : ",\n\t\t\t\t" . implode(",\n\t\t\t\t", $question_selects);

		$limit_sql = '';
		if ($limit !== null) {
			$limit_sql = "\n\t\t\tLIMIT " . max(0, (int) $limit);
		}

		return "
			SELECT
				MAX(qi.paper_barcode) AS unique_identifier,
				MAX(qi.school_id) AS school_id,
				MAX(qi.roll_no) AS student_id,
				COALESCE(
					NULLIF(MAX(s.school_code), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_school_code END), ''),
					NULLIF(MAX(s.school_lsacode), ''),
					NULLIF(MAX(qi.lsacode), '')
				) AS emis_code,
				COALESCE(
					NULLIF(MAX(s.school_name), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_school_name END), '')
				) AS school_name,
				COALESCE(
					NULLIF(MAX(d.district_name_en), ''),
					NULLIF(MAX(s.school_district), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_district END), '')
				) AS district,
				COALESCE(
					NULLIF(MAX(t.tehsil_name_en), ''),
					NULLIF(MAX(s.school_tehsil), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_tehsil END), '')
				) AS tehsil,
				MAX(s.username) AS school_admin,
				MAX(s.school_level) AS school_level,
				MAX(s.school_department) AS school_type,
				MAX(s.school_gender) AS gender,
				MAX(qi.grade) AS grade,
				MAX(qi.source_paper_id) AS exam_id,
				MAX(qi.subject_code) AS subject_code,
				MAX(qi.version) AS version{$question_select_sql}
			FROM emarking_question_images qi
			INNER JOIN emarking_questions q ON q.id = qi.question_id
			LEFT JOIN schools s ON s.school_id = qi.school_id
			LEFT JOIN districts d ON d.district_id = s.school_district_id
			LEFT JOIN tehsils t ON t.tehsil_id = s.school_tehsil_id
			LEFT JOIN {$source_tables[0]} src1 ON qi.source_table = '{$source_tables[0]}' AND src1.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[1]} src2 ON qi.source_table = '{$source_tables[1]}' AND src2.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[2]} src3 ON qi.source_table = '{$source_tables[2]}' AND src3.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[3]} src4 ON qi.source_table = '{$source_tables[3]}' AND src4.paper_id = qi.source_paper_id
			LEFT JOIN (
				SELECT m1.question_image_id, m1.question_id, m1.marks_obtained
				FROM emarking_marks m1
				INNER JOIN (
					SELECT
						question_image_id,
						question_id,
						MAX(
							CONCAT(
								LPAD(COALESCE(is_final, 0), 1, '0'),
								'|',
								IFNULL(DATE_FORMAT(finalized_at, '%Y%m%d%H%i%s'), '00000000000000'),
								'|',
								IFNULL(DATE_FORMAT(marked_at, '%Y%m%d%H%i%s'), '00000000000000'),
								'|',
								LPAD(id, 12, '0')
							)
						) AS pick_key
					FROM emarking_marks
					GROUP BY question_image_id, question_id
				) picked
					ON picked.question_image_id = m1.question_image_id
					AND picked.question_id = m1.question_id
					AND CONCAT(
						LPAD(COALESCE(m1.is_final, 0), 1, '0'),
						'|',
						IFNULL(DATE_FORMAT(m1.finalized_at, '%Y%m%d%H%i%s'), '00000000000000'),
						'|',
						IFNULL(DATE_FORMAT(m1.marked_at, '%Y%m%d%H%i%s'), '00000000000000'),
						'|',
						LPAD(m1.id, 12, '0')
					) = picked.pick_key
			) sm ON sm.question_image_id = qi.id AND sm.question_id = qi.question_id
			WHERE {$where_sql}
			GROUP BY qi.school_id, qi.roll_no, qi.subject_code, qi.version, qi.grade
			ORDER BY MAX(qi.school_id) ASC, MAX(qi.roll_no) ASC{$limit_sql}
		";
	}

	private function build_assessment_export_sql($assessment_type, array $question_labels, $filters = [], $limit = null)
	{
		$assessment_type = strtoupper(trim((string) $assessment_type));
		$where_sql = $this->assessment_export_where_sql($assessment_type, $filters);

		$source_tables = ($assessment_type === 'CRQ')
			? ['digital_papers_booklets1', 'digital_papers_booklets2', 'digital_papers_booklets3', 'digital_papers_booklets4']
			: ['digital_papers_dictation1', 'digital_papers_dictation2', 'digital_papers_dictation3', 'digital_papers_dictation4'];

		$question_selects = [];
		foreach ($question_labels as $label) {
			$escaped = $this->db->escape($label);
			$col_alias = strtolower($label);
			$question_selects[] = "MAX(CASE WHEN UPPER(q.question_no) = {$escaped} THEN sm.marks_obtained END) AS `{$col_alias}`";
		}
		$question_select_sql = empty($question_selects) ? '' : ",\n\t\t\t\t" . implode(",\n\t\t\t\t", $question_selects);

		$limit_sql = '';
		if ($limit !== null) {
			$limit_sql = "\n\t\t\tLIMIT " . max(0, (int) $limit);
		}

		return "
			SELECT
				qi.paper_barcode AS unique_identifier,
				MAX(qi.school_id) AS school_id,
				MAX(qi.roll_no) AS student_id,
				COALESCE(
					NULLIF(MAX(s.school_code), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_school_code END), ''),
					NULLIF(MAX(s.school_lsacode), ''),
					NULLIF(MAX(qi.lsacode), '')
				) AS emis_code,
				COALESCE(
					NULLIF(MAX(s.school_name), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_school_name END), '')
				) AS school_name,
				COALESCE(
					NULLIF(MAX(d.district_name_en), ''),
					NULLIF(MAX(s.school_district), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_district END), '')
				) AS district,
				COALESCE(
					NULLIF(MAX(t.tehsil_name_en), ''),
					NULLIF(MAX(s.school_tehsil), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[0]}' THEN src1.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[1]}' THEN src2.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[2]}' THEN src3.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = '{$source_tables[3]}' THEN src4.paper_tehsil END), '')
				) AS tehsil,
				MAX(s.username) AS school_admin,
				MAX(s.school_level) AS school_level,
				MAX(s.school_department) AS school_type,
				MAX(s.school_gender) AS gender,
				MAX(qi.grade) AS grade,
				MAX(qi.source_paper_id) AS exam_id,
				MAX(qi.subject_code) AS subject_code,
				MAX(qi.version) AS version{$question_select_sql}
			FROM emarking_question_images qi
			INNER JOIN emarking_questions q ON q.id = qi.question_id
			LEFT JOIN schools s ON s.school_id = qi.school_id
			LEFT JOIN districts d ON d.district_id = s.school_district_id
			LEFT JOIN tehsils t ON t.tehsil_id = s.school_tehsil_id
			LEFT JOIN {$source_tables[0]} src1 ON qi.source_table = '{$source_tables[0]}' AND src1.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[1]} src2 ON qi.source_table = '{$source_tables[1]}' AND src2.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[2]} src3 ON qi.source_table = '{$source_tables[2]}' AND src3.paper_id = qi.source_paper_id
			LEFT JOIN {$source_tables[3]} src4 ON qi.source_table = '{$source_tables[3]}' AND src4.paper_id = qi.source_paper_id
			LEFT JOIN (
				SELECT m1.question_image_id, m1.question_id, m1.marks_obtained
				FROM emarking_marks m1
				INNER JOIN (
					SELECT
						question_image_id,
						question_id,
						MAX(
							CONCAT(
								LPAD(COALESCE(is_final, 0), 1, '0'),
								'|',
								IFNULL(DATE_FORMAT(finalized_at, '%Y%m%d%H%i%s'), '00000000000000'),
								'|',
								IFNULL(DATE_FORMAT(marked_at, '%Y%m%d%H%i%s'), '00000000000000'),
								'|',
								LPAD(id, 12, '0')
							)
						) AS pick_key
					FROM emarking_marks
					GROUP BY question_image_id, question_id
				) picked
					ON picked.question_image_id = m1.question_image_id
					AND picked.question_id = m1.question_id
					AND CONCAT(
						LPAD(COALESCE(m1.is_final, 0), 1, '0'),
						'|',
						IFNULL(DATE_FORMAT(m1.finalized_at, '%Y%m%d%H%i%s'), '00000000000000'),
						'|',
						IFNULL(DATE_FORMAT(m1.marked_at, '%Y%m%d%H%i%s'), '00000000000000'),
						'|',
						LPAD(m1.id, 12, '0')
					) = picked.pick_key
			) sm ON sm.question_image_id = qi.id AND sm.question_id = qi.question_id
			WHERE {$where_sql}
			GROUP BY qi.source_table, qi.source_paper_id, qi.paper_barcode
			ORDER BY qi.source_paper_id ASC, qi.paper_barcode ASC{$limit_sql}
		";
	}

	private function build_assessment_csv_row_from_sql_row(array $row, array $question_labels)
	{
		$out = [
			'Unique Identifier' => (string) ($row['unique_identifier'] ?? ''),
			'School ID' => (string) ($row['school_id'] ?? ''),
			'Student ID' => (string) ($row['student_id'] ?? ''),
			'EMIS Code' => (string) ($row['emis_code'] ?? ''),
			'School Name' => (string) ($row['school_name'] ?? ''),
			'District' => (string) ($row['district'] ?? ''),
			'Tehsil' => (string) ($row['tehsil'] ?? ''),
			'School Admin' => (string) ($row['school_admin'] ?? ''),
			'School Level' => (string) ($row['school_level'] ?? ''),
			'School Type' => (string) ($row['school_type'] ?? ''),
			'Gender' => (string) ($row['gender'] ?? ''),
			'Grade' => (string) ($row['grade'] ?? ''),
			'Exam ID' => (string) ($row['exam_id'] ?? ''),
			'Subject' => $this->dictation_subject_name($row['subject_code'] ?? ''),
			'Version' => (string) ($row['version'] ?? ''),
		];

		$parts = [];
		$total = 0.0;
		foreach ($question_labels as $label) {
			$key = strtolower($label);
			$value = $this->format_mark_value($row[$key] ?? null);
			$out[$label] = $value;
			if ($value !== '') {
				$parts[] = $label . '=' . $value;
				$total += (float) $value;
			}
		}

		$out['Obtained Marks in Each Question'] = implode(', ', $parts);
		$out['Total Obtained'] = $this->format_mark_value($total);
		return $out;
	}

	private function dictation_export_where_sql($filters = [])
	{
		$where = [
			"q.assessment_type = 'DICTATION'",
			"qi.assessment_type = 'DICTATION'",
		];

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$where[] = 'qi.grade = ' . (int) $grade;
		}

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$where[] = 'qi.subject_code = ' . $this->db->escape($subject_code);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$where[] = 'qi.version = ' . (int) $version;
		}

		$district_id = trim((string) ($filters['district_id'] ?? ''));
		if ($district_id !== '') {
			$where[] = 's.school_district_id = ' . (int) $district_id;
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$like = $this->db->escape_like_str($school_query);
			$where[] = "("
				. "s.school_name LIKE '%{$like}%' ESCAPE '!'"
				. " OR s.school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR s.school_lsacode LIKE '%{$like}%' ESCAPE '!'"
				. " OR qi.lsacode LIKE '%{$like}%' ESCAPE '!'"
				. " OR qi.paper_barcode LIKE '%{$like}%' ESCAPE '!'"
				. " OR dp1.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. " OR dp2.paper_school_code LIKE '%{$like}%' ESCAPE '!'"
				. ")";
		}

		return implode(' AND ', $where);
	}

	private function build_dictation_csv_query($filters = [])
	{
		// This query intentionally joins both dictation source tables and lets source_table decide
		// which fallback values are used for school and exam metadata.
		$this->db->select("
			qi.id AS question_image_id,
			qi.source_table,
			qi.source_paper_id,
			qi.paper_barcode,
			qi.grade,
			qi.school_id,
			qi.lsacode,
			qi.subject_code,
			qi.version,
			qi.roll_no,
			qi.page_no,
			qi.question_id,
			qi.question_no AS image_question_no,
			q.question_no,
			m.id AS mark_id,
			m.marks_obtained,
			m.marking_status,
			m.is_final,
			m.marked_at,
			m.finalized_at,
			s.school_code,
			s.school_lsacode,
			s.school_name,
			s.username AS school_admin,
			s.school_level,
			s.school_department,
			s.school_gender,
			s.school_district,
			s.school_tehsil,
			d.district_name_en,
			t.tehsil_name_en,
			dp1.paper_school_code AS d1_school_code,
			dp1.paper_school_name AS d1_school_name,
			dp1.paper_district AS d1_district,
			dp1.paper_tehsil AS d1_tehsil,
			dp2.paper_school_code AS d2_school_code,
			dp2.paper_school_name AS d2_school_name,
			dp2.paper_district AS d2_district,
			dp2.paper_tehsil AS d2_tehsil
		", false);
		$this->db->from('emarking_question_images qi');
		$this->db->join('emarking_questions q', 'q.id = qi.question_id', 'inner');
		$this->db->join('emarking_marks m', 'm.question_image_id = qi.id AND m.question_id = q.id', 'left');
		$this->db->join('schools s', 's.school_id = qi.school_id', 'left');
		$this->db->join('districts d', 'd.district_id = s.school_district_id', 'left');
		$this->db->join('tehsils t', 't.tehsil_id = s.school_tehsil_id', 'left');
		$this->db->join('digital_papers_dictation1 dp1', "qi.source_table = 'digital_papers_dictation1' AND dp1.paper_id = qi.source_paper_id", 'left', false);
		$this->db->join('digital_papers_dictation2 dp2', "qi.source_table = 'digital_papers_dictation2' AND dp2.paper_id = qi.source_paper_id", 'left', false);
		$this->db->where('q.assessment_type', 'DICTATION');
		$this->db->where('qi.assessment_type', 'DICTATION');

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$this->db->where('qi.grade', (int) $grade);
		}

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$this->db->where('qi.subject_code', $subject_code);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$this->db->where('qi.version', (int) $version);
		}

		$district_id = trim((string) ($filters['district_id'] ?? ''));
		if ($district_id !== '') {
			$this->db->where('s.school_district_id', (int) $district_id);
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$this->db->group_start()
				->like('s.school_name', $school_query)
				->or_like('s.school_code', $school_query)
				->or_like('s.school_lsacode', $school_query)
				->or_like('qi.lsacode', $school_query)
				->or_like('qi.paper_barcode', $school_query)
				->or_like('dp1.paper_school_code', $school_query)
				->or_like('dp2.paper_school_code', $school_query)
				->group_end();
		}

		$this->db->order_by('qi.source_paper_id', 'ASC');
		$this->db->order_by('qi.paper_barcode', 'ASC');
		$this->db->order_by('qi.question_id', 'ASC');
		$this->db->order_by('m.is_final', 'DESC');
		$this->db->order_by('m.finalized_at', 'DESC');
		$this->db->order_by('m.marked_at', 'DESC');
		$this->db->order_by('m.id', 'DESC');
	}

	private function get_dictation_paper_keys_page($filters = [], $limit = 50, $offset = 0)
	{
		$limit = (int) $limit;
		if ($limit <= 0) $limit = 50;
		$offset = max(0, (int) $offset);

		$this->db->distinct();
		$this->db->select('qi.source_table, qi.source_paper_id, qi.paper_barcode');
		$this->db->from('emarking_question_images qi');
		$this->db->join('emarking_questions q', 'q.id = qi.question_id', 'inner');
		$this->db->join('schools s', 's.school_id = qi.school_id', 'left');
		$this->db->join('digital_papers_dictation1 dp1', "qi.source_table = 'digital_papers_dictation1' AND dp1.paper_id = qi.source_paper_id", 'left', false);
		$this->db->join('digital_papers_dictation2 dp2', "qi.source_table = 'digital_papers_dictation2' AND dp2.paper_id = qi.source_paper_id", 'left', false);
		$this->db->where('q.assessment_type', 'DICTATION');
		$this->db->where('qi.assessment_type', 'DICTATION');

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$this->db->where('qi.grade', (int) $grade);
		}

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$this->db->where('qi.subject_code', $subject_code);
		}

		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') {
			$this->db->where('qi.version', (int) $version);
		}

		$district_id = trim((string) ($filters['district_id'] ?? ''));
		if ($district_id !== '') {
			$this->db->where('s.school_district_id', (int) $district_id);
		}

		$school_query = trim((string) ($filters['school_query'] ?? ''));
		if ($school_query !== '') {
			$this->db->group_start()
				->like('s.school_name', $school_query)
				->or_like('s.school_code', $school_query)
				->or_like('s.school_lsacode', $school_query)
				->or_like('qi.lsacode', $school_query)
				->or_like('qi.paper_barcode', $school_query)
				->or_like('dp1.paper_school_code', $school_query)
				->or_like('dp2.paper_school_code', $school_query)
				->group_end();
		}

		$this->db->order_by('qi.source_paper_id', 'ASC');
		$this->db->order_by('qi.paper_barcode', 'ASC');
		$this->db->limit($limit, $offset);

		return $this->db->get()->result_array();
	}

	private function get_dictation_csv_rows_for_paper_keys($filters, array $paper_keys)
	{
		if (empty($paper_keys)) {
			return [];
		}

		$this->build_dictation_csv_query($filters);
		$this->db->group_start();
		foreach ($paper_keys as $i => $paper) {
			$method = ($i === 0) ? 'group_start' : 'or_group_start';
			$this->db->{$method}();
			$this->db->where('qi.source_table', (string) ($paper['source_table'] ?? ''));
			$this->db->where('qi.source_paper_id', (int) ($paper['source_paper_id'] ?? 0));
			$this->db->where('qi.paper_barcode', (string) ($paper['paper_barcode'] ?? ''));
			$this->db->group_end();
		}
		$this->db->group_end();

		$rows = $this->db->get()->result_array();
		return $this->aggregate_dictation_csv_rows($rows);
	}

	private function aggregate_dictation_csv_rows(array $rows)
	{
		$best_by_image = [];
		foreach ($rows as $row) {
			$image_id = (int) ($row['question_image_id'] ?? 0);
			if ($image_id <= 0 || isset($best_by_image[$image_id])) {
				continue;
			}
			$best_by_image[$image_id] = $row;
		}

		$papers = [];
		foreach ($best_by_image as $row) {
			if (empty($row['mark_id'])) {
				continue;
			}

			$paper_key = trim((string) ($row['source_table'] ?? '')) . '|' . (int) ($row['source_paper_id'] ?? 0) . '|' . trim((string) ($row['paper_barcode'] ?? ''));
			if (!isset($papers[$paper_key])) {
				$district = trim((string) ($row['district_name_en'] ?? ''));
				if ($district === '') $district = trim((string) ($row['school_district'] ?? ''));
				if ($district === '') $district = trim((string) ($row['d1_district'] ?? ''));
				if ($district === '') $district = trim((string) ($row['d2_district'] ?? ''));

				$tehsil = trim((string) ($row['tehsil_name_en'] ?? ''));
				if ($tehsil === '') $tehsil = trim((string) ($row['school_tehsil'] ?? ''));
				if ($tehsil === '') $tehsil = trim((string) ($row['d1_tehsil'] ?? ''));
				if ($tehsil === '') $tehsil = trim((string) ($row['d2_tehsil'] ?? ''));

				$school_name = trim((string) ($row['school_name'] ?? ''));
				if ($school_name === '') $school_name = trim((string) ($row['d1_school_name'] ?? ''));
				if ($school_name === '') $school_name = trim((string) ($row['d2_school_name'] ?? ''));

				$emis_code = trim((string) ($row['school_code'] ?? ''));
				if ($emis_code === '') $emis_code = trim((string) ($row['d1_school_code'] ?? ''));
				if ($emis_code === '') $emis_code = trim((string) ($row['d2_school_code'] ?? ''));
				if ($emis_code === '') $emis_code = trim((string) ($row['school_lsacode'] ?? ''));
				if ($emis_code === '') $emis_code = trim((string) ($row['lsacode'] ?? ''));

				$papers[$paper_key] = [
					'Unique Identifier' => trim((string) ($row['paper_barcode'] ?? '')),
					'School ID' => (string) ($row['school_id'] ?? ''),
					'Student ID' => trim((string) ($row['roll_no'] ?? '')),
					'EMIS Code' => $emis_code,
					'School Name' => $school_name,
					'District' => $district,
					'Tehsil' => $tehsil,
					'School Admin' => trim((string) ($row['school_admin'] ?? '')),
					'School Level' => trim((string) ($row['school_level'] ?? '')),
					// No dedicated school_type column exists in schools; school_department is the nearest stored label.
					'School Type' => trim((string) ($row['school_department'] ?? '')),
					'Gender' => trim((string) ($row['school_gender'] ?? '')),
					'Grade' => (string) ($row['grade'] ?? ''),
					'Exam ID' => (string) ($row['source_paper_id'] ?? ''),
					'Subject' => $this->dictation_subject_name($row['subject_code'] ?? ''),
					'Version' => (string) ($row['version'] ?? ''),
					'Obtained Marks in Each Question' => '',
					'Q1' => '',
					'Q2' => '',
					'Q3' => '',
					'Q4' => '',
					'Q5' => '',
					'Total Obtained' => '0',
				];
			}

			$question_key = $this->normalize_dictation_question_no($row['question_no'] ?? $row['image_question_no'] ?? '');
			if ($question_key === '') {
				continue;
			}

			$papers[$paper_key][$question_key] = $this->format_mark_value($row['marks_obtained'] ?? null);
		}

		$out = [];
		foreach ($papers as $paper) {
			$parts = [];
			$total = 0.0;
			foreach (['Q1', 'Q2', 'Q3', 'Q4', 'Q5'] as $qcol) {
				$raw = trim((string) ($paper[$qcol] ?? ''));
				if ($raw === '') continue;
				$parts[] = $qcol . '=' . $raw;
				$total += (float) $raw;
			}
			$paper['Obtained Marks in Each Question'] = implode(', ', $parts);
			$paper['Total Obtained'] = $this->format_mark_value($total);

			$row = [];
			foreach ($this->dictation_csv_headers as $header) {
				$row[$header] = (string) ($paper[$header] ?? '');
			}
			$out[] = $row;
		}

		return $out;
	}

	public function get_dictation_csv_headers()
	{
		return $this->dictation_csv_headers;
	}

	public function get_dictation_csv_versions($filters = [])
	{
		$this->db->distinct();
		$this->db->select('qi.version');
		$this->db->from('emarking_question_images qi');
		$this->db->join('emarking_questions q', 'q.id = qi.question_id', 'inner');
		$this->db->where('q.assessment_type', 'DICTATION');
		$this->db->where('qi.assessment_type', 'DICTATION');

		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') {
			$this->db->where('qi.subject_code', $subject_code);
		}

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$this->db->where('qi.grade', (int) $grade);
		}

		$this->db->order_by('qi.version', 'ASC');
		$rows = $this->db->get()->result();

		$out = [];
		foreach ($rows as $row) {
			$v = trim((string) ($row->version ?? ''));
			if ($v !== '') $out[] = $v;
		}
		return array_values(array_unique($out));
	}

	public function get_dictation_csv_rows($filters = [], $limit = null)
	{
		if ($limit !== null) {
			$paper_keys = $this->get_dictation_paper_keys_page($filters, $limit, 0);
			return $this->get_dictation_csv_rows_for_paper_keys($filters, $paper_keys);
		}

		$this->build_dictation_csv_query($filters);
		$rows = $this->db->get()->result_array();
		return $this->aggregate_dictation_csv_rows($rows);
	}

	public function get_dictation_csv_rows_page($filters = [], $limit = 200, $offset = 0)
	{
		$paper_keys = $this->get_dictation_paper_keys_page($filters, $limit, $offset);
		return $this->get_dictation_csv_rows_for_paper_keys($filters, $paper_keys);
	}

	public function stream_dictation_csv_export($filters = [], callable $writer)
	{
		$where_sql = $this->dictation_export_where_sql($filters);

		$sql = "
			SELECT
				qi.paper_barcode AS unique_identifier,
				MAX(qi.school_id) AS school_id,
				MAX(qi.roll_no) AS student_id,
				COALESCE(
					NULLIF(MAX(s.school_code), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation1' THEN dp1.paper_school_code END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation2' THEN dp2.paper_school_code END), ''),
					NULLIF(MAX(s.school_lsacode), ''),
					NULLIF(MAX(qi.lsacode), '')
				) AS emis_code,
				COALESCE(
					NULLIF(MAX(s.school_name), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation1' THEN dp1.paper_school_name END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation2' THEN dp2.paper_school_name END), '')
				) AS school_name,
				COALESCE(
					NULLIF(MAX(d.district_name_en), ''),
					NULLIF(MAX(s.school_district), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation1' THEN dp1.paper_district END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation2' THEN dp2.paper_district END), '')
				) AS district,
				COALESCE(
					NULLIF(MAX(t.tehsil_name_en), ''),
					NULLIF(MAX(s.school_tehsil), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation1' THEN dp1.paper_tehsil END), ''),
					NULLIF(MAX(CASE WHEN qi.source_table = 'digital_papers_dictation2' THEN dp2.paper_tehsil END), '')
				) AS tehsil,
				MAX(s.username) AS school_admin,
				MAX(s.school_level) AS school_level,
				MAX(s.school_department) AS school_type,
				MAX(s.school_gender) AS gender,
				MAX(qi.grade) AS grade,
				MAX(qi.source_paper_id) AS exam_id,
				MAX(qi.subject_code) AS subject_code,
				MAX(qi.version) AS version,
				MAX(CASE WHEN UPPER(q.question_no) = 'Q1' THEN sm.marks_obtained END) AS q1,
				MAX(CASE WHEN UPPER(q.question_no) = 'Q2' THEN sm.marks_obtained END) AS q2,
				MAX(CASE WHEN UPPER(q.question_no) = 'Q3' THEN sm.marks_obtained END) AS q3,
				MAX(CASE WHEN UPPER(q.question_no) = 'Q4' THEN sm.marks_obtained END) AS q4,
				MAX(CASE WHEN UPPER(q.question_no) = 'Q5' THEN sm.marks_obtained END) AS q5
			FROM emarking_question_images qi
			INNER JOIN emarking_questions q ON q.id = qi.question_id
			LEFT JOIN schools s ON s.school_id = qi.school_id
			LEFT JOIN districts d ON d.district_id = s.school_district_id
			LEFT JOIN tehsils t ON t.tehsil_id = s.school_tehsil_id
			LEFT JOIN digital_papers_dictation1 dp1 ON qi.source_table = 'digital_papers_dictation1' AND dp1.paper_id = qi.source_paper_id
			LEFT JOIN digital_papers_dictation2 dp2 ON qi.source_table = 'digital_papers_dictation2' AND dp2.paper_id = qi.source_paper_id
			LEFT JOIN (
				SELECT m1.question_image_id, m1.question_id, m1.marks_obtained
				FROM emarking_marks m1
				INNER JOIN (
					SELECT
						question_image_id,
						question_id,
						MAX(
							CONCAT(
								LPAD(COALESCE(is_final, 0), 1, '0'),
								'|',
								IFNULL(DATE_FORMAT(finalized_at, '%Y%m%d%H%i%s'), '00000000000000'),
								'|',
								IFNULL(DATE_FORMAT(marked_at, '%Y%m%d%H%i%s'), '00000000000000'),
								'|',
								LPAD(id, 12, '0')
							)
						) AS pick_key
					FROM emarking_marks
					GROUP BY question_image_id, question_id
				) picked
					ON picked.question_image_id = m1.question_image_id
					AND picked.question_id = m1.question_id
					AND CONCAT(
						LPAD(COALESCE(m1.is_final, 0), 1, '0'),
						'|',
						IFNULL(DATE_FORMAT(m1.finalized_at, '%Y%m%d%H%i%s'), '00000000000000'),
						'|',
						IFNULL(DATE_FORMAT(m1.marked_at, '%Y%m%d%H%i%s'), '00000000000000'),
						'|',
						LPAD(m1.id, 12, '0')
					) = picked.pick_key
			) sm ON sm.question_image_id = qi.id AND sm.question_id = qi.question_id
			WHERE {$where_sql}
			GROUP BY qi.source_table, qi.source_paper_id, qi.paper_barcode
			ORDER BY qi.source_paper_id ASC, qi.paper_barcode ASC
		";

		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to stream dictation CSV export: ' . $mysqli->error);
		}

		try {
			while ($row = $result->fetch_assoc()) {
				$q1 = $this->format_mark_value($row['q1'] ?? null);
				$q2 = $this->format_mark_value($row['q2'] ?? null);
				$q3 = $this->format_mark_value($row['q3'] ?? null);
				$q4 = $this->format_mark_value($row['q4'] ?? null);
				$q5 = $this->format_mark_value($row['q5'] ?? null);

				$parts = [];
				foreach (['Q1' => $q1, 'Q2' => $q2, 'Q3' => $q3, 'Q4' => $q4, 'Q5' => $q5] as $label => $value) {
					if ($value !== '') $parts[] = $label . '=' . $value;
				}

				$total = 0.0;
				foreach ([$q1, $q2, $q3, $q4, $q5] as $value) {
					if ($value !== '') $total += (float) $value;
				}

				$writer([
					'Unique Identifier' => (string) ($row['unique_identifier'] ?? ''),
					'School ID' => (string) ($row['school_id'] ?? ''),
					'Student ID' => (string) ($row['student_id'] ?? ''),
					'EMIS Code' => (string) ($row['emis_code'] ?? ''),
					'School Name' => (string) ($row['school_name'] ?? ''),
					'District' => (string) ($row['district'] ?? ''),
					'Tehsil' => (string) ($row['tehsil'] ?? ''),
					'School Admin' => (string) ($row['school_admin'] ?? ''),
					'School Level' => (string) ($row['school_level'] ?? ''),
					'School Type' => (string) ($row['school_type'] ?? ''),
					'Gender' => (string) ($row['gender'] ?? ''),
					'Grade' => (string) ($row['grade'] ?? ''),
					'Exam ID' => (string) ($row['exam_id'] ?? ''),
					'Subject' => $this->dictation_subject_name($row['subject_code'] ?? ''),
					'Version' => (string) ($row['version'] ?? ''),
					'Obtained Marks in Each Question' => implode(', ', $parts),
					'Q1' => $q1,
					'Q2' => $q2,
					'Q3' => $q3,
					'Q4' => $q4,
					'Q5' => $q5,
					'Total Obtained' => $this->format_mark_value($total),
				]);
			}
		} finally {
			$result->free();
		}
	}

	public function get_crq_csv_headers($filters = [])
	{
		return $this->build_crq_csv_headers($filters);
	}

	public function get_crq_csv_rows($filters = [], $limit = 50)
	{
		$question_sequence = $this->get_crq_question_sequence($filters);
		if (empty($question_sequence)) {
			return [];
		}

		$group_keys = $this->get_crq_group_keys_page($filters, $limit, 0);
		if (empty($group_keys)) {
			return [];
		}

		$question_labels = array_map(function ($item) {
			return (string) ($item['label'] ?? '');
		}, $question_sequence);
		$sql = $this->build_crq_export_sql($question_sequence, $filters, null, $group_keys);
		$rows = $this->db->query($sql)->result_array();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->build_assessment_csv_row_from_sql_row($row, $question_labels);
		}
		return $out;
	}

	public function stream_crq_csv_export($filters = [], callable $writer)
	{
		$question_sequence = $this->get_crq_question_sequence($filters);
		if (empty($question_sequence)) {
			return;
		}

		$question_labels = array_map(function ($item) {
			return (string) ($item['label'] ?? '');
		}, $question_sequence);
		$sql = $this->build_crq_export_sql($question_sequence, $filters, null);
		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to stream CRQ CSV export: ' . $mysqli->error);
		}

		try {
			while ($row = $result->fetch_assoc()) {
				$writer($this->build_assessment_csv_row_from_sql_row($row, $question_labels));
			}
		} finally {
			$result->free();
		}
	}

	public function get_mcq_csv_headers($filters = [])
	{
		$question_labels = $this->get_mcq_question_labels($filters);
		return array_merge($this->result_csv_base_headers, $question_labels);
	}

	public function get_mcq_csv_rows($filters = [], $limit = 50)
	{
		$data = $this->collect_mcq_rows($filters, $limit);
		return $data['rows'] ?? [];
	}

	public function stream_mcq_csv_export($filters = [], callable $writer)
	{
		$question_labels = $this->get_mcq_question_labels($filters);
		if (empty($question_labels)) {
			return;
		}

		$sql = $this->build_mcq_export_sql($filters);
		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to stream MCQ CSV export: ' . $mysqli->error);
		}

		$current_key = null;
		$current = null;

		try {
			while ($row = $result->fetch_assoc()) {
				$group_key = $this->mcq_group_key_from_row($row);
				if ($current_key !== $group_key) {
					if ($current !== null) {
						$writer($this->build_mcq_row_from_accumulator($current, $question_labels));
					}

					$current_key = $group_key;
					$current = [
						'base' => $this->build_mcq_base_row($row),
						'questions' => [],
						'question_index' => 1,
					];
				}

				foreach (['page_q1', 'page_q2', 'page_q3', 'page_q4'] as $page_key) {
					$value = $this->format_mark_value($row[$page_key] ?? null);
					if ($value === '') {
						continue;
					}
					$label = 'Q' . $current['question_index'];
					$current['questions'][$label] = $value;
					$current['question_index']++;
				}
			}

			if ($current !== null) {
				$writer($this->build_mcq_row_from_accumulator($current, $question_labels));
			}
		} finally {
			$result->free();
		}
	}

	public function get_bq_source_tables()
	{
		return $this->bq_allowed_tables();
	}

	public function get_bq_csv_headers($filters = [])
	{
		$table = trim((string) ($filters['source_table'] ?? ''));
		$question_labels = $this->get_bq_question_labels($table);
		return array_merge($this->bq_csv_base_headers, $question_labels);
	}

	public function get_bq_csv_rows($filters = [], $limit = 50)
	{
		$table = trim((string) ($filters['source_table'] ?? ''));
		if ($table === '' || !in_array($table, $this->bq_allowed_tables(), true)) {
			return [];
		}

		$question_labels = $this->get_bq_question_labels($table);
		$sql = $this->build_bq_export_sql($table, $filters, $limit);
		$query = $this->db->query($sql);
		if ($query === false) {
			$error = $this->db->error();
			throw new RuntimeException('Unable to load BQ CSV rows: ' . (string) ($error['message'] ?? 'Unknown database error'));
		}
		$rows = $query->result_array();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->build_bq_csv_row_from_sql_row($row, $question_labels);
		}
		return $out;
	}

	public function stream_bq_csv_export($filters = [], callable $writer)
	{
		$table = trim((string) ($filters['source_table'] ?? ''));
		if ($table === '' || !in_array($table, $this->bq_allowed_tables(), true)) {
			return;
		}

		$question_labels = $this->get_bq_question_labels($table);
		$sql = $this->build_bq_export_sql($table, $filters, null);
		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to stream BQ CSV export: ' . $mysqli->error);
		}

		try {
			while ($row = $result->fetch_assoc()) {
				$writer($this->build_bq_csv_row_from_sql_row($row, $question_labels));
			}
		} finally {
			$result->free();
		}
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

		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('q.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		}

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
			SUM(CASE WHEN qi.status='UPLOADED' THEN 1 ELSE 0 END) AS uploaded,
			SUM(CASE WHEN qi.status='ASSIGNED' THEN 1 ELSE 0 END) AS assigned,
			SUM(CASE WHEN qi.status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN qi.status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN qi.status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(CASE WHEN qi.status='RECHECK' THEN 1 ELSE 0 END) AS recheck,
			SUM(CASE WHEN qi.status='FINALIZED' THEN 1 ELSE 0 END) AS finalized
		", false);
		$this->db->from('emarking_question_images qi');
		$this->db->join('emarking_questions q', 'q.id = qi.question_id', 'inner');

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('q.assessment_type', $assessment_type);
		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);

		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('q.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		}

		if ($from) $this->db->where('qi.created_at >=', $from);
		if ($to) $this->db->where('qi.created_at <=', $to);
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
		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('q.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		}

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
			GROUP_CONCAT(DISTINCT q.subject_code ORDER BY q.subject_code SEPARATOR ', ') AS subjects,
			COUNT(m.id) AS total_actions,
			SUM(CASE WHEN m.marking_status='MARKED' THEN 1 ELSE 0 END) AS marked,
			SUM(CASE WHEN m.marking_status='SKIPPED' THEN 1 ELSE 0 END) AS skipped,
			SUM(CASE WHEN m.marking_status='NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
			SUM(m.marks_obtained) AS total_marks
		", false);
		$this->db->from('emarking_marks m');
		$this->db->join('users u', 'u.id = m.emarker_id', 'left');
		$this->db->join('emarking_questions q', 'q.id = m.question_id', 'inner');
		if ($from) $this->db->where('m.marked_at >=', $from);
		if ($to) $this->db->where('m.marked_at <=', $to);

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') $this->db->where('q.assessment_type', $assessment_type);
		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);
		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('q.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		}

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
			GROUP_CONCAT(DISTINCT q.subject_code ORDER BY q.subject_code SEPARATOR ', ') AS subjects,
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
		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('q.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		}

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
		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) $this->db->where_in('b.subject_code', $subject_code);
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') $this->db->where('b.subject_code', $subject_code);
		}

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
