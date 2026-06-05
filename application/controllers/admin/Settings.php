<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

	private function require_super_admin()
	{
		if ((int) logged('role') !== 1) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Access denied.');
			redirect('admin/settings');
			exit;
		}
	}

	private function bulk_mark_range_options($max_marks)
	{
		$max_marks = (float) $max_marks;
		if ($max_marks < 0) $max_marks = 0;

		$scale = 2;
		$step = 0.5;
		$options = [];
		$current = 0.0;

		while ($current < $max_marks) {
			$options[] = number_format($current, $scale, '.', '');
			$current += $step;
		}

		$options[] = number_format($max_marks, $scale, '.', '');
		$options = array_values(array_unique($options));

		return array_map(function ($value) {
			return rtrim(rtrim((string) $value, '0'), '.');
		}, $options);
	}

	private function bulk_mark_interval_options()
	{
		return ['1', '0.5'];
	}

	private function bulk_mark_value_matches_interval($value, $interval)
	{
		$value_units = (int) round(((float) $value) * 2);
		$interval_units = max(1, (int) round(((float) $interval) * 2));
		return $value_units % $interval_units === 0;
	}

	private function bulk_mark_random_value($min_mark, $max_mark, $interval)
	{
		$min_mark = (float) $min_mark;
		$max_mark = (float) $max_mark;
		$interval = (float) $interval;
		if ($interval <= 0) $interval = 0.5;
		if ($max_mark < $min_mark) {
			$tmp = $min_mark;
			$min_mark = $max_mark;
			$max_mark = $tmp;
		}

		$min_units = (int) round($min_mark * 2);
		$max_units = (int) round($max_mark * 2);
		$interval_units = max(1, (int) round($interval * 2));
		if ($max_units < $min_units) $max_units = $min_units;

		$choices = [];
		for ($units = $min_units; $units <= $max_units; $units += $interval_units) {
			$choices[] = $units;
		}
		if (empty($choices)) {
			$choices[] = $min_units;
		}

		$selected = $choices[array_rand($choices)];
		return $selected / 2;
	}

	private function bulk_mark_summary_text($selected, $processed, $failed)
	{
		return 'Bulk auto mark completed. Marked: ' . (int) $selected . ', Saved: ' . (int) $processed . ', Failed: ' . (int) $failed . '.';
	}

	private function sync_eng_crqs_table()
	{
		return 'tbl_missing_barcodes_englishcrq';
	}

	private function sync_eng_dict_table()
	{
		return 'tbl_missing_barcodes_englishdict';
	}

	private function sync_eng_crqs_counts()
	{
		$table = $this->sync_eng_crqs_table();
		if (!$this->db->table_exists($table)) {
			return [
				'total' => 0,
				'pending' => 0,
				'done' => 0,
			];
		}

		$row = $this->db->select('COUNT(*) AS total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS done', false)
			->from($table)
			->get()
			->row();

		return [
			'total' => (int) ($row->total ?? 0),
			'pending' => (int) ($row->pending ?? 0),
			'done' => (int) ($row->done ?? 0),
		];
	}

	private function sync_eng_dict_counts()
	{
		$table = $this->sync_eng_dict_table();
		if (!$this->db->table_exists($table)) {
			return [
				'total' => 0,
				'pending' => 0,
				'done' => 0,
			];
		}

		$row = $this->db->select('COUNT(*) AS total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS done', false)
			->from($table)
			->get()
			->row();

		return [
			'total' => (int) ($row->total ?? 0),
			'pending' => (int) ($row->pending ?? 0),
			'done' => (int) ($row->done ?? 0),
		];
	}

	private function sync_eng_crqs_path_meta($image_barcode)
	{
		$raw = trim((string) $image_barcode);
		if ($raw === '') return null;

		$filename = basename(str_replace('\\', '/', $raw));
		if (!preg_match('/^([0-9]+)_(1|2)(\.[A-Za-z0-9]+)?$/', $filename, $matches)) {
			return null;
		}

		$digits = (string) $matches[1];
		if (strlen($digits) < 14) {
			return null;
		}

		$image_no = (string) $matches[2];
		$ext = trim((string) ($matches[3] ?? ''));
		if ($ext === '') {
			$ext = '.jpg';
			$filename = $digits . '_' . $image_no . $ext;
		}

		$dir_parts = [
			substr($digits, 0, 1),
			substr($digits, 7, 1),
			substr($digits, 8, 1),
			substr($digits, 11, 3),
			($image_no === '2') ? 'q2' : 'q1',
		];
		$dir_parts = array_map(function ($part) {
			return trim((string) $part);
		}, $dir_parts);
		if (in_array('', $dir_parts, true)) {
			return null;
		}

		$relative_dir = implode('/', $dir_parts);
		return [
			'filename' => $filename,
			'relative_dir' => $relative_dir,
			'source_relative_dir' => 'storagebox/crqs/' . $relative_dir,
			'target_relative_dir' => 'storagebox/mcrqs/' . $relative_dir,
		];
	}

	private function sync_eng_dict_path_meta($image_barcode)
	{
		$meta = $this->sync_eng_crqs_path_meta($image_barcode);
		if ($meta === null) return null;

		$meta['source_relative_dir'] = 'storagebox/dictations/' . $meta['relative_dir'];
		$meta['target_relative_dir'] = 'storagebox/mdictations/' . $meta['relative_dir'];
		return $meta;
	}

	private function sync_eng_crqs_abs_path($relative_path)
	{
		$relative_path = trim(str_replace('\\', '/', (string) $relative_path), '/');
		return rtrim((string) FCPATH, '/\\') . '/' . $relative_path;
	}

	private function sync_eng_crqs_pick_random_file($dir)
	{
		$dir = trim((string) $dir);
		if ($dir === '' || !is_dir($dir)) return null;

		$candidates = [];
		foreach ((glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '*') ?: []) as $path) {
			if (!is_file($path)) continue;
			$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
			if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) continue;
			$candidates[] = $path;
		}

		if (empty($candidates)) return null;
		return $candidates[array_rand($candidates)];
	}

	private function sync_eng_crqs_ensure_dir($dir)
	{
		$dir = trim((string) $dir);
		if ($dir === '') return false;
		if (is_dir($dir)) return true;
		return @mkdir($dir, 0775, true);
	}

	private function sync_eng_crqs_process_batch($limit)
	{
		$table = $this->sync_eng_crqs_table();
		$limit = max(1, min(10000, (int) $limit));

		$summary = [
			'requested' => $limit,
			'processed' => 0,
			'copied' => 0,
			'failed' => 0,
			'items' => [],
		];

		if (!$this->db->table_exists($table)) {
			$summary['error'] = 'Table not found: ' . $table;
			return $summary;
		}

		$rows = $this->db->select('id, image_barcode, status')
			->from($table)
			->where('status', 0)
			->order_by('id', 'ASC')
			->limit($limit)
			->get()
			->result();

		foreach (($rows ?? []) as $row) {
			$summary['processed']++;
			$item = [
				'id' => (int) ($row->id ?? 0),
				'image_barcode' => (string) ($row->image_barcode ?? ''),
				'result' => 'Failed',
				'message' => '',
				'source' => '',
				'target' => '',
			];

			$meta = $this->sync_eng_crqs_path_meta($item['image_barcode']);
			if ($meta === null) {
				$item['message'] = 'Invalid image_barcode format.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_dir_rel = $meta['source_relative_dir'];
			$target_dir_rel = $meta['target_relative_dir'];
			$source_dir_abs = $this->sync_eng_crqs_abs_path($source_dir_rel);
			$target_dir_abs = $this->sync_eng_crqs_abs_path($target_dir_rel);
			$target_file_abs = rtrim($target_dir_abs, '/\\') . '/' . $meta['filename'];
			$target_file_rel = $target_dir_rel . '/' . $meta['filename'];

			$item['source'] = $source_dir_rel;
			$item['target'] = $target_file_rel;

			if (!is_dir($source_dir_abs)) {
				$item['message'] = 'Source folder not found.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_file_abs = $this->sync_eng_crqs_pick_random_file($source_dir_abs);
			if ($source_file_abs === null) {
				$item['message'] = 'No source image found in source folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			if (!$this->sync_eng_crqs_ensure_dir($target_dir_abs)) {
				$item['message'] = 'Unable to create target folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$copied = is_file($target_file_abs) ? true : @copy($source_file_abs, $target_file_abs);
			if (!$copied) {
				$item['message'] = 'Copy failed.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$this->db->where('id', (int) $item['id'])->update($table, ['status' => 1]);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$item['message'] = 'Copied file but failed to update DB status.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$item['result'] = 'Copied';
			$item['message'] = is_file($target_file_abs) ? 'Synchronized successfully.' : 'Copied successfully.';
			$summary['copied']++;
			$summary['items'][] = $item;
		}

		return $summary;
	}

	private function sync_eng_dict_process_batch($limit)
	{
		$table = $this->sync_eng_dict_table();
		$limit = max(1, min(10000, (int) $limit));

		$summary = [
			'requested' => $limit,
			'processed' => 0,
			'copied' => 0,
			'failed' => 0,
			'items' => [],
		];

		if (!$this->db->table_exists($table)) {
			$summary['error'] = 'Table not found: ' . $table;
			return $summary;
		}

		$rows = $this->db->select('id, image_barcode, status')
			->from($table)
			->where('status', 0)
			->order_by('id', 'ASC')
			->limit($limit)
			->get()
			->result();

		foreach (($rows ?? []) as $row) {
			$summary['processed']++;
			$item = [
				'id' => (int) ($row->id ?? 0),
				'image_barcode' => (string) ($row->image_barcode ?? ''),
				'result' => 'Failed',
				'message' => '',
				'source' => '',
				'target' => '',
			];

			$meta = $this->sync_eng_dict_path_meta($item['image_barcode']);
			if ($meta === null) {
				$item['message'] = 'Invalid image_barcode format.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_dir_rel = $meta['source_relative_dir'];
			$target_dir_rel = $meta['target_relative_dir'];
			$source_dir_abs = $this->sync_eng_crqs_abs_path($source_dir_rel);
			$target_dir_abs = $this->sync_eng_crqs_abs_path($target_dir_rel);
			$target_file_abs = rtrim($target_dir_abs, '/\\') . '/' . $meta['filename'];
			$target_file_rel = $target_dir_rel . '/' . $meta['filename'];

			$item['source'] = $source_dir_rel;
			$item['target'] = $target_file_rel;

			if (!is_dir($source_dir_abs)) {
				$item['message'] = 'Source folder not found.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_file_abs = $this->sync_eng_crqs_pick_random_file($source_dir_abs);
			if ($source_file_abs === null) {
				$item['message'] = 'No source image found in source folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			if (!$this->sync_eng_crqs_ensure_dir($target_dir_abs)) {
				$item['message'] = 'Unable to create target folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$copied = is_file($target_file_abs) ? true : @copy($source_file_abs, $target_file_abs);
			if (!$copied) {
				$item['message'] = 'Copy failed.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$this->db->where('id', (int) $item['id'])->update($table, ['status' => 1]);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$item['message'] = 'Copied file but failed to update DB status.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$item['result'] = 'Copied';
			$item['message'] = is_file($target_file_abs) ? 'Synchronized successfully.' : 'Copied successfully.';
			$summary['copied']++;
			$summary['items'][] = $item;
		}

		return $summary;
	}

	private function sync_urdu_crqs_table()
	{
		return 'tbl_missing_barcodes_urducrq';
	}

	private function sync_urdu_dict_table()
	{
		return 'tbl_missing_barcodes_urdudict';
	}

	private function sync_urdu_crqs_counts()
	{
		$table = $this->sync_urdu_crqs_table();
		if (!$this->db->table_exists($table)) {
			return [
				'total' => 0,
				'pending' => 0,
				'done' => 0,
			];
		}

		$row = $this->db->select('COUNT(*) AS total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS done', false)
			->from($table)
			->get()
			->row();

		return [
			'total' => (int) ($row->total ?? 0),
			'pending' => (int) ($row->pending ?? 0),
			'done' => (int) ($row->done ?? 0),
		];
	}

	private function sync_urdu_dict_counts()
	{
		$table = $this->sync_urdu_dict_table();
		if (!$this->db->table_exists($table)) {
			return [
				'total' => 0,
				'pending' => 0,
				'done' => 0,
			];
		}

		$row = $this->db->select('COUNT(*) AS total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS done', false)
			->from($table)
			->get()
			->row();

		return [
			'total' => (int) ($row->total ?? 0),
			'pending' => (int) ($row->pending ?? 0),
			'done' => (int) ($row->done ?? 0),
		];
	}

	private function sync_urdu_crqs_process_batch($limit)
	{
		$table = $this->sync_urdu_crqs_table();
		$limit = max(1, min(10000, (int) $limit));

		$summary = [
			'requested' => $limit,
			'processed' => 0,
			'copied' => 0,
			'failed' => 0,
			'items' => [],
		];

		if (!$this->db->table_exists($table)) {
			$summary['error'] = 'Table not found: ' . $table;
			return $summary;
		}

		$rows = $this->db->select('id, image_barcode, status')
			->from($table)
			->where('status', 0)
			->order_by('id', 'ASC')
			->limit($limit)
			->get()
			->result();

		foreach (($rows ?? []) as $row) {
			$summary['processed']++;
			$item = [
				'id' => (int) ($row->id ?? 0),
				'image_barcode' => (string) ($row->image_barcode ?? ''),
				'result' => 'Failed',
				'message' => '',
				'source' => '',
				'target' => '',
			];

			$meta = $this->sync_eng_crqs_path_meta($item['image_barcode']);
			if ($meta === null) {
				$item['message'] = 'Invalid image_barcode format.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_dir_rel = $meta['source_relative_dir'];
			$target_dir_rel = $meta['target_relative_dir'];
			$source_dir_abs = $this->sync_eng_crqs_abs_path($source_dir_rel);
			$target_dir_abs = $this->sync_eng_crqs_abs_path($target_dir_rel);
			$target_file_abs = rtrim($target_dir_abs, '/\\') . '/' . $meta['filename'];
			$target_file_rel = $target_dir_rel . '/' . $meta['filename'];

			$item['source'] = $source_dir_rel;
			$item['target'] = $target_file_rel;

			if (!is_dir($source_dir_abs)) {
				$item['message'] = 'Source folder not found.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_file_abs = $this->sync_eng_crqs_pick_random_file($source_dir_abs);
			if ($source_file_abs === null) {
				$item['message'] = 'No source image found in source folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			if (!$this->sync_eng_crqs_ensure_dir($target_dir_abs)) {
				$item['message'] = 'Unable to create target folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$copied = is_file($target_file_abs) ? true : @copy($source_file_abs, $target_file_abs);
			if (!$copied) {
				$item['message'] = 'Copy failed.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$this->db->where('id', (int) $item['id'])->update($table, ['status' => 1]);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$item['message'] = 'Copied file but failed to update DB status.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$item['result'] = 'Copied';
			$item['message'] = is_file($target_file_abs) ? 'Synchronized successfully.' : 'Copied successfully.';
			$summary['copied']++;
			$summary['items'][] = $item;
		}

		return $summary;
	}

	private function sync_urdu_dict_process_batch($limit)
	{
		$table = $this->sync_urdu_dict_table();
		$limit = max(1, min(10000, (int) $limit));

		$summary = [
			'requested' => $limit,
			'processed' => 0,
			'copied' => 0,
			'failed' => 0,
			'items' => [],
		];

		if (!$this->db->table_exists($table)) {
			$summary['error'] = 'Table not found: ' . $table;
			return $summary;
		}

		$rows = $this->db->select('id, image_barcode, status')
			->from($table)
			->where('status', 0)
			->order_by('id', 'ASC')
			->limit($limit)
			->get()
			->result();

		foreach (($rows ?? []) as $row) {
			$summary['processed']++;
			$item = [
				'id' => (int) ($row->id ?? 0),
				'image_barcode' => (string) ($row->image_barcode ?? ''),
				'result' => 'Failed',
				'message' => '',
				'source' => '',
				'target' => '',
			];

			$meta = $this->sync_eng_dict_path_meta($item['image_barcode']);
			if ($meta === null) {
				$item['message'] = 'Invalid image_barcode format.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_dir_rel = $meta['source_relative_dir'];
			$target_dir_rel = $meta['target_relative_dir'];
			$source_dir_abs = $this->sync_eng_crqs_abs_path($source_dir_rel);
			$target_dir_abs = $this->sync_eng_crqs_abs_path($target_dir_rel);
			$target_file_abs = rtrim($target_dir_abs, '/\\') . '/' . $meta['filename'];
			$target_file_rel = $target_dir_rel . '/' . $meta['filename'];

			$item['source'] = $source_dir_rel;
			$item['target'] = $target_file_rel;

			if (!is_dir($source_dir_abs)) {
				$item['message'] = 'Source folder not found.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$source_file_abs = $this->sync_eng_crqs_pick_random_file($source_dir_abs);
			if ($source_file_abs === null) {
				$item['message'] = 'No source image found in source folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			if (!$this->sync_eng_crqs_ensure_dir($target_dir_abs)) {
				$item['message'] = 'Unable to create target folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$copied = is_file($target_file_abs) ? true : @copy($source_file_abs, $target_file_abs);
			if (!$copied) {
				$item['message'] = 'Copy failed.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$this->db->where('id', (int) $item['id'])->update($table, ['status' => 1]);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$item['message'] = 'Copied file but failed to update DB status.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}

			$item['result'] = 'Copied';
			$item['message'] = is_file($target_file_abs) ? 'Synchronized successfully.' : 'Copied successfully.';
			$summary['copied']++;
			$summary['items'][] = $item;
		}

		return $summary;
	}

	private function sync_math_crqs_table()
	{
		return 'tbl_missing_barcodes_mathcrq';
	}

	private function sync_math_crqs_counts()
	{
		$table = $this->sync_math_crqs_table();
		if (!$this->db->table_exists($table)) {
			return ['total' => 0, 'pending' => 0, 'done' => 0];
		}
		$row = $this->db->select('COUNT(*) AS total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS done', false)
			->from($table)
			->get()
			->row();
		return [
			'total' => (int) ($row->total ?? 0),
			'pending' => (int) ($row->pending ?? 0),
			'done' => (int) ($row->done ?? 0),
		];
	}

	private function sync_math_crqs_process_batch($limit)
	{
		$table = $this->sync_math_crqs_table();
		$limit = max(1, min(10000, (int) $limit));
		$summary = ['requested' => $limit, 'processed' => 0, 'copied' => 0, 'failed' => 0, 'items' => []];
		if (!$this->db->table_exists($table)) {
			$summary['error'] = 'Table not found: ' . $table;
			return $summary;
		}
		$rows = $this->db->select('id, image_barcode, status')->from($table)->where('status', 0)->order_by('id', 'ASC')->limit($limit)->get()->result();
		foreach (($rows ?? []) as $row) {
			$summary['processed']++;
			$item = ['id' => (int) ($row->id ?? 0), 'image_barcode' => (string) ($row->image_barcode ?? ''), 'result' => 'Failed', 'message' => '', 'source' => '', 'target' => ''];
			$meta = $this->sync_eng_crqs_path_meta($item['image_barcode']);
			if ($meta === null) {
				$item['message'] = 'Invalid image_barcode format.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$source_dir_rel = $meta['source_relative_dir'];
			$target_dir_rel = $meta['target_relative_dir'];
			$source_dir_abs = $this->sync_eng_crqs_abs_path($source_dir_rel);
			$target_dir_abs = $this->sync_eng_crqs_abs_path($target_dir_rel);
			$target_file_abs = rtrim($target_dir_abs, '/\\') . '/' . $meta['filename'];
			$target_file_rel = $target_dir_rel . '/' . $meta['filename'];
			$item['source'] = $source_dir_rel;
			$item['target'] = $target_file_rel;
			if (!is_dir($source_dir_abs)) {
				$item['message'] = 'Source folder not found.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$source_file_abs = $this->sync_eng_crqs_pick_random_file($source_dir_abs);
			if ($source_file_abs === null) {
				$item['message'] = 'No source image found in source folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			if (!$this->sync_eng_crqs_ensure_dir($target_dir_abs)) {
				$item['message'] = 'Unable to create target folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$copied = is_file($target_file_abs) ? true : @copy($source_file_abs, $target_file_abs);
			if (!$copied) {
				$item['message'] = 'Copy failed.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$this->db->where('id', (int) $item['id'])->update($table, ['status' => 1]);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$item['message'] = 'Copied file but failed to update DB status.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$item['result'] = 'Copied';
			$item['message'] = 'Synchronized successfully.';
			$summary['copied']++;
			$summary['items'][] = $item;
		}
		return $summary;
	}

	private function sync_science_crqs_table()
	{
		return 'tbl_missing_barcodes_sciencecrq';
	}

	private function sync_science_crqs_counts()
	{
		$table = $this->sync_science_crqs_table();
		if (!$this->db->table_exists($table)) {
			return ['total' => 0, 'pending' => 0, 'done' => 0];
		}
		$row = $this->db->select('COUNT(*) AS total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS done', false)
			->from($table)
			->get()
			->row();
		return [
			'total' => (int) ($row->total ?? 0),
			'pending' => (int) ($row->pending ?? 0),
			'done' => (int) ($row->done ?? 0),
		];
	}

	private function sync_science_crqs_process_batch($limit)
	{
		$table = $this->sync_science_crqs_table();
		$limit = max(1, min(10000, (int) $limit));
		$summary = ['requested' => $limit, 'processed' => 0, 'copied' => 0, 'failed' => 0, 'items' => []];
		if (!$this->db->table_exists($table)) {
			$summary['error'] = 'Table not found: ' . $table;
			return $summary;
		}
		$rows = $this->db->select('id, image_barcode, status')->from($table)->where('status', 0)->order_by('id', 'ASC')->limit($limit)->get()->result();
		foreach (($rows ?? []) as $row) {
			$summary['processed']++;
			$item = ['id' => (int) ($row->id ?? 0), 'image_barcode' => (string) ($row->image_barcode ?? ''), 'result' => 'Failed', 'message' => '', 'source' => '', 'target' => ''];
			$meta = $this->sync_eng_crqs_path_meta($item['image_barcode']);
			if ($meta === null) {
				$item['message'] = 'Invalid image_barcode format.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$source_dir_rel = $meta['source_relative_dir'];
			$target_dir_rel = $meta['target_relative_dir'];
			$source_dir_abs = $this->sync_eng_crqs_abs_path($source_dir_rel);
			$target_dir_abs = $this->sync_eng_crqs_abs_path($target_dir_rel);
			$target_file_abs = rtrim($target_dir_abs, '/\\') . '/' . $meta['filename'];
			$target_file_rel = $target_dir_rel . '/' . $meta['filename'];
			$item['source'] = $source_dir_rel;
			$item['target'] = $target_file_rel;
			if (!is_dir($source_dir_abs)) {
				$item['message'] = 'Source folder not found.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$source_file_abs = $this->sync_eng_crqs_pick_random_file($source_dir_abs);
			if ($source_file_abs === null) {
				$item['message'] = 'No source image found in source folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			if (!$this->sync_eng_crqs_ensure_dir($target_dir_abs)) {
				$item['message'] = 'Unable to create target folder.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$copied = is_file($target_file_abs) ? true : @copy($source_file_abs, $target_file_abs);
			if (!$copied) {
				$item['message'] = 'Copy failed.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$this->db->where('id', (int) $item['id'])->update($table, ['status' => 1]);
			$err = $this->db->error();
			if (!empty($err['code'])) {
				$item['message'] = 'Copied file but failed to update DB status.';
				$summary['failed']++;
				$summary['items'][] = $item;
				continue;
			}
			$item['result'] = 'Copied';
			$item['message'] = 'Synchronized successfully.';
			$summary['copied']++;
			$summary['items'][] = $item;
		}
		return $summary;
	}

	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'Settings';
		$this->page_data['page']->menu = 'settings';
	}

	public function index()
	{
		$this->general();
	}

	public function general()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'general';
		$this->load->view('admin/settings/general', $this->page_data);
	}

	public function generalUpdate()
	{

		ifPermissions('general_settings');

		postAllowed();
		
		$this->settings_model->updateByKey('date_format', post('date_format'));
		$this->settings_model->updateByKey('datetime_format', post('datetime_format'));
		$this->settings_model->updateByKey('google_recaptcha_enabled', post('google_recaptcha_enabled') == 'ok' ? 1 : 0 );
		$this->settings_model->updateByKey('google_recaptcha_sitekey', post('google_recaptcha_sitekey'));
		$this->settings_model->updateByKey('google_recaptcha_secretkey', post('google_recaptcha_secretkey'));
		$this->settings_model->updateByKey('timezone', post('timezone'));
		$this->settings_model->updateByKey('default_lang', post('default_lang'));
		$this->settings_model->updateByKey('user_access_blocked', post('user_access_blocked') == '1' ? '1' : '0');
		$this->settings_model->updateByKey('user_access_block_message', trim((string) post('user_access_block_message')));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Settings has been Updated Successfully');

		$this->activity_model->add("Company Settings Updated by User: #".logged('id'));
		
		redirect('admin/settings/general');
	}

	public function sync_blacklist()
	{
		$this->session->set_flashdata('alert-type', 'error');
		$this->session->set_flashdata('alert', 'Blacklist sync has been removed from this project.');
		redirect('admin/settings/general');
	}

	public function company()
	{
		ifPermissions('company_settings');
		$this->page_data['page']->submenu = 'company';
		$this->load->view('admin/settings/company', $this->page_data);
	}

	public function registration()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'registration';

		$this->page_data['registration_enabled'] = (string) $this->settings_model->get_setting('registration_enabled', '1');
		$this->page_data['registration_close_at'] = (string) $this->settings_model->get_setting('registration_close_at', '');

		$this->load->view('admin/settings/registration', $this->page_data);
	}

	public function registrationUpdate()
	{
		ifPermissions('general_settings');
		postAllowed();

		$this->load->library('form_validation');
		$this->form_validation->set_rules('registration_enabled', 'Enable Registration', 'trim|required|in_list[0,1]');
		$this->form_validation->set_rules('registration_close_at', 'Registration Close Date/Time', 'trim|callback__valid_optional_datetime');

		if ($this->form_validation->run() === FALSE) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', validation_errors());
			redirect('admin/settings/registration');
			return;
		}

		$enabled = (string) post('registration_enabled');
		$close_at = trim((string) post('registration_close_at'));
		if ($close_at !== '' && strpos($close_at, 'T') !== false) {
			$close_at = str_replace('T', ' ', $close_at);
		}
		if ($close_at !== '' && preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}$/', $close_at)) {
			$close_at .= ':00';
		}

		$this->settings_model->set_setting('registration_enabled', $enabled === '1' ? '1' : '0');
		$this->settings_model->set_setting('registration_close_at', $close_at);

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Registration settings updated successfully.');
		$this->activity_model->add("Registration settings updated by User: #".logged('id'));

		redirect('admin/settings/registration');
	}

	public function marking()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'marking';

		$this->page_data['marking_enabled'] = (string) $this->settings_model->get_setting('marking_enabled', '1');
		$this->page_data['marking_block_message'] = (string) $this->settings_model->get_setting('marking_block_message', 'Marking is stopped currently. Please try again later.');

		$this->load->view('admin/settings/marking', $this->page_data);
	}

	public function markingUpdate()
	{
		ifPermissions('general_settings');
		postAllowed();

		$this->load->library('form_validation');
		$this->form_validation->set_rules('marking_enabled', 'Enable Marking', 'trim|required|in_list[0,1]');
		$this->form_validation->set_rules('marking_block_message', 'Blocked Marking Message', 'trim|required|min_length[3]');

		if ($this->form_validation->run() === FALSE) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', validation_errors());
			redirect('admin/settings/marking');
			return;
		}

		$enabled = (string) post('marking_enabled');
		$msg = trim((string) post('marking_block_message'));

		$this->settings_model->set_setting('marking_enabled', $enabled === '1' ? '1' : '0');
		$this->settings_model->set_setting('marking_block_message', $msg);

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Marking settings updated successfully.');
		$this->activity_model->add("Marking settings updated by User: #".logged('id'));

		redirect('admin/settings/marking');
	}

	public function mark()
	{
		$this->require_super_admin();

		$this->load->model('Emarking_batch_model', 'emarking_batch');

		$this->page_data['page']->submenu = 'mark';
		$this->page_data['emarkers'] = $this->emarking_batch->get_emarkers();

		$selected_emarker_id = (int) $this->input->get('emarker_id', true);
		$selected_batch_id = (int) $this->input->get('batch_id', true);

		$this->page_data['selected_emarker_id'] = $selected_emarker_id;
		$this->page_data['selected_batch_id'] = $selected_batch_id;
		$this->page_data['batches'] = $selected_emarker_id > 0
			? $this->emarking_batch->get_bulk_mark_batches_by_emarker($selected_emarker_id)
			: [];
		$this->page_data['selected_batch'] = $selected_emarker_id > 0 && $selected_batch_id > 0
			? $this->emarking_batch->get_bulk_mark_batch($selected_batch_id, $selected_emarker_id)
			: null;

		$selected_batch = $this->page_data['selected_batch'];
		$max_marks = (float) ($selected_batch->max_marks ?? 5);
		$this->page_data['mark_options'] = $this->bulk_mark_range_options($max_marks);
		$this->page_data['interval_options'] = $this->bulk_mark_interval_options();
		$this->page_data['default_min_mark'] = rtrim(rtrim(number_format(min($max_marks, 3), 2, '.', ''), '0'), '.');
		$this->page_data['default_max_mark'] = rtrim(rtrim(number_format(min($max_marks, 5), 2, '.', ''), '0'), '.');
		$this->page_data['default_interval'] = '1';
		if ($this->page_data['default_min_mark'] === '') $this->page_data['default_min_mark'] = '0';
		if ($this->page_data['default_max_mark'] === '') $this->page_data['default_max_mark'] = '0';

		$this->load->view('admin/settings/mark', $this->page_data);
	}

	public function markSubmit()
	{
		$this->require_super_admin();
		postAllowed();

		$this->load->model('Emarking_batch_model', 'emarking_batch');
		$this->load->model('Marking_model', 'marking');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('emarker_id', 'eMarker', 'trim|required|integer');
		$this->form_validation->set_rules('batch_id', 'Batch', 'trim|required|integer');
		$this->form_validation->set_rules('min_mark', 'Minimum Mark', 'trim|required|numeric');
		$this->form_validation->set_rules('max_mark', 'Maximum Mark', 'trim|required|numeric');
		$this->form_validation->set_rules('mark_interval', 'Interval', 'trim|required');

		$selected_emarker_id = (int) $this->input->post('emarker_id', true);
		$selected_batch_id = (int) $this->input->post('batch_id', true);

		if ($this->form_validation->run() === false) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', validation_errors());
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		$batch = $this->emarking_batch->get_bulk_mark_batch($selected_batch_id, $selected_emarker_id);
		if (!$batch) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Selected batch is invalid or has no pending items.');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id);
			return;
		}

		$min_mark = (float) $this->input->post('min_mark', true);
		$max_mark = (float) $this->input->post('max_mark', true);
		$mark_interval = trim((string) $this->input->post('mark_interval', true));
		$question_max = (float) ($batch->max_marks ?? 0);

		if (!in_array($mark_interval, $this->bulk_mark_interval_options(), true)) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Invalid interval selected.');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		if ($min_mark < 0 || $max_mark < 0) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Marks cannot be negative.');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		if ($min_mark > $max_mark) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Minimum mark cannot be greater than maximum mark.');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		if ($max_mark > $question_max) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Selected range exceeds question max marks (' . rtrim(rtrim(number_format($question_max, 2, '.', ''), '0'), '.') . ').');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		if (!$this->bulk_mark_value_matches_interval($min_mark, $mark_interval) || !$this->bulk_mark_value_matches_interval($max_mark, $mark_interval)) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Selected minimum/maximum marks do not match the chosen interval.');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		$items = $this->db->select('i.id')
			->from('emarking_batch_items i')
			->join('emarking_batches b', 'b.id = i.batch_id', 'inner')
			->where('i.batch_id', $selected_batch_id)
			->where('b.assigned_to', $selected_emarker_id)
			->where('i.status', 'PENDING')
			->order_by('i.id', 'ASC')
			->get()
			->result();

		if (empty($items)) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'No pending items found for the selected batch.');
			redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
			return;
		}

		$selected = count($items);
		$processed = 0;
		$failed = 0;
		foreach ($items as $item) {
			$random_mark = $this->bulk_mark_random_value($min_mark, $max_mark, $mark_interval);
			$out = $this->marking->save_mark((int) $item->id, $selected_emarker_id, [
				'action' => 'MARKED',
				'marks_obtained' => $random_mark,
				'remarks' => 'Auto marked by admin bulk tool',
				'steps' => [],
			]);
			if (!empty($out['ok'])) {
				$processed++;
			} else {
				$failed++;
			}
		}

		$this->session->set_flashdata('alert-type', $failed > 0 ? 'warning' : 'success');
		$this->session->set_flashdata('alert', $this->bulk_mark_summary_text($selected, $processed, $failed));
		$this->activity_model->add('Bulk auto mark executed by User: #' . logged('id') . ' for eMarker #' . $selected_emarker_id . ' batch #' . $selected_batch_id . ' range [' . $min_mark . ',' . $max_mark . '] interval [' . $mark_interval . ']');

		redirect('admin/settings/mark?emarker_id=' . $selected_emarker_id . '&batch_id=' . $selected_batch_id);
	}

	public function check_sizes()
	{
		ifPermissions('general_settings');
		$this->load->library('pagination');
		$this->page_data['page']->submenu = 'check_sizes';

		$dir = trim((string) $this->input->get('dir', true));
		$min_mb = (float) $this->input->get('min_mb', true);
		if ($min_mb < 0) $min_mb = 0;
		$min_bytes = (int) round($min_mb * 1024 * 1024);

		$per_page = (int) $this->input->get('per_page', true);
		$allowed_per_page = [100, 200, 500];
		if (!in_array($per_page, $allowed_per_page, true)) $per_page = 100;

		$page = (int) $this->input->get('page', true);
		$page = $page > 0 ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$scan_id = trim((string) $this->input->get('scan_id', true));
		$cache_dir = rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'check_sizes';
		if (!is_dir($cache_dir)) {
			@mkdir($cache_dir, 0755, true);
		}

		// Cleanup old scans (older than 24 hours)
		if (is_dir($cache_dir)) {
			foreach (glob($cache_dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
				if (!is_file($f)) continue;
				if (@filemtime($f) !== false && (time() - (int) filemtime($f)) > 86400) {
					@unlink($f);
				}
			}
		}

		$error = '';
		$results = [];
		$scan_meta = null;

		$cache_file = ($scan_id !== '') ? ($cache_dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9_\\-]/', '', $scan_id) . '.json') : '';
		if ($cache_file !== '' && is_file($cache_file)) {
			$raw = @file_get_contents($cache_file);
			$data = $raw ? json_decode($raw, true) : null;
			if (is_array($data)) {
				$scan_meta = $data;
				$results = isset($data['results']) && is_array($data['results']) ? $data['results'] : [];
			}
		} elseif ($dir !== '') {
			$real_dir = realpath($dir);
			if ($real_dir === false || !is_dir($real_dir)) {
				$error = 'Directory not found.';
			} else {
				$real_dir = rtrim($real_dir, '/\\');

				// Safety: allow scanning only inside project root or storagebox
				$project_root = rtrim((string) realpath(FCPATH), '/\\');
				$storagebox_root = realpath(FCPATH . 'storagebox');
				$storagebox_root = $storagebox_root ? rtrim((string) $storagebox_root, '/\\') : '';

				$allowed = false;
				if ($project_root !== '' && strpos($real_dir, $project_root) === 0) $allowed = true;
				if (!$allowed && $storagebox_root !== '' && strpos($real_dir, $storagebox_root) === 0) $allowed = true;

				if (!$allowed) {
					$error = 'Scanning is allowed only inside this project directory (or storagebox).';
				} else {
					$extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
					$found = [];

					try {
						$iter = new RecursiveIteratorIterator(
							new RecursiveDirectoryIterator($real_dir, FilesystemIterator::SKIP_DOTS)
						);

						foreach ($iter as $fileInfo) {
							if (!$fileInfo instanceof SplFileInfo) continue;
							if (!$fileInfo->isFile()) continue;

							$ext = strtolower((string) $fileInfo->getExtension());
							if (!in_array($ext, $extensions, true)) continue;

							$size = (int) $fileInfo->getSize();
							if ($size <= $min_bytes) continue;

							$abs = $fileInfo->getPathname();
							$abs_norm = str_replace('\\', '/', (string) $abs);

							$rel = null;
							$url = null;
							$fcp = rtrim(str_replace('\\', '/', (string) FCPATH), '/') . '/';
							if (strpos($abs_norm, $fcp) === 0) {
								$rel = ltrim(substr($abs_norm, strlen($fcp)), '/');
								$url = base_url($rel);
							}

							$found[] = [
								'path' => $rel !== null ? $rel : $abs_norm,
								'url' => $url,
								'size_bytes' => $size,
							];
						}
					} catch (Exception $e) {
						$error = 'Unable to scan directory: ' . $e->getMessage();
					}

					usort($found, function ($a, $b) {
						return (int) ($b['size_bytes'] ?? 0) <=> (int) ($a['size_bytes'] ?? 0);
					});

					$scan_id = sha1($real_dir . '|' . $min_bytes . '|' . microtime(true));
					$cache_file = $cache_dir . DIRECTORY_SEPARATOR . $scan_id . '.json';
					$scan_meta = [
						'created_at' => date('Y-m-d H:i:s'),
						'dir' => $real_dir,
						'min_mb' => $min_mb,
						'min_bytes' => $min_bytes,
						'results_count' => count($found),
						'results' => $found,
					];
					@file_put_contents($cache_file, json_encode($scan_meta));

					// Redirect to cached scan for pagination without rescanning
					$q = [
						'scan_id' => $scan_id,
						'dir' => $real_dir,
						'min_mb' => $min_mb,
						'per_page' => $per_page,
					];
					redirect('admin/settings/check_sizes?' . http_build_query($q));
					return;
				}
			}
		}

		$total = is_array($results) ? count($results) : 0;
		$config = [
			'base_url' => url('admin/settings/check_sizes'),
			'total_rows' => $total,
			'per_page' => $per_page,
			'page_query_string' => true,
			'query_string_segment' => 'page',
			'use_page_numbers' => true,
			'reuse_query_string' => true,
		];
		$this->pagination->initialize($config);

		$paged = array_slice($results, $offset, $per_page);

		$this->page_data['filters'] = [
			'dir' => $dir,
			'min_mb' => $min_mb,
			'per_page' => $per_page,
			'scan_id' => $scan_id,
		];
		$this->page_data['scan_meta'] = $scan_meta;
		$this->page_data['error'] = $error;
		$this->page_data['items'] = $paged;
		$this->page_data['pagination_links'] = $this->pagination->create_links();

		$this->load->view('admin/settings/check_sizes', $this->page_data);
	}

	public function sync_eng_crqs()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'sync_eng_crqs';
		$this->page_data['page']->title = 'Synchronize Eng CRQs';

		$batch_size = (int) $this->input->get_post('batch_size', true);
		if ($batch_size <= 0) $batch_size = 100;
		$batch_size = max(1, min(10000, $batch_size));

		$run_summary = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			@set_time_limit(0);
			$run_summary = $this->sync_eng_crqs_process_batch($batch_size);
			if (empty($run_summary['error'])) {
				$this->activity_model->add('Synchronize Eng CRQs run by User: #' . logged('id') . ' processed=' . (int) ($run_summary['processed'] ?? 0) . ' copied=' . (int) ($run_summary['copied'] ?? 0));
			}
		}

		$this->page_data['batch_size'] = $batch_size;
		$this->page_data['sync_counts'] = $this->sync_eng_crqs_counts();
		$this->page_data['run_summary'] = $run_summary;
		$this->load->view('admin/settings/sync_eng_crqs', $this->page_data);
	}

	public function sync_eng_dict()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'sync_eng_dict';
		$this->page_data['page']->title = 'Synchronize Eng Dict';

		$batch_size = (int) $this->input->get_post('batch_size', true);
		if ($batch_size <= 0) $batch_size = 100;
		$batch_size = max(1, min(10000, $batch_size));

		$run_summary = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			@set_time_limit(0);
			$run_summary = $this->sync_eng_dict_process_batch($batch_size);
			if (empty($run_summary['error'])) {
				$this->activity_model->add('Synchronize Eng Dict run by User: #' . logged('id') . ' processed=' . (int) ($run_summary['processed'] ?? 0) . ' copied=' . (int) ($run_summary['copied'] ?? 0));
			}
		}

		$this->page_data['batch_size'] = $batch_size;
		$this->page_data['sync_counts'] = $this->sync_eng_dict_counts();
		$this->page_data['run_summary'] = $run_summary;
		$this->load->view('admin/settings/sync_eng_dict', $this->page_data);
	}

	public function sync_urdu_crqs()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'sync_urdu_crqs';
		$this->page_data['page']->title = 'Synchronize Urdu CRQs';

		$batch_size = (int) $this->input->get_post('batch_size', true);
		if ($batch_size <= 0) $batch_size = 100;
		$batch_size = max(1, min(10000, $batch_size));

		$run_summary = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			@set_time_limit(0);
			$run_summary = $this->sync_urdu_crqs_process_batch($batch_size);
			if (empty($run_summary['error'])) {
				$this->activity_model->add('Synchronize Urdu CRQs run by User: #' . logged('id') . ' processed=' . (int) ($run_summary['processed'] ?? 0) . ' copied=' . (int) ($run_summary['copied'] ?? 0));
			}
		}

		$this->page_data['batch_size'] = $batch_size;
		$this->page_data['sync_counts'] = $this->sync_urdu_crqs_counts();
		$this->page_data['run_summary'] = $run_summary;
		$this->load->view('admin/settings/sync_urdu_crqs', $this->page_data);
	}

	public function sync_urdu_dict()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'sync_urdu_dict';
		$this->page_data['page']->title = 'Synchronize Urdu Dict';

		$batch_size = (int) $this->input->get_post('batch_size', true);
		if ($batch_size <= 0) $batch_size = 100;
		$batch_size = max(1, min(10000, $batch_size));

		$run_summary = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			@set_time_limit(0);
			$run_summary = $this->sync_urdu_dict_process_batch($batch_size);
			if (empty($run_summary['error'])) {
				$this->activity_model->add('Synchronize Urdu Dict run by User: #' . logged('id') . ' processed=' . (int) ($run_summary['processed'] ?? 0) . ' copied=' . (int) ($run_summary['copied'] ?? 0));
			}
		}

		$this->page_data['batch_size'] = $batch_size;
		$this->page_data['sync_counts'] = $this->sync_urdu_dict_counts();
		$this->page_data['run_summary'] = $run_summary;
		$this->load->view('admin/settings/sync_urdu_dict', $this->page_data);
	}

	public function sync_math_crqs()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'sync_math_crqs';
		$this->page_data['page']->title = 'Synchronize Math CRQs';
		$batch_size = (int) $this->input->get_post('batch_size', true);
		if ($batch_size <= 0) $batch_size = 100;
		$batch_size = max(1, min(10000, $batch_size));
		$run_summary = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			@set_time_limit(0);
			$run_summary = $this->sync_math_crqs_process_batch($batch_size);
			if (empty($run_summary['error'])) {
				$this->activity_model->add('Synchronize Math CRQs run by User: #' . logged('id') . ' processed=' . (int) ($run_summary['processed'] ?? 0) . ' copied=' . (int) ($run_summary['copied'] ?? 0));
			}
		}
		$this->page_data['batch_size'] = $batch_size;
		$this->page_data['sync_counts'] = $this->sync_math_crqs_counts();
		$this->page_data['run_summary'] = $run_summary;
		$this->load->view('admin/settings/sync_math_crqs', $this->page_data);
	}

	public function sync_science_crqs()
	{
		ifPermissions('general_settings');
		$this->page_data['page']->submenu = 'sync_science_crqs';
		$this->page_data['page']->title = 'Synchronize Science CRQs';
		$batch_size = (int) $this->input->get_post('batch_size', true);
		if ($batch_size <= 0) $batch_size = 100;
		$batch_size = max(1, min(10000, $batch_size));
		$run_summary = null;
		if ($this->input->method(true) === 'POST') {
			postAllowed();
			@set_time_limit(0);
			$run_summary = $this->sync_science_crqs_process_batch($batch_size);
			if (empty($run_summary['error'])) {
				$this->activity_model->add('Synchronize Science CRQs run by User: #' . logged('id') . ' processed=' . (int) ($run_summary['processed'] ?? 0) . ' copied=' . (int) ($run_summary['copied'] ?? 0));
			}
		}
		$this->page_data['batch_size'] = $batch_size;
		$this->page_data['sync_counts'] = $this->sync_science_crqs_counts();
		$this->page_data['run_summary'] = $run_summary;
		$this->load->view('admin/settings/sync_science_crqs', $this->page_data);
	}

	public function _valid_optional_datetime($value)
	{
		$value = trim((string) $value);
		if ($value === '') {
			return true;
		}

		$normalized = $value;
		if (strpos($normalized, 'T') !== false) {
			$normalized = str_replace('T', ' ', $normalized);
		}
		if (preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}$/', $normalized)) {
			$normalized .= ':00';
		}

		$dt = DateTime::createFromFormat('Y-m-d H:i:s', $normalized);
		$errors = DateTime::getLastErrors();
		$is_valid = $dt && is_array($errors) && empty($errors['warning_count']) && empty($errors['error_count']) && $dt->format('Y-m-d H:i:s') === $normalized;

		if (!$is_valid) {
			$this->form_validation->set_message('_valid_optional_datetime', 'The {field} must be a valid date/time.');
			return false;
		}

		return true;
	}

	public function companyUpdate()
	{

		ifPermissions('company_settings');

		postAllowed();
		
		$this->settings_model->updateByKey('company_name', post('company_name'));
		$this->settings_model->updateByKey('company_email', post('company_email'));
		$this->settings_model->updateByKey('spell', post('spell'));
		$this->settings_model->updateByKey('deadline', post('deadline'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Settings has been Updated Successfully');

		$this->activity_model->add("Company Settings Updated by User: #".logged('id'));
		
		redirect('admin/settings/company');
	}

	public function login_theme()
	{
		ifPermissions('login_theme');
		$this->page_data['page']->submenu = 'login_theme';
		$this->load->view('admin/settings/login_theme', $this->page_data);
	}

	public function loginthemeUpdate()
	{

		ifPermissions('login_theme');

		postAllowed();
		
		$this->settings_model->updateByKey('login_theme', post('login_theme'));

		if (!empty($_FILES['image']['name'])) {

			$path = $_FILES['image']['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$this->uploadlib->initialize([
				'file_name' => 'login-bg.'.$ext
			]);
			$image = $this->uploadlib->uploadImage('image');

			if($image['status']){
				$this->settings_model->updateByKey('bg_img_type', $ext);
			}

			$this->activity_model->add("User #$id Updated his/her Profile Image.");

			$this->session->set_flashdata('alert-type', 'success');
			$this->session->set_flashdata('alert', 'Profile Image has been Updated Successfully');

		}
		else{

			$this->session->set_flashdata('alert-type', 'success');
			$this->session->set_flashdata('alert', 'Server Error Occured while Uploading Image !');

		}

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Settings has been Updated Successfully');

		$this->activity_model->add("Login Theme Updated by User: #".logged('id'));
		
		redirect('admin/settings/login_theme');
	}

	public function email_templates()
	{
		ifPermissions('email_templates');
		$this->page_data['page']->submenu = 'email_templates';
		$this->load->view('admin/settings/email_templates/list', $this->page_data);
	}

	public function edit_email_templates($id)
	{
		ifPermissions('email_templates');
		$this->page_data['page']->submenu = 'email_templates';
		$this->page_data['template'] = $this->templates_model->getById($id);
		$this->load->view('admin/settings/email_templates/edit', $this->page_data);
	}

	public function update_email_templates($id)
	{

		ifPermissions('login_theme');

		postAllowed();
		
		$this->templates_model->update($id, [
			// 'code'	=>	post('code'),
			'name'	=>	post('name'),
			'data'	=>	post('data'),
		]);

		// dd( post('data') );

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Email Template has been Updated Successfully');

		$this->activity_model->add("Email Template Updated by User: #".logged('id'));
		
		redirect('admin/settings/email_templates');
	}

}

/* End of file Settings.php */
/* Location: ./application/controllers/Settings.php */
