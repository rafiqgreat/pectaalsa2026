<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emarking_model extends CI_Model
{
	private function ensure_upload_dir($relativeDir)
	{
		$relativeDir = trim(str_replace(['\\', '..'], ['/', ''], (string) $relativeDir), '/');
		if ($relativeDir === '') return null;
		$abs = rtrim(FCPATH, '\\/') . '/' . $relativeDir;
		if (!is_dir($abs)) {
			@mkdir($abs, 0775, true);
		}
		return $abs;
	}

	private function handle_upload($field, $relativeDir)
	{
		if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) return null;

		$absDir = $this->ensure_upload_dir($relativeDir);
		if (!$absDir) return null;

		$config = [
			'upload_path' => $absDir,
			'allowed_types' => '*',
			'encrypt_name' => true,
			'max_size' => 0,
		];

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		if (!$this->upload->do_upload($field)) {
			return null;
		}

		$data = $this->upload->data();
		$rel = rtrim($relativeDir, '/') . '/' . $data['file_name'];
		return str_replace(['\\', '//'], ['/', '/'], $rel);
	}

	public function build_question_payload_from_post($createdBy)
	{
		$assessment_type = trim((string) $this->input->post('assessment_type', true));
		$subject_code = trim((string) $this->input->post('subject_code', true));
		$source_table = trim((string) $this->input->post('source_table', true));
		if ($source_table === '') {
			$source_table = $this->get_source_table($assessment_type, $subject_code);
		}

		$payload = [
			'assessment_type' => $assessment_type,
			'source_table' => $source_table ?: null,
			'grade' => (int) $this->input->post('grade', true),
			'subject_code' => $subject_code,
			'version' => (int) $this->input->post('version', true),
			'page_no' => trim((string) $this->input->post('page_no', true)),
			'question_no' => trim((string) $this->input->post('question_no', true)),
			'question_title' => trim((string) $this->input->post('question_title', true)),
			'question_type' => trim((string) $this->input->post('question_type', true)),
			'max_marks' => (float) $this->input->post('max_marks', true),
			'rubric_title' => trim((string) $this->input->post('rubric_title', true)),
			'rubric_detail' => $this->input->post('rubric_detail'),
			'sample_answer' => $this->input->post('sample_answer'),
			'guide_text' => $this->input->post('guide_text'),
			'status' => (int) $this->input->post('status', true) ? 1 : 0,
		];

		$uploadDir = 'uploads/emarking/questions';
		$sampleAnswerFile = $this->handle_upload('sample_answer_file', $uploadDir);
		$guideFile = $this->handle_upload('guide_file', $uploadDir);
		$paperFile = $this->handle_upload('question_paper_file', $uploadDir);
		if ($sampleAnswerFile) $payload['sample_answer_file'] = $sampleAnswerFile;
		if ($guideFile) $payload['guide_file'] = $guideFile;
		if ($paperFile) $payload['question_paper_file'] = $paperFile;

		if ((int) $createdBy > 0) $payload['created_by'] = (int) $createdBy;
		return $payload;
	}

	public function get_questions($filters = [])
	{
		$this->db->select('q.*,
			(SELECT COUNT(*) FROM emarking_question_rubric_steps rs WHERE rs.question_id = q.id) AS rubric_steps_count
		', false);
		$this->db->from('emarking_questions q');

		$assessment_type = isset($filters['assessment_type']) ? trim((string) $filters['assessment_type']) : '';
		if ($assessment_type !== '') $this->db->where('q.assessment_type', $assessment_type);

		$grade = isset($filters['grade']) ? trim((string) $filters['grade']) : '';
		if ($grade !== '') $this->db->where('q.grade', (int) $grade);

		$subject_code = isset($filters['subject_code']) ? trim((string) $filters['subject_code']) : '';
		if ($subject_code !== '') $this->db->where('q.subject_code', $subject_code);

		$version = isset($filters['version']) ? trim((string) $filters['version']) : '';
		if ($version !== '') $this->db->where('q.version', (int) $version);

		$page_no = isset($filters['page_no']) ? trim((string) $filters['page_no']) : '';
		if ($page_no !== '') $this->db->where('q.page_no', $page_no);

		$status = isset($filters['status']) ? trim((string) $filters['status']) : '';
		if ($status !== '' && $status !== 'all') $this->db->where('q.status', (int) $status);

		$q = isset($filters['q']) ? trim((string) $filters['q']) : '';
		if ($q !== '') {
			$this->db->group_start()
				->like('q.question_title', $q)
				->or_like('q.question_no', $q)
				->group_end();
		}

		$this->db->order_by('q.assessment_type', 'ASC');
		$this->db->order_by('q.grade', 'ASC');
		$this->db->order_by('q.subject_code', 'ASC');
		$this->db->order_by('q.version', 'ASC');
		$this->db->order_by('q.page_no', 'ASC');
		$this->db->order_by('q.question_no', 'ASC');

		return $this->db->get()->result();
	}

	public function get_question($id)
	{
		return $this->db->get_where('emarking_questions', ['id' => (int) $id])->row();
	}

	public function save_question($data, $id = null)
	{
		if (empty($data['assessment_type']) || empty($data['grade']) || empty($data['subject_code']) || empty($data['page_no']) || empty($data['question_no'])) {
			return false;
		}

		if ($id === null) {
			$this->db->insert('emarking_questions', $data);
			$err = $this->db->error();
			if (!empty($err['code'])) return false;
			return (int) $this->db->insert_id();
		}

		$this->db->where('id', (int) $id)->update('emarking_questions', $data);
		$err = $this->db->error();
		if (!empty($err['code'])) return false;
		return true;
	}

	public function get_rubric_steps($question_id)
	{
		return $this->db->from('emarking_question_rubric_steps')
			->where('question_id', (int) $question_id)
			->order_by('step_order', 'ASC')
			->order_by('id', 'ASC')
			->get()
			->result();
	}

	public function get_rubric_step($id)
	{
		return $this->db->get_where('emarking_question_rubric_steps', ['id' => (int) $id])->row();
	}

	public function save_rubric_step($data, $id = null)
	{
		if (empty($data['question_id']) || empty($data['step_title'])) return false;
		if (empty($data['marking_type']) || !in_array($data['marking_type'], ['ZERO_ONE', 'RANGE', 'FIXED'], true)) {
			$data['marking_type'] = 'ZERO_ONE';
		}
		if ((int) ($data['step_order'] ?? 0) <= 0) $data['step_order'] = 1;

		if ($id === null) {
			$this->db->insert('emarking_question_rubric_steps', $data);
			$err = $this->db->error();
			return empty($err['code']);
		}

		$this->db->where('id', (int) $id)->update('emarking_question_rubric_steps', $data);
		$err = $this->db->error();
		return empty($err['code']);
	}

	public function delete_rubric_step($id)
	{
		$this->db->delete('emarking_question_rubric_steps', ['id' => (int) $id]);
	}

	// Required mapper
	private function get_source_table($assessment_type, $subject_code)
	{
		$assessment_type = strtoupper(trim((string) $assessment_type));
		$subject_code = (int) $subject_code;

		if ($assessment_type == 'CRQ') {
			if ($subject_code == 1) return 'digital_papers_booklets1';
			if ($subject_code == 2) return 'digital_papers_booklets2';
			if ($subject_code == 3) return 'digital_papers_booklets3';
			if ($subject_code == 4) return 'digital_papers_booklets4';
		}

		if ($assessment_type == 'DICTATION') {
			if ($subject_code == 1) return 'digital_papers_dictation1';
			if ($subject_code == 2) return 'digital_papers_dictation2';
		}

		return false;
	}

	private function resolve_source_columns($table)
	{
		$candidates = [
			// Primary key (varies by table)
			'id' => ['id', 'paper_id'],
			'barcode' => ['paper_barcode', 'barcode', 'paperbarcode', 'bar_code', 'paper_bar_code'],
			'roll_no' => ['paper_sr_roll', 'roll_no', 'rollno', 'rollnumber', 'roll_number'],
			'grade' => ['paper_grade', 'grade', 'class', 'class_id'],
			'version' => ['paper_version', 'version'],
			'subject_code' => ['paper_subject_code', 'subject_code', 'subject'],
			'page_no' => ['paper_page_no', 'page_no', 'pageno', 'page'],
			'school_id' => ['paper_school_id', 'school_id', 'institute_id', 'schoolid'],
			'lsacode' => ['paper_lsacode', 'lsacode', 'lsa_code', 'lscode', 'l_s_a_code'],
			'paper_type_code' => ['paper_type_code', 'paper_type', 'paperTypeCode'],
			'paper_generated' => ['paper_generated', 'is_generated', 'generated'],
		];

		$out = [];
		foreach ($candidates as $key => $cols) {
			$out[$key] = null;
			foreach ($cols as $col) {
				if ($this->db->field_exists($col, $table)) {
					$out[$key] = $col;
					break;
				}
			}
		}
		return $out;
	}

	/**
	 * Source tables store paper_page_no as 2 digits (00-99), while folder structure may use 3 digits
	 * with a fixed trailing "1" (e.g. 01 -> 011, 10 -> 101, 13 -> 131).
	 */
	private function folder_page_no_to_source_page_no($folder_page_no)
	{
		$folder_page_no = trim((string) $folder_page_no);
		if ($folder_page_no === '') return $folder_page_no;

		// If folder page is 3 digits and ends with "1", drop the trailing digit (101->10, 011->01).
		if (ctype_digit($folder_page_no) && strlen($folder_page_no) === 3 && substr($folder_page_no, -1) === '1') {
			return substr($folder_page_no, 0, 2);
		}

		// Backward-compatible fallback for older 3+ digit schemes (e.g. 101->01 etc).
		if (ctype_digit($folder_page_no) && (int) $folder_page_no > 99) {
			return str_pad((string) (((int) $folder_page_no) % 100), 2, '0', STR_PAD_LEFT);
		}

		return $folder_page_no;
	}

	private function source_page_no_to_folder_page_no($source_page_no)
	{
		$source_page_no = trim((string) $source_page_no);
		if ($source_page_no === '') return $source_page_no;

		// If already 3 digits, keep as-is.
		if (ctype_digit($source_page_no) && strlen($source_page_no) === 3) return $source_page_no;

		// If 1-2 digits, pad to 2 digits and append fixed trailing "1".
		if (ctype_digit($source_page_no) && strlen($source_page_no) <= 2) {
			return str_pad($source_page_no, 2, '0', STR_PAD_LEFT) . '1';
		}

		return $source_page_no;
	}

	private function build_image_path($assessment_type, $grade, $subject_code, $version, $page_no, $question_no, $barcode)
	{
		$assessment_type = strtoupper((string) $assessment_type);
		$baseDir = $assessment_type === 'DICTATION' ? 'storagebox/dictations' : 'storagebox/crqs';

		$grade = (string) (int) $grade;
		$subject_code = (string) $subject_code;
		$version = (string) (int) $version;
		$page_no = $this->source_page_no_to_folder_page_no($page_no);
		$question_no = trim((string) $question_no);
		$barcode = trim((string) $barcode);

		$parts = [$baseDir, $grade, $subject_code, $version, $page_no, $question_no, $barcode . '_1.jpg'];
		$path = implode('/', array_map(function ($p) {
			$p = str_replace(['\\', '..'], ['/', ''], (string) $p);
			return trim($p, '/');
		}, $parts));
		return $path;
	}

	public function import_images_for_question($question_id, $expected_assessment_type, $upload_batch_no, $limit = 1000, $check_file_exists = true)
	{
		$question = $this->get_question((int) $question_id);
		if (!$question) return ['inserted' => 0, 'skipped' => 0, 'error' => 'Question not found'];

		$expected_assessment_type = strtoupper(trim((string) $expected_assessment_type));
		if (strtoupper((string) $question->assessment_type) !== $expected_assessment_type) {
			return ['inserted' => 0, 'skipped' => 0, 'error' => 'Assessment type mismatch'];
		}

		$source_table = !empty($question->source_table) ? (string) $question->source_table : $this->get_source_table($question->assessment_type, $question->subject_code);
		if (!$source_table) return ['inserted' => 0, 'skipped' => 0, 'error' => 'Source table not configured'];

		$cols = $this->resolve_source_columns($source_table);
		if (empty($cols['barcode']) || empty($cols['paper_generated'])) {
			return ['inserted' => 0, 'skipped' => 0, 'error' => 'Source table missing required columns'];
		}

		$paper_type_code = null;
		if (strtoupper((string) $question->assessment_type) === 'CRQ') {
			$paper_type_code = 1;
		} elseif (strtoupper((string) $question->assessment_type) === 'DICTATION') {
			$paper_type_code = ((string) $question->subject_code === '2') ? 13 : 12;
		}

		$this->db->from($source_table);
		$this->db->where($cols['paper_generated'], 1);
		if (!empty($cols['paper_type_code']) && $paper_type_code !== null) {
			$this->db->where($cols['paper_type_code'], (int) $paper_type_code);
		}
		if (!empty($cols['subject_code'])) {
			$this->db->where($cols['subject_code'], (int) $question->subject_code);
		}
		if (!empty($cols['grade'])) {
			$this->db->where($cols['grade'], (int) $question->grade);
		}
		if (!empty($cols['version'])) {
			$this->db->where($cols['version'], (int) $question->version);
		}
		if (!empty($cols['page_no'])) {
			$this->db->where($cols['page_no'], (string) $question->page_no);
		}

		$this->db->order_by('id', 'ASC');
		$this->db->limit((int) $limit);
		$rows = $this->db->get()->result_array();

		$inserted = 0;
		$skipped = 0;

		$now = date('Y-m-d H:i:s');
		foreach ($rows as $r) {
			$barcode = trim((string) ($r[$cols['barcode']] ?? ''));
			if ($barcode === '') {
				$skipped++;
				continue;
			}

			$image_path = $this->build_image_path($question->assessment_type, $question->grade, $question->subject_code, $question->version, $question->page_no, $question->question_no, $barcode);
			if ($check_file_exists) {
				$abs = rtrim(FCPATH, '\\/') . '/' . $image_path;
				if (!is_file($abs)) {
					$skipped++;
					continue;
				}
			}

			$payload = [
				'assessment_type' => $question->assessment_type,
				'source_table' => $source_table,
				'source_paper_id' => (int) ($r['id'] ?? 0),
				'paper_barcode' => $barcode,
				'grade' => (int) $question->grade,
				'school_id' => !empty($cols['school_id']) ? (int) ($r[$cols['school_id']] ?? null) : null,
				'lsacode' => !empty($cols['lsacode']) ? (string) ($r[$cols['lsacode']] ?? null) : null,
				'subject_code' => (string) $question->subject_code,
				'version' => (int) $question->version,
				'roll_no' => !empty($cols['roll_no']) ? (string) ($r[$cols['roll_no']] ?? '') : '',
				'page_no' => (string) $question->page_no,
				'question_id' => (int) $question->id,
				'question_no' => (string) $question->question_no,
				'image_path' => $image_path,
				'upload_batch_no' => (string) $upload_batch_no,
				'status' => 'UPLOADED',
				'created_at' => $now,
			];

			$this->db->insert('emarking_question_images', $payload);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				// Typically duplicate key: uq_question_image
				$skipped++;
				continue;
			}
			$inserted++;
		}

		return ['inserted' => $inserted, 'skipped' => $skipped];
	}

	// Required folder import
	public function import_images_from_folder($base_folder, $assessment_type, $upload_batch_no)
	{
		$assessment_type = strtoupper(trim((string) $assessment_type));
		if (!in_array($assessment_type, ['CRQ', 'DICTATION'], true)) {
			return ['inserted' => 0, 'skipped' => 0, 'errors' => [['reason' => 'Invalid assessment_type']]];
		}

		$base_folder = str_replace('\\', '/', trim((string) $base_folder));
		$base_folder = rtrim($base_folder, '/');
		if ($base_folder === '') {
			return ['inserted' => 0, 'skipped' => 0, 'errors' => [['reason' => 'Invalid base_folder']]];
		}

		$abs_base = $base_folder;
		if (strpos($abs_base, ':') === false && strpos($abs_base, '/') !== 0) {
			$abs_base = rtrim(FCPATH, '\\/') . '/' . ltrim($base_folder, '/');
		}
		$abs_base = str_replace(['\\', '//'], ['/', '/'], $abs_base);

		if (!is_dir($abs_base)) {
			return ['inserted' => 0, 'skipped' => 0, 'errors' => [['reason' => 'Base folder not found', 'base_folder' => $abs_base]]];
		}

		$inserted = 0;
		$skipped = 0;
		$errors = [];
		$now = date('Y-m-d H:i:s');

		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($abs_base, FilesystemIterator::SKIP_DOTS)
		);

		foreach ($it as $fileInfo) {
			/** @var SplFileInfo $fileInfo */
			if (!$fileInfo->isFile()) continue;

			$ext = strtolower((string) $fileInfo->getExtension());
			if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) continue;

			$abs_path = str_replace('\\', '/', $fileInfo->getPathname());
			$rel_to_base = ltrim(substr($abs_path, strlen($abs_base)), '/');
			$rel_parts = explode('/', $rel_to_base);

			// Expect: grade/subject_code/version/page_no/question_no/filename
			if (count($rel_parts) < 6) {
				$skipped++;
				$errors[] = ['file' => $abs_path, 'reason' => 'Path structure invalid'];
				continue;
			}

			$grade = (int) $rel_parts[0];
			$subject_code = (int) $rel_parts[1];
			$version = (int) $rel_parts[2];
			$page_no = (string) $rel_parts[3];
			$question_no = (string) $rel_parts[4];
			$filename = (string) $rel_parts[count($rel_parts) - 1];

			if ($grade <= 0 || $subject_code <= 0 || $version <= 0 || trim($page_no) === '' || trim($question_no) === '') {
				$skipped++;
				$errors[] = ['file' => $abs_path, 'reason' => 'Invalid folder values'];
				continue;
			}

			$barcode = $filename;
			$underscorePos = strpos($barcode, '_');
			if ($underscorePos !== false) $barcode = substr($barcode, 0, $underscorePos);
			$barcode = trim((string) $barcode);
			if ($barcode === '') {
				$skipped++;
				$errors[] = ['file' => $abs_path, 'reason' => 'Barcode missing in filename'];
				continue;
			}

			$source_table = $this->get_source_table($assessment_type, $subject_code);
			if (!$source_table) {
				$skipped++;
				$errors[] = ['file' => $abs_path, 'reason' => 'No source table mapping', 'subject_code' => $subject_code];
				continue;
			}

			$cols = $this->resolve_source_columns($source_table);
			// If source table structure doesn't match expectations, still allow import using source_paper_id=0.
			// This supports cases where cropped images exist but source tables are incomplete/out-of-sync.
			$can_validate_source = !empty($cols['barcode']) && !empty($cols['paper_generated']);

			$paper_type_code = null;
			if ($assessment_type === 'CRQ') {
				$paper_type_code = 1;
			} else {
				$paper_type_code = ($subject_code === 2) ? 13 : 12;
			}

			// Try to validate/attach source row if possible; otherwise import with source_paper_id=0.
			$source_row = null;
			if ($can_validate_source) {
				$getSourceRow = function ($pageNoOverride = null, $includeType = true, $includeGenerated = true, $includeMeta = false) use ($source_table, $cols, $barcode, $paper_type_code, $grade, $subject_code, $version, $page_no) {
					$this->db->from($source_table);
					$this->db->where($cols['barcode'], $barcode);

					// Keep constraints minimal (barcode is typically unique). Add others only when useful.
					if ($includeGenerated) $this->db->where($cols['paper_generated'], 1);
					if ($includeType && !empty($cols['paper_type_code']) && $paper_type_code !== null) {
						$this->db->where($cols['paper_type_code'], (int) $paper_type_code);
					}

					// Optional additional constraints (can be unreliable due to formatting / NULLs).
					if ($includeMeta) {
						if (!empty($cols['grade'])) $this->db->where($cols['grade'], (int) $grade);
						if (!empty($cols['subject_code'])) $this->db->where($cols['subject_code'], (int) $subject_code);
						if (!empty($cols['version'])) $this->db->where($cols['version'], (int) $version);
						if (!empty($cols['page_no'])) {
							$pn = (string) ($pageNoOverride ?? $page_no);
							$pn = $this->folder_page_no_to_source_page_no($pn);
							$this->db->where($cols['page_no'], $pn);
						}
					}

					$this->db->limit(1);
					return $this->db->get()->row_array();
				};

				// Attempt 1: minimal match (barcode + generated + type).
				$source_row = $getSourceRow(null, true, true, false);

				// Attempt 2: use meta constraints with normalized page_no (folder 101 -> source 10, 011 -> 01).
				if (empty($source_row) && !empty($cols['page_no'])) {
					$pn = $this->folder_page_no_to_source_page_no($page_no);
					$source_row = $getSourceRow($pn, true, true, true);
				}

				// Attempt 3: relax paper_type_code constraint (some sources use different codes).
				if (empty($source_row)) {
					$source_row = $getSourceRow(null, false, true, false);
				}

				// Attempt 4: relax generated constraint as well (still links by barcode).
				if (empty($source_row)) {
					$source_row = $getSourceRow(null, false, false, false);
				}
			} else {
				$errors[] = ['file' => $abs_path, 'reason' => 'Source validation skipped (missing source columns)', 'source_table' => $source_table];
			}

			if (empty($source_row)) {
				// Do not skip: allow import so batches/marking can proceed, but record why source attach failed.
				$errors[] = ['file' => $abs_path, 'reason' => 'Source record not found/eligible (imported with source_paper_id=0)', 'source_table' => $source_table];
			}

			// Find matching emarking_questions record
			$q = $this->db->get_where('emarking_questions', [
				'assessment_type' => $assessment_type,
				'grade' => (int) $grade,
				'subject_code' => (string) $subject_code,
				'version' => (int) $version,
				'page_no' => (string) $page_no,
				'question_no' => (string) $question_no,
				'status' => 1,
			])->row();

			if (!$q) {
				$skipped++;
				$errors[] = ['file' => $abs_path, 'reason' => 'No matching emarking_questions row', 'grade' => $grade, 'subject_code' => $subject_code, 'version' => $version, 'page_no' => $page_no, 'question_no' => $question_no];
				continue;
			}

			// Build DB image_path relative to web root
			$image_path = $base_folder;
			if (strpos($image_path, ':') !== false || strpos($image_path, '/') === 0) {
				// absolute base folder: store relative to FCPATH if possible
				$fcp = str_replace('\\', '/', rtrim(FCPATH, '\\/'));
				if (strpos($abs_path, $fcp) === 0) {
					$image_path = ltrim(substr($abs_path, strlen($fcp)), '/');
				} else {
					$image_path = ltrim(str_replace($abs_base, $base_folder, $abs_path), '/');
				}
			} else {
				$image_path = ltrim(str_replace($abs_base, $base_folder, $abs_path), '/');
			}
			$image_path = str_replace(['\\', '//'], ['/', '/'], $image_path);

			$payload = [
				'assessment_type' => $assessment_type,
				'source_table' => $source_table,
				'source_paper_id' => (!empty($source_row) && !empty($cols['id'])) ? (int) ($source_row[$cols['id']] ?? 0) : 0,
				'paper_barcode' => $barcode,
				'grade' => (int) $grade,
				'school_id' => (!empty($source_row) && !empty($cols['school_id'])) ? (int) ($source_row[$cols['school_id']] ?? null) : null,
				'lsacode' => (!empty($source_row) && !empty($cols['lsacode'])) ? (string) ($source_row[$cols['lsacode']] ?? null) : null,
				'subject_code' => (string) $subject_code,
				'version' => (int) $version,
				'roll_no' => (!empty($source_row) && !empty($cols['roll_no'])) ? (string) ($source_row[$cols['roll_no']] ?? '') : '',
				'page_no' => (string) $page_no,
				'question_id' => (int) $q->id,
				'question_no' => (string) $question_no,
				'image_path' => $image_path,
				'upload_batch_no' => (string) $upload_batch_no,
				'status' => 'UPLOADED',
				'created_at' => $now,
			];

			$this->db->insert('emarking_question_images', $payload);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$skipped++;
				// Duplicate key expected sometimes
				$errors[] = ['file' => $abs_path, 'reason' => 'DB insert failed', 'db_code' => $err['code'], 'db_message' => $err['message']];
				continue;
			}

			$inserted++;
		}

		return [
			'inserted' => $inserted,
			'skipped' => $skipped,
			'errors' => $errors,
		];
	}
}
