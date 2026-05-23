<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Login extends CI_Controller {
	public $data;

	private function getRegistrationRoles()
	{
		return $this->db->select('id, title')
			->from('roles')
			->where('id !=', 1)
			->order_by('id', 'ASC')
			->get()
			->result();
	}

	private function isValidRegistrationRole($roleId)
	{
		return $this->db->select('id')
			->from('roles')
			->where('id', (int) $roleId)
			->where('id !=', 1)
			->limit(1)
			->get()
			->num_rows() === 1;
	}

	private function getUserAccessBlockedMessage()
	{
		return user_access_block_message();
	}
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set( setting('timezone') );
		
		if( !empty($this->db->username) && !empty($this->db->hostname) && !empty($this->db->database) ){ }else{
			die('Database is not configured');
		}
		if (is_logged() && user_access_blocked() && (int) $this->session->userdata('logged')['role'] !== 1) {
			$this->session->unset_userdata('login');
			$this->session->unset_userdata('logged');
			delete_cookie('login');
			delete_cookie('login_token');
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
		}
		if(is_logged()){
			$role = (int) ($this->session->userdata('logged')['role'] ?? 0);
			// Only role 2 users should access the user area; admin should stay in admin area.
			if ($role === 2) {
				redirect('user/dashboard','refresh');
			}
			redirect('admin', 'refresh');
		}
		$this->data = [
			'assets' => assets_url(),
			'body_classes'	=> setting('login_theme') == '1' ? 'login-page login-background' : 'login-page-side login-background',
			'user_access_blocked' => user_access_blocked(),
			'user_access_block_message' => $this->getUserAccessBlockedMessage()
		];
	}
	public function index()
	{
		$this->load->view('user/account/login', $this->data, FALSE);
	}

	public function check()
	{
		if (user_access_blocked()) {
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login', 'refresh');
			return;
		}
        $this->load->library('form_validation');
        $this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[5]|xss_clean|callback_validate_username');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|xss_clean');
        $is_recaptcha_enabled = (setting('google_recaptcha_enabled') == '1');
        if($is_recaptcha_enabled)
        	$this->form_validation->set_rules('g-recaptcha-response', 'Google Recaptcha', 'callback_validate_recaptcha');
        if ($this->form_validation->run() == FALSE)
        {
            $this->index();
            return;
        }
        $username = post('username');
        $password = post('password');
        $attempt = $this->user_users_model->attempt( compact('username', 'password') );
		if( $attempt=='valid' ){
        	// If Allowed, then retreive user row and login the user
			$user = $this->db->where( 'username', $username )->or_where( 'email', $username )->get( $this->user_users_model->table )->row();
			$this->session->set_userdata('show_popup', true);

			if($user->role!=1){	
				$this->user_users_model->login($user, post('remember_me'));
				redirect('user/dashboard','refresh');
				
			}else{				
				$this->session->set_flashdata('message', 'You are not valid User.');
				$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
				$this->activity_model->add("User: ".logged('name').' Logged Out');
				$this->user_users_model->logout();
				redirect('user/login','refresh');
			}
        }elseif( $attempt=='invalid_password' ){	
			$this->session->set_flashdata('message', 'Invalid Password.');
			$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
			redirect('user/login'); // Redirect back to registration page
        }elseif( $attempt=='not_allowed' ){
			$this->session->set_flashdata('message', 'You are not allowed to Login ! Contact Admin.');
			$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
			redirect('user/login'); // Redirect back to registration page
        }else{
			$this->session->set_flashdata('message', 'Something Went Wrong !');
			$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
			redirect('user/login'); // Redirect back to registration page			
        }
        redirect('/','refresh');
	}
	public function validate_recaptcha($recaptchaResponse)
	{
		
		$userIp=$this->input->ip_address();
        $secret = setting('google_recaptcha_secretkey');
        $url="https://www.google.com/recaptcha/api/siteverify?secret=".$secret."&response=".$recaptchaResponse."&remoteip=".$userIp;
 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);      
         
        $status= json_decode($output, true);
 
        if ($status['success']) {
			return true;
		}else{
			$this->form_validation->set_message('validate_recaptcha', 'Google Recaptcha not valid !');  
			return false;
		}
	}
	public function validate_username($username)
	{
		$table = $this->user_users_model->table;
		$this->db->where('username', $username);
		$this->db->or_where('email', $username);
		$exists = $this->db->get($table)->num_rows();
		
		if($exists > 0){
			return true;
		}else{
			// $this->form_validation->set_message('validate_username', 'Invalid Username/Email');  
			return false;
			//$this->session->set_flashdata('message', 'Invalid Username/Email');
//			$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
//			redirect('login'); // Redirect back to registration page
		}
	}
	public function forget()
	{
		if (user_access_blocked()) {
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login', 'refresh');
			return;
		}
		$this->load->view('user/account/forget', $this->data, FALSE);
	}
	public function reset_password()
	{ 
		if (user_access_blocked()) {
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login', 'refresh');
			return;
		}
		$this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[5]|xss_clean');
		
		postAllowed();
		//die('postAllowed');
		if($this->form_validation->run() == FALSE){
			$this->session->set_flashdata('message', 'Invalid Username/Email');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login/forget'); // Redirect to login/forget if validation fails
			return;
		}
		
		$reset = $this->user_users_model->resetPassword( [ 'username' => post('username') ] );
		
		$this->data['message']	=	'Reset Link Sent to <a href="#">'.obfuscate_email($reset).'</a> ! Please check your email';
		$this->data['message_type']	=	'info';
		if($reset==='invalid'){
			$this->session->set_flashdata('message', 'Invalid Username/Email');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login/forget'); // Redirect to login/forget if validation fails
			return;
		}
		$this->forget();
	}
	public function new_password()
	{
		if (user_access_blocked()) {
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login', 'refresh');
			return;
		}
		$reset_token = !empty(get('token')) ? get('token') : false;
		$user = $this->user_users_model->getByWhere(['reset_token' => $reset_token]);
		if(!$reset_token || !$user || empty($user)){
			echo 'Invalid Request';
			redirect('user/login/forget', 'refresh'); return;
		}
		$user = $user[0];
		$this->data['user']	=	$user;
		$this->load->view('user/account/reset_password', $this->data, FALSE);
	}
	public function set_new_password()
	{
		if (user_access_blocked()) {
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login', 'refresh');
			return;
		}
		postAllowed();
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[5]');
		$this->form_validation->set_rules('password_confirm', 'Password Confirm', 'required|matches[password]');
		if($this->form_validation->run() == FALSE){
			$this->data['user']	=	$this->user_users_model->getByWhere(['reset_token' => post('token')])[0];
			$this->load->view('user/account/reset_password', $this->data, FALSE);
			return;
		}
		$reset_token = post('token');
		$user	=	$this->user_users_model->getByWhere(compact('reset_token'))[0];
		$this->user_users_model->update($user->id, [
			'password'	=>	hash( "sha256", post('password') ),
			'reset_token'	=>	'',
		]);
		$this->session->set_flashdata('message', 'New Password has been Updated, You can login now');
		$this->session->set_flashdata('message_type', 'success');
		redirect('user/login', 'refresh');
	}
	
	public function register()
	{
		if (user_access_blocked()) {
			$this->session->set_flashdata('message', $this->getUserAccessBlockedMessage());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('user/login', 'refresh');
			return;
		}

		$registration_is_open = $this->settings_model->registration_is_open();
		$close_at_raw = $this->settings_model->get_registration_close_at();
		$close_at_display = null;
		if (!empty($close_at_raw)) {
			$dt = DateTime::createFromFormat('Y-m-d H:i:s', $close_at_raw);
			if ($dt) {
				$close_at_display = $dt->format('d-m-Y h:i A');
			}
		}
		$this->data['registration_roles'] = $this->getRegistrationRoles();
		$this->data['registration_is_open'] = $registration_is_open;
		$this->data['registration_close_at_raw'] = $close_at_raw;
		$this->data['registration_close_at_display'] = $close_at_display;
		$this->load->view('user/account/register', $this->data, FALSE);
	}
	
	public function appstatus()
	{
		$this->load->view('user/account/appstatus', $this->data, FALSE);
	}

	public function register_user()
	{
		if (!$this->settings_model->registration_is_open()) {
			$close_at_raw = $this->settings_model->get_registration_close_at();
			$close_at_display = null;
			if (!empty($close_at_raw)) {
				$dt = DateTime::createFromFormat('Y-m-d H:i:s', $close_at_raw);
				if ($dt) {
					$close_at_display = $dt->format('d-m-Y h:i A');
				}
			}

			$message = 'Registration is closed.';
			if (!empty($close_at_display)) {
				$message .= ' The last date for registration was: ' . $close_at_display;
			}
			$this->session->set_flashdata('message_type', 'danger');
			$this->session->set_flashdata('message', $message);
			redirect(base_url('user/login/register'), 'refresh');
			return;
		}

		$this->load->library('form_validation');
		$this->load->database();
		
		$raw_username = (string) $this->input->post('username', TRUE);
		$username = preg_replace('/\D+/', '', $raw_username);
		$_POST['username'] = $username;

		$email = $this->input->post('email', TRUE);
		$role = (int) $this->input->post('role', TRUE);

		$this->form_validation->set_rules('fullName', 'Name', 'required|trim|min_length[3]|max_length[50]|is_unique[users.username]');
		$this->form_validation->set_rules('role', 'Role', 'required|trim');
		$this->form_validation->set_rules('mobileNumber', 'MobileNumber', 'required|trim');
		$this->form_validation->set_rules('username', 'CNIC', 'required|trim|regex_match[/^[0-9]{13}$/]|callback_unique_cnic_for_registration');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[users.email]');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
		$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');
		
		if ($this->form_validation->run() == FALSE) {
			$data = array(
				'errors' => validation_errors()
			);
			$this->session->set_flashdata('old', $this->input->post(NULL, true));
			$this->session->set_flashdata('message_type', 'danger');
			$this->session->set_flashdata('message', $data['errors']);
			redirect(base_url('user/login/register'),'refresh');
		}

		if (!$this->isValidRegistrationRole($role)) {
			$this->session->set_flashdata('old', $this->input->post(NULL, true));
			$this->session->set_flashdata('message_type', 'danger');
			$this->session->set_flashdata('message', 'Selected role is invalid.');
			redirect(base_url('user/login/register'),'refresh');
		}
		
		$data = [
			'role' => $role,
			'name' => $this->input->post('fullName', TRUE),
			'phone' => $this->input->post('mobileNumber', TRUE),
			'username' => $username,
			'email' => $email,
			'password' => hash( "sha256", post('password') ),
			'created_at' => date('Y-m-d H:i:s')
		];
		
		$this->db->insert('users', $data);
		if ($this->db->affected_rows() > 0) {
			//$user = $this->db->where('username', $username)->or_where('email', $username)->get($this->user_users_model->table)->row();
			$this->session->set_flashdata('message', 'Registered. Successfully.');
			$this->session->set_flashdata('message_type', 'success'); // 'success', 'info', 'warning', or 'danger'
			redirect('user/login'); // Redirect back to registration page
		} else {
			echo json_encode(['status' => false, 'message' => 'Registration failed.']);
		}
	}

	public function unique_cnic_for_registration($cnic)
	{
		$cnic = preg_replace('/\D+/', '', (string) $cnic);
		if (strlen($cnic) !== 13) {
			$this->form_validation->set_message('unique_cnic_for_registration', 'CNIC must be exactly 13 digits.');
			return false;
		}

		$variants = [$cnic];
		$dashed = substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
		$variants[] = $dashed;

		// Always enforce uniqueness in users.username
		$user_exists = $this->db->select('id')
			->from('users')
			->where_in('username', $variants)
			->limit(1)
			->get()
			->num_rows() > 0;
		if ($user_exists) {
			$this->form_validation->set_message('unique_cnic_for_registration', 'This CNIC is already registered. Please login.');
			return false;
		}

		$role = (int) $this->input->post('role', TRUE);
		if (!$this->isValidRegistrationRole($role)) {
			$this->form_validation->set_message('unique_cnic_for_registration', 'Selected role is invalid.');
			return false;
		}

		return true;
	}
	
	/*public function userlogin()
	{
		$this->load->view('user_dashboard', $this->data, FALSE);
	}
*/
}
/* End of file Login.php */
/* Location: ./application/controllers/Admin/Login.php */
