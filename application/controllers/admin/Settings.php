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
