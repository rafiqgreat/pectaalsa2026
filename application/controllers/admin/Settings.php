<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

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
