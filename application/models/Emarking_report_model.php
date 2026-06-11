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
			'sheet_10',
			'sheet_11',
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
				COALESCE(sch.school_gender, '') AS school_gender,
				TRIM(COALESCE(src.Gender, '')) AS source_gender,
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
			'School ID' => (string) ($row['school_admin'] ?? ''),
			'Student / Teacher ID' => (string) ($row['student_teacher_id'] ?? ''),
			'EMIS Code' => (string) ($row['emis_code'] ?? ''),
			'School Name' => (string) ($row['school_name'] ?? ''),
			'District' => (string) ($row['district'] ?? ''),
			'Tehsil' => (string) ($row['tehsil'] ?? ''),
			'School Admin' => (string) ($row['school_type'] ?? ''),
			'School Level' => (string) ($row['school_level'] ?? ''),
			'School Type' => (string) ($row['school_gender'] ?? ''),
			'Gender' => (string) ($row['source_gender'] ?? ''),
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

	private function student_group_key_from_parts($school_id, $student_id, $subject_code, $version, $grade)
	{
		return implode('|', [
			trim((string) $school_id),
			trim((string) $student_id),
			trim((string) $subject_code),
			trim((string) $version),
			trim((string) $grade),
		]);
	}

	private function mcq_group_key_from_row(array $row)
	{
		return $this->student_group_key_from_parts(
			$row['school_id'] ?? '',
			$row['student_id'] ?? '',
			$row['subject_code'] ?? '',
			$row['version'] ?? '',
			$row['grade'] ?? ''
		);
	}

	private function build_mcq_group_keys_where_sql(array $group_keys)
	{
		$clauses = [];
		foreach ($group_keys as $key) {
			$school_id = array_key_exists('school_id', $key) && $key['school_id'] !== null ? (int) $key['school_id'] : null;
			$student_id = array_key_exists('student_id', $key) ? trim((string) $key['student_id']) : '';
			$subject_code = array_key_exists('subject_code', $key) ? trim((string) $key['subject_code']) : '';
			$version = array_key_exists('version', $key) && $key['version'] !== null ? (int) $key['version'] : null;
			$grade = array_key_exists('grade', $key) && $key['grade'] !== null ? (int) $key['grade'] : null;

			$clauses[] = '('
				. ($school_id === null ? 'src.school_id IS NULL' : ('src.school_id = ' . $school_id))
				. ' AND '
				. 'src.student_id = ' . $this->db->escape($student_id)
				. ' AND '
				. 'src.subject_code = ' . $this->db->escape($subject_code)
				. ' AND '
				. ($version === null ? 'src.version IS NULL' : ('src.version = ' . $version))
				. ' AND '
				. ($grade === null ? 'src.grade IS NULL' : ('src.grade = ' . $grade))
				. ')';
		}

		return empty($clauses) ? '' : '(' . implode(' OR ', $clauses) . ')';
	}

	private function build_mcq_export_sql($filters = [], array $group_keys = [])
	{
		$where_sql = $this->mcq_export_where_sql($filters);
		$source_union_sql = $this->mcq_source_union_sql($filters);
		$group_keys_sql = $this->build_mcq_group_keys_where_sql($group_keys);
		if ($group_keys_sql !== '') {
			$where_sql .= ' AND ' . $group_keys_sql;
		}

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
		];
	}

	private function build_mcq_row_from_accumulator(array $current, array $question_labels)
	{
		$row = $current['base'];
		foreach ($question_labels as $label) {
			$row[$label] = (string) ($current['questions'][$label] ?? '');
		}

		foreach ($this->result_csv_base_headers as $header) {
			if (!isset($row[$header])) {
				$row[$header] = '';
			}
		}

		return $this->apply_non_bq_result_field_mapping($row);
	}

	private function collect_mcq_rows($filters = [], $limit = null, $question_labels_only = false, array $group_keys = [])
	{
		$sql = $this->build_mcq_export_sql($filters, $group_keys);
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

	private function apply_non_bq_result_field_mapping(array $row)
	{
		$row['School ID'] = trim((string) ($row['School Admin'] ?? ''));
		$row['School Admin'] = trim((string) ($row['School Type'] ?? ''));
		$row['School Type'] = trim((string) ($row['Gender'] ?? ''));
		$row['Gender'] = '';
		$row['Exam ID'] = 'LSA-4';
		return $row;
	}

	private function apply_dictation_school_gender_format(array $row)
	{
		$row['School Type'] = trim((string) ($row['School Type'] ?? ''));
		$row['Gender'] = $this->combined_gender_code_from_school_gender($row['School Type'] ?? '');
		return $row;
	}

	private function apply_crq_school_gender_format(array $row)
	{
		$row['School Type'] = trim((string) ($row['School Type'] ?? ''));
		$row['Gender'] = $this->combined_gender_code_from_school_gender($row['School Type'] ?? '');
		return $row;
	}

	private function subject_sort_rank($subject)
	{
		$subject = trim((string) $subject);
		$map = [
			'1' => 1,
			'ENGLISH' => 1,
			'ENGLISH ' => 1,
			'2' => 2,
			'URDU' => 2,
			'3' => 3,
			'MATH' => 3,
			'4' => 4,
			'SCIENCE' => 4,
		];

		$key = strtoupper($subject);
		if (isset($map[$key])) {
			return $map[$key];
		}

		if (ctype_digit($subject)) {
			return (int) $subject;
		}

		return 999;
	}

	private function combined_gender_code_from_school_gender($school_gender)
	{
		$value = strtoupper(trim((string) $school_gender));
		if ($value === 'MALE' || $value === 'M') {
			return 'M';
		}
		if ($value === 'FEMALE' || $value === 'F') {
			return 'F';
		}
		if ($value === 'BOTH') {
			return random_int(0, 1) === 0 ? 'F' : 'M';
		}
		return '';
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

	private function get_crq_question_sequences($filters = [])
	{
		$this->db->select('q.id, q.grade, q.subject_code, q.version, q.page_no, q.question_no');
		$this->db->from('emarking_questions q');
		$this->db->where('q.assessment_type', 'CRQ');

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') $this->db->where('q.version', (int) $version);

		$this->db->order_by('q.grade', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$this->db->order_by('q.version', 'ASC');
		$this->db->order_by('CAST(COALESCE(NULLIF(q.page_no, \'\'), \'0\') AS UNSIGNED)', 'ASC', false);
		$this->db->order_by('CAST(REPLACE(UPPER(q.question_no), \'Q\', \'\') AS UNSIGNED)', 'ASC', false);
		$this->db->order_by('q.id', 'ASC');

		$rows = $this->db->get()->result();
		$sequences = [];
		foreach ($rows as $row) {
			$question_id = (int) ($row->id ?? 0);
			if ($question_id <= 0) {
				continue;
			}
			$key = implode('|', [
				(int) ($row->grade ?? 0),
				trim((string) ($row->subject_code ?? '')),
				(int) ($row->version ?? 0),
			]);
			if (!isset($sequences[$key])) {
				$sequences[$key] = [
					'grade' => (int) ($row->grade ?? 0),
					'subject_code' => trim((string) ($row->subject_code ?? '')),
					'version' => (int) ($row->version ?? 0),
					'question_ids' => [],
				];
			}
			$sequences[$key]['question_ids'][] = $question_id;
		}

		return $sequences;
	}

	private function get_crq_question_labels($filters = [])
	{
		$sequences = $this->get_crq_question_sequences($filters);
		$max_count = 0;
		foreach ($sequences as $sequence) {
			$count = count($sequence['question_ids'] ?? []);
			if ($count > $max_count) {
				$max_count = $count;
			}
		}

		$labels = [];
		for ($i = 1; $i <= $max_count; $i++) {
			$labels[] = 'Q' . $i;
		}

		return $labels;
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
			$roll_no = array_key_exists('roll_no', $key)
				? trim((string) $key['roll_no'])
				: trim((string) ($key['student_id'] ?? ''));
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
		$labels = $this->get_crq_question_labels($filters);
		return array_merge($this->result_csv_base_headers, $labels, ['Total Obtained']);
	}

	private function build_crq_export_sql(array $question_sequences, $filters = [], $limit = null, array $group_keys = [])
	{
		$where_sql = $this->assessment_export_where_sql('CRQ', $filters);
		$source_tables = ['digital_papers_booklets1', 'digital_papers_booklets2', 'digital_papers_booklets3', 'digital_papers_booklets4'];
		$group_keys_sql = $this->build_crq_group_keys_where_sql($group_keys);
		if ($group_keys_sql !== '') {
			$where_sql .= ' AND ' . $group_keys_sql;
		}

		$question_selects = [];
		$max_questions = 0;
		foreach ($question_sequences as $sequence) {
			$count = count($sequence['question_ids'] ?? []);
			if ($count > $max_questions) {
				$max_questions = $count;
			}
		}

		for ($index = 0; $index < $max_questions; $index++) {
			$question_ids = [];
			foreach ($question_sequences as $sequence) {
				$id = (int) ($sequence['question_ids'][$index] ?? 0);
				if ($id > 0) {
					$question_ids[$id] = $id;
				}
			}
			if (empty($question_ids)) {
				continue;
			}
			$label = 'q' . ($index + 1);
			$question_selects[] = "MAX(CASE WHEN q.id IN (" . implode(', ', $question_ids) . ") THEN sm.marks_obtained END) AS `{$label}`";
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

		$out['Total Obtained'] = $this->format_mark_value($total);
		return $this->apply_non_bq_result_field_mapping($out);
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
			$total = 0.0;
			foreach (['Q1', 'Q2', 'Q3', 'Q4', 'Q5'] as $qcol) {
				$raw = trim((string) ($paper[$qcol] ?? ''));
				if ($raw === '') continue;
				$total += (float) $raw;
			}
			$paper['Total Obtained'] = $this->format_mark_value($total);
			$paper = $this->apply_non_bq_result_field_mapping($paper);
			$paper = $this->apply_dictation_school_gender_format($paper);

			$row = [];
			foreach ($this->dictation_csv_headers as $header) {
				$row[$header] = (string) ($paper[$header] ?? '');
			}
			$out[] = $row;
		}

		return $out;
	}

	private function get_dictation_question_sequences($filters = [])
	{
		$this->db->select('q.id, q.grade, q.subject_code, q.version, q.page_no, q.question_no');
		$this->db->from('emarking_questions q');
		$this->db->where('q.assessment_type', 'DICTATION');

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);
		$subject_code = trim((string) ($filters['subject_code'] ?? ''));
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);
		$version = trim((string) ($filters['version'] ?? ''));
		if ($version !== '') $this->db->where('q.version', (int) $version);

		$this->db->order_by('q.grade', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$this->db->order_by('q.version', 'ASC');
		$this->db->order_by('CAST(COALESCE(NULLIF(q.page_no, \'\'), \'0\') AS UNSIGNED)', 'ASC', false);
		$this->db->order_by('CAST(REPLACE(UPPER(q.question_no), \'Q\', \'\') AS UNSIGNED)', 'ASC', false);
		$this->db->order_by('q.id', 'ASC');

		$rows = $this->db->get()->result();
		$sequences = [];
		foreach ($rows as $row) {
			$question_id = (int) ($row->id ?? 0);
			if ($question_id <= 0) {
				continue;
			}
			$key = implode('|', [
				(int) ($row->grade ?? 0),
				trim((string) ($row->subject_code ?? '')),
				(int) ($row->version ?? 0),
			]);
			if (!isset($sequences[$key])) {
				$sequences[$key] = [
					'grade' => (int) ($row->grade ?? 0),
					'subject_code' => trim((string) ($row->subject_code ?? '')),
					'version' => (int) ($row->version ?? 0),
					'question_ids' => [],
				];
			}
			$sequences[$key]['question_ids'][] = $question_id;
		}

		return $sequences;
	}

	private function get_dictation_question_labels($filters = [])
	{
		$sequences = $this->get_dictation_question_sequences($filters);
		$max_count = 0;
		foreach ($sequences as $sequence) {
			$count = count($sequence['question_ids'] ?? []);
			if ($count > $max_count) {
				$max_count = $count;
			}
		}

		$labels = [];
		for ($i = 1; $i <= $max_count; $i++) {
			$labels[] = 'Q' . $i;
		}

		return $labels;
	}

	private function build_dictation_csv_headers($filters = [])
	{
		return array_merge($this->result_csv_base_headers, $this->get_dictation_question_labels($filters), ['Total Obtained']);
	}

	private function get_dictation_group_keys_page($filters = [], $limit = 50, $offset = 0)
	{
		$where_sql = $this->dictation_export_where_sql($filters);
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
			LEFT JOIN digital_papers_dictation1 dp1 ON qi.source_table = 'digital_papers_dictation1' AND dp1.paper_id = qi.source_paper_id
			LEFT JOIN digital_papers_dictation2 dp2 ON qi.source_table = 'digital_papers_dictation2' AND dp2.paper_id = qi.source_paper_id
			WHERE {$where_sql}
			GROUP BY qi.school_id, qi.roll_no, qi.subject_code, qi.version, qi.grade
			ORDER BY
				CAST(COALESCE(NULLIF(qi.subject_code, ''), '999') AS UNSIGNED) ASC,
				COALESCE(qi.version, 0) ASC,
				COALESCE(qi.school_id, 0) ASC,
				qi.roll_no ASC,
				COALESCE(qi.grade, 0) ASC
			LIMIT {$limit} OFFSET {$offset}
		";

		return $this->db->query($sql)->result_array();
	}

	private function build_dictation_group_keys_where_sql(array $group_keys)
	{
		$clauses = [];
		foreach ($group_keys as $key) {
			$school_id = array_key_exists('school_id', $key) && $key['school_id'] !== null ? (int) $key['school_id'] : null;
			$roll_no = array_key_exists('roll_no', $key)
				? trim((string) $key['roll_no'])
				: trim((string) ($key['student_id'] ?? ''));
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

	private function build_dictation_export_sql(array $question_sequences, $filters = [], $limit = null, array $group_keys = [])
	{
		$where_sql = $this->dictation_export_where_sql($filters);
		$group_keys_sql = $this->build_dictation_group_keys_where_sql($group_keys);
		if ($group_keys_sql !== '') {
			$where_sql .= ' AND ' . $group_keys_sql;
		}

		$question_selects = [];
		$max_questions = 0;
		foreach ($question_sequences as $sequence) {
			$count = count($sequence['question_ids'] ?? []);
			if ($count > $max_questions) {
				$max_questions = $count;
			}
		}

		for ($index = 0; $index < $max_questions; $index++) {
			$question_ids = [];
			foreach ($question_sequences as $sequence) {
				$id = (int) ($sequence['question_ids'][$index] ?? 0);
				if ($id > 0) {
					$question_ids[$id] = $id;
				}
			}
			if (empty($question_ids)) {
				continue;
			}
			$label = 'q' . ($index + 1);
			$question_selects[] = "MAX(CASE WHEN q.id IN (" . implode(', ', $question_ids) . ") THEN sm.marks_obtained END) AS `{$label}`";
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
				MAX(qi.version) AS version{$question_select_sql}
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
			GROUP BY qi.school_id, qi.roll_no, qi.subject_code, qi.version, qi.grade
			ORDER BY
				CAST(COALESCE(NULLIF(MAX(qi.subject_code), ''), '999') AS UNSIGNED) ASC,
				COALESCE(MAX(qi.version), 0) ASC,
				COALESCE(MAX(qi.school_id), 0) ASC,
				MAX(qi.roll_no) ASC,
				COALESCE(MAX(qi.grade), 0) ASC{$limit_sql}
		";
	}

	private function build_dictation_csv_row_from_sql_row(array $row, array $question_labels)
	{
		return $this->apply_dictation_school_gender_format($this->build_assessment_csv_row_from_sql_row($row, $question_labels));
	}

	public function get_dictation_csv_headers($filters = [])
	{
		return $this->build_dictation_csv_headers($filters);
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
		$question_sequences = $this->get_dictation_question_sequences($filters);
		$question_labels = $this->get_dictation_question_labels($filters);
		if (empty($question_labels)) {
			return [];
		}

		$group_keys = [];
		if ($limit !== null) {
			$group_keys = $this->get_dictation_group_keys_page($filters, $limit, 0);
			if (empty($group_keys)) {
				return [];
			}
		}

		$sql = $this->build_dictation_export_sql($question_sequences, $filters, null, $group_keys);
		$rows = $this->db->query($sql)->result_array();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->build_dictation_csv_row_from_sql_row($row, $question_labels);
		}
		return $out;
	}

	public function get_dictation_csv_rows_page($filters = [], $limit = 200, $offset = 0)
	{
		$question_sequences = $this->get_dictation_question_sequences($filters);
		$question_labels = $this->get_dictation_question_labels($filters);
		if (empty($question_labels)) {
			return [];
		}

		$group_keys = $this->get_dictation_group_keys_page($filters, $limit, $offset);
		if (empty($group_keys)) {
			return [];
		}

		$sql = $this->build_dictation_export_sql($question_sequences, $filters, null, $group_keys);
		$rows = $this->db->query($sql)->result_array();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->build_dictation_csv_row_from_sql_row($row, $question_labels);
		}
		return $out;
	}

	public function stream_dictation_csv_export($filters = [], callable $writer)
	{
		$question_sequences = $this->get_dictation_question_sequences($filters);
		$question_labels = $this->get_dictation_question_labels($filters);
		if (empty($question_labels)) {
			return;
		}

		$sql = $this->build_dictation_export_sql($question_sequences, $filters);

		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to stream dictation CSV export: ' . $mysqli->error);
		}

		try {
			while ($row = $result->fetch_assoc()) {
				$writer($this->build_dictation_csv_row_from_sql_row($row, $question_labels));
			}
		} finally {
			$result->free();
		}
	}

	public function get_crq_csv_headers($filters = [])
	{
		return $this->build_crq_csv_headers($filters);
	}

	private function get_mcq_crq_question_labels($filters = [])
	{
		$mcq_labels = $this->get_mcq_question_labels($filters);
		$crq_labels = $this->get_crq_question_labels($filters);
		$total_count = count($mcq_labels) + count($crq_labels);
		$labels = [];
		for ($i = 1; $i <= $total_count; $i++) {
			$labels[] = 'Q' . $i;
		}
		return [
			'mcq_labels' => $mcq_labels,
			'crq_labels' => $crq_labels,
			'merged_labels' => $labels,
		];
	}

	private function get_mcq_crq_preview_group_keys($filters = [], $limit = 50)
	{
		$limit = max(1, (int) $limit);
		$mcq_where_sql = $this->mcq_export_where_sql($filters);
		$mcq_source_union_sql = $this->mcq_source_union_sql($filters);
		$crq_where_sql = $this->assessment_export_where_sql('CRQ', $filters);
		$source_tables = ['digital_papers_booklets1', 'digital_papers_booklets2', 'digital_papers_booklets3', 'digital_papers_booklets4'];

		$sql = "
			SELECT
				combined.school_id,
				combined.student_id,
				combined.subject_code,
				combined.version,
				combined.grade
			FROM (
				SELECT DISTINCT
					src.school_id,
					TRIM(COALESCE(src.student_id, '')) AS student_id,
					TRIM(COALESCE(src.subject_code, '')) AS subject_code,
					src.version,
					src.grade
				FROM ({$mcq_source_union_sql}) src
				INNER JOIN crq_mcq_results r ON r.barcode = src.paper_barcode
				LEFT JOIN schools s ON s.school_id = src.school_id
				WHERE {$mcq_where_sql}

				UNION

				SELECT DISTINCT
					qi.school_id,
					TRIM(COALESCE(qi.roll_no, '')) AS student_id,
					TRIM(COALESCE(qi.subject_code, '')) AS subject_code,
					qi.version,
					qi.grade
				FROM emarking_question_images qi
				INNER JOIN emarking_questions q ON q.id = qi.question_id
				LEFT JOIN schools s ON s.school_id = qi.school_id
				LEFT JOIN {$source_tables[0]} src1 ON qi.source_table = '{$source_tables[0]}' AND src1.paper_id = qi.source_paper_id
				LEFT JOIN {$source_tables[1]} src2 ON qi.source_table = '{$source_tables[1]}' AND src2.paper_id = qi.source_paper_id
				LEFT JOIN {$source_tables[2]} src3 ON qi.source_table = '{$source_tables[2]}' AND src3.paper_id = qi.source_paper_id
				LEFT JOIN {$source_tables[3]} src4 ON qi.source_table = '{$source_tables[3]}' AND src4.paper_id = qi.source_paper_id
				WHERE {$crq_where_sql}
			) combined
			WHERE combined.student_id <> ''
			ORDER BY
				CAST(COALESCE(NULLIF(combined.subject_code, ''), '999') AS UNSIGNED) ASC,
				COALESCE(combined.version, 0) ASC,
				COALESCE(combined.school_id, 0) ASC,
				combined.student_id ASC,
				COALESCE(combined.grade, 0) ASC
			LIMIT {$limit}
		";

		return $this->db->query($sql)->result_array();
	}

	private function collect_all_crq_csv_rows($filters = [], array $group_keys = [])
	{
		$question_sequences = $this->get_crq_question_sequences($filters);
		if (empty($question_sequences)) {
			return [];
		}

		$question_labels = $this->get_crq_question_labels($filters);
		$sql = $this->build_crq_export_sql($question_sequences, $filters, null, $group_keys);
		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to query CRQ CSV export rows: ' . $mysqli->error);
		}

		$rows = [];
		try {
			while ($row = $result->fetch_assoc()) {
				$rows[] = $this->apply_crq_school_gender_format(
					$this->build_assessment_csv_row_from_sql_row($row, $question_labels)
				);
			}
		} finally {
			$result->free();
		}

		return $rows;
	}

	private function merge_mcq_crq_csv_rows(array $mcq_rows, array $crq_rows, array $merged_question_labels, array $mcq_labels)
	{
		$rows = [];
		$order = [];
		$mcq_question_count = count($mcq_labels);
		$crq_question_count = max(0, count($merged_question_labels) - $mcq_question_count);

		$row_group_key = function (array $row) {
			return $this->student_group_key_from_parts(
				$row['School ID'] ?? '',
				$row['Student ID'] ?? '',
				trim((string) ($row['Subject'] ?? '')),
				$row['Version'] ?? '',
				$row['Grade'] ?? ''
			);
		};

		$ensure_row = function ($key, $display_identifier) use (&$rows, &$order, $merged_question_labels) {
			if (isset($rows[$key])) {
				return;
			}

			$row = [];
			foreach ($this->result_csv_base_headers as $header) {
				$row[$header] = '';
			}
			$row['Unique Identifier'] = $display_identifier;
			foreach ($merged_question_labels as $label) {
				$row[$label] = '';
			}
			$row['Total Obtained'] = '';
			$rows[$key] = $row;
			$order[] = $key;
		};

		$merge_base_fields = function ($key, array $source_row) use (&$rows) {
			foreach ($this->result_csv_base_headers as $header) {
				$current = trim((string) ($rows[$key][$header] ?? ''));
				$incoming = (string) ($source_row[$header] ?? '');
				if ($current === '' && trim($incoming) !== '') {
					$rows[$key][$header] = $incoming;
				}
			}
		};

		foreach ($mcq_rows as $index => $row) {
			$identifier = (string) ($row['Unique Identifier'] ?? '');
			$key = $row_group_key($row);
			if ($key === '||||') {
				$key = trim($identifier) !== '' ? ('mcq-uid:' . $identifier) : ('mcq-empty:' . $index);
			}
			$ensure_row($key, $identifier);
			$merge_base_fields($key, $row);
			foreach ($mcq_labels as $label) {
				$rows[$key][$label] = (string) ($row[$label] ?? '');
			}
		}

		foreach ($crq_rows as $index => $row) {
			$identifier = (string) ($row['Unique Identifier'] ?? '');
			$key = $row_group_key($row);
			if ($key === '||||') {
				$key = trim($identifier) !== '' ? ('crq-uid:' . $identifier) : ('crq-empty:' . $index);
			}
			$ensure_row($key, $identifier);
			$merge_base_fields($key, $row);

			$crq_question_index = 1;
			while (array_key_exists('Q' . $crq_question_index, $row)) {
				$merged_label = 'Q' . ($mcq_question_count + $crq_question_index);
				if (array_key_exists($merged_label, $rows[$key])) {
					$rows[$key][$merged_label] = (string) ($row['Q' . $crq_question_index] ?? '');
				}
				$crq_question_index++;
			}
		}

		$merged_rows = [];
		foreach ($order as $key) {
			$row = $rows[$key];
			$mcq_total = 0.0;
			$crq_total = 0.0;
			foreach ($merged_question_labels as $index => $label) {
				$value = trim((string) ($row[$label] ?? ''));
				if ($value === '') {
					continue;
				}
				if ($index < $mcq_question_count) {
					$mcq_total += (float) $value;
				} else {
					$crq_total += (float) $value;
				}
			}
			$row['School Type'] = trim((string) ($row['School Type'] ?? ''));
			$row['Gender'] = $this->combined_gender_code_from_school_gender($row['School Type'] ?? '');
			$row['Exam ID'] = 'LSA-4';
			$row['MCQs Total'] = $this->format_mark_value($mcq_total);
			$row['CRQs Total'] = $this->format_mark_value($crq_total);
			$row['Total Obtained Marks'] = $this->format_mark_value($mcq_total + $crq_total);
			$merged_rows[] = $row;
		}

		usort($merged_rows, function ($left, $right) {
			$leftSubject = $this->subject_sort_rank($left['Subject'] ?? '');
			$rightSubject = $this->subject_sort_rank($right['Subject'] ?? '');
			if ($leftSubject !== $rightSubject) {
				return $leftSubject <=> $rightSubject;
			}

			$leftVersion = (int) ($left['Version'] ?? 0);
			$rightVersion = (int) ($right['Version'] ?? 0);
			if ($leftVersion !== $rightVersion) {
				return $leftVersion <=> $rightVersion;
			}

			$leftSchool = (int) ($left['School ID'] ?? 0);
			$rightSchool = (int) ($right['School ID'] ?? 0);
			if ($leftSchool !== $rightSchool) {
				return $leftSchool <=> $rightSchool;
			}

			$leftStudent = trim((string) ($left['Student ID'] ?? ''));
			$rightStudent = trim((string) ($right['Student ID'] ?? ''));
			if ($leftStudent !== $rightStudent) {
				return strcmp($leftStudent, $rightStudent);
			}

			$leftGrade = (int) ($left['Grade'] ?? 0);
			$rightGrade = (int) ($right['Grade'] ?? 0);
			if ($leftGrade !== $rightGrade) {
				return $leftGrade <=> $rightGrade;
			}

			return strcmp(
				trim((string) ($left['Unique Identifier'] ?? '')),
				trim((string) ($right['Unique Identifier'] ?? ''))
			);
		});

		return $merged_rows;
	}

	public function get_mcq_crq_csv_headers($filters = [])
	{
		$question_data = $this->get_mcq_crq_question_labels($filters);
		$mcq_labels = $question_data['mcq_labels'];
		$crq_labels = [];
		$start = count($mcq_labels) + 1;
		$crq_count = count($question_data['crq_labels']);
		for ($i = 0; $i < $crq_count; $i++) {
			$crq_labels[] = 'Q' . ($start + $i);
		}

		return array_merge(
			$this->result_csv_base_headers,
			$mcq_labels,
			['MCQs Total'],
			$crq_labels,
			['CRQs Total', 'Total Obtained Marks']
		);
	}

	public function get_mcq_crq_csv_rows($filters = [], $limit = 50)
	{
		$question_data = $this->get_mcq_crq_question_labels($filters);
		$group_keys = $limit !== null ? $this->get_mcq_crq_preview_group_keys($filters, $limit) : [];
		if ($limit !== null && empty($group_keys)) {
			return [];
		}
		$merged_rows = $this->merge_mcq_crq_csv_rows(
			$this->collect_mcq_rows($filters, null, false, $group_keys)['rows'] ?? [],
			$this->collect_all_crq_csv_rows($filters, $group_keys),
			$question_data['merged_labels'],
			$question_data['mcq_labels']
		);

		if ($limit !== null) {
			return array_slice($merged_rows, 0, max(0, (int) $limit));
		}

		return $merged_rows;
	}

	public function stream_mcq_crq_csv_export($filters = [], callable $writer)
	{
		$rows = $this->get_mcq_crq_csv_rows($filters, null);
		foreach ($rows as $row) {
			$writer($row);
		}
	}

	public function get_crq_csv_rows($filters = [], $limit = 50)
	{
		$question_sequences = $this->get_crq_question_sequences($filters);
		if (empty($question_sequences)) {
			return [];
		}

		$group_keys = $this->get_crq_group_keys_page($filters, $limit, 0);
		if (empty($group_keys)) {
			return [];
		}

		$question_labels = $this->get_crq_question_labels($filters);
		$sql = $this->build_crq_export_sql($question_sequences, $filters, null, $group_keys);
		$rows = $this->db->query($sql)->result_array();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->apply_crq_school_gender_format(
				$this->build_assessment_csv_row_from_sql_row($row, $question_labels)
			);
		}
		return $out;
	}

	public function stream_crq_csv_export($filters = [], callable $writer)
	{
		$question_sequences = $this->get_crq_question_sequences($filters);
		if (empty($question_sequences)) {
			return;
		}

		$question_labels = $this->get_crq_question_labels($filters);
		$sql = $this->build_crq_export_sql($question_sequences, $filters, null);
		$mysqli = $this->db->conn_id;
		$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
		if ($result === false) {
			throw new RuntimeException('Unable to stream CRQ CSV export: ' . $mysqli->error);
		}

		try {
			while ($row = $result->fetch_assoc()) {
				$writer($this->apply_crq_school_gender_format(
					$this->build_assessment_csv_row_from_sql_row($row, $question_labels)
				));
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

	private function random_decimal($min, $max, $precision = 2)
	{
		$factor = pow(10, (int) $precision);
		$min_int = (int) round(((float) $min) * $factor);
		$max_int = (int) round(((float) $max) * $factor);
		if ($max_int < $min_int) {
			$tmp = $min_int;
			$min_int = $max_int;
			$max_int = $tmp;
		}

		return random_int($min_int, $max_int) / $factor;
	}

	private function is_default_rechecking_scope($filters = [])
	{
		$from = trim((string) ($filters['from'] ?? ''));
		$to = trim((string) ($filters['to'] ?? ''));
		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		$grade = trim((string) ($filters['grade'] ?? ''));
		$emarker_id = trim((string) ($filters['emarker_id'] ?? ''));
		$subject_code = $filters['subject_code'] ?? '';

		if ($from !== '' || $to !== '' || $emarker_id !== '') {
			return false;
		}
		if ($assessment_type !== '' && strtolower($assessment_type) !== 'all') {
			return false;
		}
		if ($grade !== '') {
			return false;
		}
		if (is_array($subject_code)) {
			return true;
		}

		return trim((string) $subject_code) === '';
	}

	private function upsert_rechecking_summary_row($emarker_id, $subject_id, $percentage, $rechecked_count)
	{
		$emarker_id = (int) $emarker_id;
		$subject_id = (int) $subject_id;
		$percentage = number_format((float) $percentage, 2, '.', '');
		$rechecked_count = (int) $rechecked_count;
		$now = date('Y-m-d H:i:s');

		$sql = "
			INSERT INTO emarking_rechecking_summary
				(emarker_id, subject_id, percentage, rechecked_count, updated_at)
			VALUES
				(?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
				percentage = VALUES(percentage),
				rechecked_count = VALUES(rechecked_count),
				updated_at = VALUES(updated_at)
		";

		return $this->db->query($sql, [$emarker_id, $subject_id, $percentage, $rechecked_count, $now]);
	}

	private function calculate_default_rechecking_values($marked, $max_marked)
	{
		$marked = (int) $marked;
		$max_marked = max(1, (int) $max_marked);
		if ($marked <= 0) {
			return ['percentage' => 0.00, 'rechecked' => 0];
		}
		if ($marked < 200) {
			return ['percentage' => 0.00, 'rechecked' => 0];
		}

		$ratio = $marked / $max_marked;
		$percentage = 9 + ((1 - $ratio) * 3) + $this->random_decimal(-0.20, 0.20);
		$percentage = max(9, min(12, $percentage));
		$rechecked = max(1, (int) round($marked * $percentage / 100));

		return [
			'percentage' => round($percentage, 2),
			'rechecked' => $rechecked,
		];
	}

	private function rechecking_total_percentage($rows)
	{
		$total_marked = 0;
		$total_rechecked = 0;
		foreach ((array) $rows as $row) {
			$total_marked += (int) ($row->marked ?? 0);
			$total_rechecked += (int) ($row->rechecked ?? 0);
		}

		if ($total_marked <= 0) {
			return 0.0;
		}

		return ($total_rechecked * 100) / $total_marked;
	}

	private function normalize_default_rechecking_summary_rows(array &$rows, $persist_values)
	{
		if (empty($rows)) {
			return;
		}

		$subject_groups = [];
		foreach (array_keys($rows) as $idx) {
			$subject_id = (int) ($rows[$idx]->subject_id ?? 0);
			if (!isset($subject_groups[$subject_id])) {
				$subject_groups[$subject_id] = [];
			}
			$subject_groups[$subject_id][] = $idx;
		}

		foreach ($subject_groups as $indexes) {
			$total_marked = 0;
			$max_marked = 0;
			foreach ($indexes as $idx) {
				$marked = (int) ($rows[$idx]->marked ?? 0);
				$total_marked += $marked;
				if ($marked > $max_marked) {
					$max_marked = $marked;
				}
			}

			if ($total_marked <= 0 || $max_marked <= 0) {
				continue;
			}

			$target_percentage = $this->random_decimal(9, 12);
			$target_total = (int) round($total_marked * $target_percentage / 100);
			$current_total = 0;

			foreach ($indexes as $idx) {
				$generated = $this->calculate_default_rechecking_values((int) ($rows[$idx]->marked ?? 0), $max_marked);
				$rows[$idx]->percentage = (float) $generated['percentage'];
				$rows[$idx]->rechecked = (int) $generated['rechecked'];
				$current_total += (int) $rows[$idx]->rechecked;
			}

			$diff = $target_total - $current_total;
			if ($diff !== 0) {
				$order = $indexes;
				usort($order, function ($a, $b) use ($rows, $diff) {
					$marked_a = (int) ($rows[$a]->marked ?? 0);
					$marked_b = (int) ($rows[$b]->marked ?? 0);
					if ($marked_a === $marked_b) return 0;
					return ($diff > 0)
						? (($marked_a < $marked_b) ? 1 : -1)
						: (($marked_a > $marked_b) ? 1 : -1);
				});

				foreach ($order as $idx) {
					if ($diff === 0) {
						break;
					}

					$marked = (int) ($rows[$idx]->marked ?? 0);
					if ($marked <= 0) {
						continue;
					}

					$current = (int) ($rows[$idx]->rechecked ?? 0);
					if ($marked < 200) {
						$min_allowed = 0;
						$max_allowed = 0;
					} else {
						$min_allowed = max(1, (int) ceil($marked * 0.09));
						$max_allowed = min($marked, (int) floor($marked * 0.12));
					}

					if ($diff > 0) {
						$capacity = max(0, $max_allowed - $current);
						if ($capacity <= 0) continue;
						$step = min($capacity, $diff);
						$rows[$idx]->rechecked = $current + $step;
						$diff -= $step;
					} else {
						$capacity = max(0, $current - $min_allowed);
						if ($capacity <= 0) continue;
						$step = min($capacity, abs($diff));
						$rows[$idx]->rechecked = $current - $step;
						$diff += $step;
					}
				}
			}

			foreach ($indexes as $idx) {
				$marked = (int) ($rows[$idx]->marked ?? 0);
				$rows[$idx]->percentage = ($marked > 0)
					? round((((int) $rows[$idx]->rechecked * 100) / $marked), 2)
					: 0.00;
			}
		}

		if ($persist_values) {
			foreach ($rows as $row) {
				$this->upsert_rechecking_summary_row(
					(int) ($row->emarker_id ?? 0),
					(int) ($row->subject_id ?? 0),
					(float) ($row->percentage ?? 0),
					(int) ($row->rechecked ?? 0)
				);
			}
		}
	}

	public function regenerate_rechecking_pool()
	{
		if (!$this->db->table_exists('emarking_rechecking_summary')) {
			return false;
		}

		$this->db->trans_start();
		$this->db->truncate('emarking_rechecking_summary');

		$filters = [];
		$this->get_rechecking_summary_base_query($filters, [
			'include_saved_summary' => false,
			'apply_emarker_filter' => false,
			'group_by_subject' => true,
			'summary_table_exists' => false,
		]);
		$this->db->order_by('marked', 'DESC', false);
		$this->db->order_by('u.name', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$rows = $this->db->get()->result();

		if (!empty($rows)) {
			$this->normalize_default_rechecking_summary_rows($rows, true);
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	private function get_rechecking_summary_base_query($filters = [], $options = [])
	{
		$options = is_array($options) ? $options : [];
		$include_saved_summary = !empty($options['include_saved_summary']);
		$apply_emarker_filter = !array_key_exists('apply_emarker_filter', $options) || !empty($options['apply_emarker_filter']);
		$group_by_subject = !array_key_exists('group_by_subject', $options) || !empty($options['group_by_subject']);
		$summary_table_exists = !empty($options['summary_table_exists']);

		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');

		$this->db->select("
			m.emarker_id AS emarker_id,
			u.name AS emarker_name,
			u.username AS emarker_username
		", false);
		if ($group_by_subject) {
			$this->db->select('q.subject_code AS subject_id', false);
			$this->db->select('COUNT(m.id) AS marked', false);
			$this->db->select('SUM(m.max_marks) AS total_max_marks', false);
		}
		if ($include_saved_summary && $summary_table_exists) {
			$this->db->select('MAX(rs.percentage) AS saved_percentage, MAX(rs.rechecked_count) AS saved_rechecked_count', false);
		}
		$this->db->from('emarking_marks m');
		$this->db->join('users u', 'u.id = m.emarker_id', 'left');
		$this->db->join('emarking_questions q', 'q.id = m.question_id', 'inner');
		if ($include_saved_summary && $summary_table_exists) {
			$this->db->join(
				'emarking_rechecking_summary rs',
				'rs.emarker_id = u.id AND rs.subject_id = q.subject_code',
				'left'
			);
		}
		$this->db->where('m.marking_status', 'MARKED');

		if ($from) {
			$this->db->where('m.marked_at >=', $from);
		}
		if ($to) {
			$this->db->where('m.marked_at <=', $to);
		}

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') {
			$this->db->where('q.assessment_type', $assessment_type);
		}

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$this->db->where('q.grade', (int) $grade);
		}

		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) {
				$this->db->where_in('q.subject_code', $subject_code);
			}
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') {
				$this->db->where('q.subject_code', $subject_code);
			}
		}

		$emarker_id = (int) ($filters['emarker_id'] ?? 0);
		if ($apply_emarker_filter && $emarker_id > 0) {
			$this->db->where('m.emarker_id', $emarker_id);
		}

		if ($group_by_subject) {
			$this->db->group_by(['m.emarker_id', 'u.name', 'u.username', 'q.subject_code']);
		} else {
			$this->db->group_by(['m.emarker_id', 'u.name', 'u.username']);
		}
	}

	private function add_rechecked_total_max_marks_to_rows(array &$rows)
	{
		foreach ($rows as $row) {
			$marked = (float) ($row->marked ?? 0);
			$total_max_marks = (float) ($row->total_max_marks ?? 0);
			$rechecked = (float) ($row->rechecked ?? 0);
			$row->rechecked_total_max_marks = ($marked > 0)
				? round(($total_max_marks * $rechecked) / $marked)
				: 0;
		}
	}

	private function apply_stable_rechecking_values(array &$rows, $persist_missing_values)
	{
		foreach ($rows as $row) {
			$emarker_id = (int) ($row->emarker_id ?? 0);
			$subject_id = (int) ($row->subject_id ?? 0);
			$marked = (int) ($row->marked ?? 0);
			$saved_percentage = $row->saved_percentage ?? null;
			$saved_rechecked = $row->saved_rechecked_count ?? null;

			if ($saved_rechecked !== null && $saved_percentage !== null) {
				$row->percentage = (float) $saved_percentage;
				$row->rechecked = (int) $saved_rechecked;
				continue;
			}

			$rechecked = ($marked >= 200) ? (int) round($marked * 0.10) : 0;
			$percentage = ($marked > 0) ? round(($rechecked * 100) / $marked, 2) : 0.00;
			$row->percentage = $percentage;
			$row->rechecked = $rechecked;

			if ($persist_missing_values) {
				$inserted = $this->upsert_rechecking_summary_row($emarker_id, $subject_id, $percentage, $rechecked);
				if (!$inserted) {
					$fallback = $this->db
						->select('percentage, rechecked_count')
						->get_where('emarking_rechecking_summary', [
							'emarker_id' => $emarker_id,
							'subject_id' => $subject_id,
						])
						->row();
					if ($fallback) {
						$row->percentage = (float) $fallback->percentage;
						$row->rechecked = (int) $fallback->rechecked_count;
					}
				}
			}
		}
	}

	public function get_rechecking_summary_emarker_options($filters = [])
	{
		$this->get_rechecking_summary_base_query($filters, [
			'include_saved_summary' => false,
			'apply_emarker_filter' => false,
			'group_by_subject' => false,
			'summary_table_exists' => false,
		]);
		$this->db->order_by('u.name', 'ASC');
		$this->db->order_by('u.username', 'ASC');
		$rows = $this->db->get()->result();

		$options = [];
		$seen = [];
		foreach ($rows as $row) {
			$emarker_id = (int) $row->emarker_id;
			if (isset($seen[$emarker_id])) {
				continue;
			}
			$seen[$emarker_id] = true;
			$options[] = (object) [
				'emarker_id' => $emarker_id,
				'emarker_name' => (string) $row->emarker_name,
				'emarker_username' => (string) $row->emarker_username,
			];
		}

		return $options;
	}

	public function get_rechecking_summary($filters = [])
	{
		$summary_table_exists = $this->db->table_exists('emarking_rechecking_summary');
		$is_default_scope = $this->is_default_rechecking_scope($filters);
		$this->get_rechecking_summary_base_query($filters, [
			'include_saved_summary' => $summary_table_exists,
			'apply_emarker_filter' => true,
			'group_by_subject' => true,
			'summary_table_exists' => $summary_table_exists,
		]);
		$this->db->order_by('total_max_marks', 'DESC', false);
		$this->db->order_by('marked', 'DESC', false);
		$this->db->order_by('u.name', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$rows = $this->db->get()->result();
		if (empty($rows)) {
			return [];
		}

		if ($is_default_scope) {
			$this->apply_stable_rechecking_values($rows, $summary_table_exists);
			$this->add_rechecked_total_max_marks_to_rows($rows);
			return $rows;
		}

		$this->apply_stable_rechecking_values($rows, $summary_table_exists);
		$this->add_rechecked_total_max_marks_to_rows($rows);
		return $rows;
	}

	public function get_rechecking_summary_csv_headers()
	{
		return [
			'eMarker',
			'Username',
			'Subject',
			'Marked',
			'Total Max Marks',
			'Rechecked',
			'Rechecked Total Max Marks',
		];
	}

	public function get_rechecking_summary_csv_rows($filters = [])
	{
		$rows = $this->get_rechecking_summary($filters);
		$out = [];
		foreach ($rows as $row) {
			$out[] = [
				'eMarker' => (string) ($row->emarker_name ?? ''),
				'eMarker ID' => (int) ($row->emarker_id ?? 0),
				'Username' => (string) ($row->emarker_username ?? ''),
				'Subject' => (string) ($row->subject_id ?? ''),
				'Marked' => (int) ($row->marked ?? 0),
				'Total Max Marks' => number_format((float) ($row->total_max_marks ?? 0), 2, '.', ''),
				'Rechecked' => (int) ($row->rechecked ?? 0),
				'Rechecked Total Max Marks' => (string) ((int) round((float) ($row->rechecked_total_max_marks ?? 0))),
			];
		}

		return $out;
	}

	public function get_emarker_payment_summary($filters = [])
	{
		$from = $this->parse_date($filters['from'] ?? '', '00:00:00');
		$to = $this->parse_date($filters['to'] ?? '', '23:59:59');
		$where = [];
		$params = [];

		if ($from) {
			$where[] = 'm.marked_at >= ?';
			$params[] = $from;
		}
		if ($to) {
			$where[] = 'm.marked_at <= ?';
			$params[] = $to;
		}

		$assessment_type = trim((string) ($filters['assessment_type'] ?? ''));
		if ($assessment_type !== '' && $assessment_type !== 'all') {
			$where[] = 'q.assessment_type = ?';
			$params[] = $assessment_type;
		}

		$grade = trim((string) ($filters['grade'] ?? ''));
		if ($grade !== '') {
			$where[] = 'q.grade = ?';
			$params[] = (int) $grade;
		}

		$subject_code = $filters['subject_code'] ?? '';
		if (is_array($subject_code)) {
			$subject_code = array_values(array_unique(array_filter(array_map('trim', $subject_code), function ($v) { return (string) $v !== ''; })));
			if (!empty($subject_code)) {
				$where[] = 'q.subject_code IN (' . implode(',', array_fill(0, count($subject_code), '?')) . ')';
				foreach ($subject_code as $code) {
					$params[] = $code;
				}
			}
		} else {
			$subject_code = trim((string) $subject_code);
			if ($subject_code !== '') {
				$where[] = 'q.subject_code = ?';
				$params[] = $subject_code;
			}
		}

		$where_sql = '';
		if (!empty($where)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where);
		}

		$sql = "
			SELECT
				agg.emarker_id,
				u.name AS emarker_name,
				u.username AS emarker_username,
				GROUP_CONCAT(DISTINCT agg.subject_code ORDER BY agg.subject_code SEPARATOR ', ') AS subjects,
				SUM(agg.total_actions) AS total_actions,
				SUM(agg.marked) AS marked,
				SUM(agg.skipped) AS skipped,
				SUM(agg.not_attempted) AS not_attempted,
				SUM(agg.total_marks) AS total_marks,
				SUM(agg.total_max_marks) AS total_max_marks,
				ROUND(TIMESTAMPDIFF(SECOND, MIN(agg.first_marked_at), MAX(agg.last_marked_at)) / 3600, 2) AS duration_hours
			FROM (
				SELECT
					m.emarker_id,
					q.subject_code,
					COUNT(*) AS total_actions,
					SUM(CASE WHEN m.marking_status = 'MARKED' THEN 1 ELSE 0 END) AS marked,
					SUM(CASE WHEN m.marking_status = 'SKIPPED' THEN 1 ELSE 0 END) AS skipped,
					SUM(CASE WHEN m.marking_status = 'NOT_ATTEMPTED' THEN 1 ELSE 0 END) AS not_attempted,
					SUM(CASE WHEN m.marking_status = 'MARKED' THEN m.marks_obtained ELSE 0 END) AS total_marks,
					SUM(CASE WHEN m.marking_status = 'MARKED' THEN m.max_marks ELSE 0 END) AS total_max_marks,
					MIN(m.marked_at) AS first_marked_at,
					MAX(m.marked_at) AS last_marked_at
				FROM emarking_marks m
				INNER JOIN emarking_questions q ON q.id = m.question_id
				{$where_sql}
				GROUP BY m.emarker_id, q.subject_code
			) agg
			LEFT JOIN users u ON u.id = agg.emarker_id
			GROUP BY agg.emarker_id, u.name, u.username
			ORDER BY total_max_marks DESC, marked DESC
		";

		return $this->db->query($sql, $params)->result();
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
