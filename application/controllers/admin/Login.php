<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Login extends CI_Controller
{
	public $data;
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set(setting('timezone'));
		if (!empty($this->db->username) && !empty($this->db->hostname) && !empty($this->db->database)) {
		} else {
			die('Database is not configured');
		}
		if (is_logged()) {
			redirect('admin/dashboard', 'refresh');
		}
		$this->data = [
			'assets' => assets_url(),
			'body_classes'	=> setting('login_theme') == '1' ? 'login-page login-background' : 'login-page-side login-background'
		];
	}
	public function index()
	{
		$this->load->view('admin/account/login', $this->data, FALSE);
	}
	public function check()
	{
		$this->load->library('form_validation');
		$this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[5]|xss_clean|callback_validate_username');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|xss_clean');
		$is_recaptcha_enabled = (setting('google_recaptcha_enabled') == '1');
		if ($is_recaptcha_enabled)
			$this->form_validation->set_rules('g-recaptcha-response', 'Google Recaptcha', 'callback_validate_recaptcha');
		if ($this->form_validation->run() == FALSE) {
			$this->index();
			return;
		}
		$username = post('username');
		$password = post('password');
		$attempt = $this->users_model->attempt(compact('username', 'password'));
		if ($attempt == 'valid') {
			// If Allowed, then retreive user row and login the user
			$user = $this->db->where('username', $username)->or_where('email', $username)->get($this->users_model->table)->row();
			if($user->role==1){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==7){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==8){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==9){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
			
			}elseif($user->role==10){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}
			elseif($user->role==11){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==12){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==13){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==14){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}elseif($user->role==15){	
				$this->users_model->login($user, post('remember_me'));
				redirect('admin/dashboard','refresh');
				
			}else{				
				$this->session->set_flashdata('message', 'You are not valid User.');
				$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
				$this->activity_model->add("User: ".logged('name').' Logged Out');
				$this->user_users_model->logout();
				redirect('admin/login','refresh');
			}


		} elseif ($attempt == 'invalid_password') {
			// Show Message if invalid password
			$this->session->set_flashdata('message', 'Invalid Password');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('admin/login', 'refresh');			
			return;
		} elseif ($attempt == 'not_allowed') {
			// Show Message if invalid password
			$this->session->set_flashdata('message', 'You are not allowed to Login ! Contact Admin');
			$this->session->set_flashdata('message_type', 'danger');
			redirect('admin/login', 'refresh');			
			return;
		} else {
			// if invalid value or false returned by $attempt
			$this->session->set_flashdata('message', 'Something went Wrong');
			$this->session->set_flashdata('message_type', 'success');
			redirect('admin/login', 'refresh');	
			return;
		}
		redirect('/', 'refresh');
	}
	public function validate_recaptcha($recaptchaResponse)
	{
		$userIp = $this->input->ip_address();
		$secret = setting('google_recaptcha_secretkey');
		$url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $recaptchaResponse . "&remoteip=" . $userIp;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		$status = json_decode($output, true);
		if ($status['success']) {
			return true;
		} else {
			$this->form_validation->set_message('validate_recaptcha', 'Google Recaptcha not valid !');
			return false;
		}
	}
	public function validate_username($username)
	{
		$table = $this->users_model->table;
		$this->db->where('username', $username);
		$this->db->or_where('email', $username);
		$query = $this->db->get($table);
		if ($query === false) {
			// Query failed (e.g., missing table/connection issue). Avoid fatal error.
			log_message('error', 'validate_username query failed: ' . $this->db->last_query());
			$this->form_validation->set_message('validate_username', 'System error. Please try again later.');
			return false;
		}
		$exists = $query->num_rows();
		if ($exists > 0) {
			return true;
		} else {
			$this->form_validation->set_message('validate_username', 'Invalid Username/Email');
			return false;
		}
	}
	public function forget()
	{
		$this->load->view('account/forget', $this->data, FALSE);
	}
	public function reset_password()
	{
		postAllowed();
		$this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[5]|xss_clean|callback_validate_username');
		if ($this->form_validation->run() == FALSE) {
			$this->forget();
			return;
		}
		$reset = $this->users_model->resetPassword(['username' => post('username')]);
		$this->data['message']	=	'Reset Link Sent to <a href="#">' . obfuscate_email($reset) . '</a> ! Please check your email';
		$this->data['message_type']	=	'info';
		if ($reset === 'invalid') {
			$this->data['message']	=	'Invalid Email/Username';
			$this->data['message_type']	=	'danger';
		}
		$this->forget();
	}
	public function new_password()
	{
		$reset_token = !empty(get('token')) ? get('token') : false;
		$user = $this->users_model->getByWhere(['reset_token' => $reset_token]);
		if (!$reset_token || !$user || empty($user)) {
			echo 'Invalid Request';
			redirect('login/forget', 'refresh');
			return;
		}
		$user = $user[0];
		$this->data['user']	=	$user;
		$this->load->view('account/reset_password', $this->data, FALSE);
	}
	public function set_new_password()
	{
		postAllowed();
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[5]');
		$this->form_validation->set_rules('password_confirm', 'Password Confirm', 'required|matches[password]');
		if ($this->form_validation->run() == FALSE) {
			$this->data['user']	=	$this->users_model->getByWhere(['reset_token' => post('token')])[0];
			$this->load->view('account/reset_password', $this->data, FALSE);
			return;
		}
		$reset_token = post('token');
		$user	=	$this->users_model->getByWhere(compact('reset_token'))[0];
		$this->users_model->update($user->id, [
			'password'	=>	hash("sha256", post('password')),
			'reset_token'	=>	'',
		]);
		$this->session->set_flashdata('message', 'New Password has been Updated, You can login now');
		$this->session->set_flashdata('message_type', 'success');
		redirect('login', 'refresh');
	}
	public function register()
	{
		$this->load->view('account/register', $this->data, FALSE);
	}
	public function register_user()
	{
		$this->load->library('form_validation');
		$this->load->database();
		$username = $this->input->post('username', TRUE);
		$email = $this->input->post('email', TRUE);
		if ($this->validate_username($username) || $this->validate_username($email)) {
			$this->session->set_flashdata('message', 'Registration failed. Username or Email already exists.');
			$this->session->set_flashdata('message_type', 'danger'); // 'success', 'info', 'warning', or 'danger'
			redirect('login/register'); // Redirect back to registration page
		}
		$this->form_validation->set_rules('fullName', 'Name', 'required|trim|min_length[3]|max_length[50]|is_unique[users.username]');
		$this->form_validation->set_rules('role', 'Role', 'required|trim');
		$this->form_validation->set_rules('mobileNumber', 'MobileNumber', 'required|trim');
		$this->form_validation->set_rules('username', 'Username', 'required|trim|min_length[3]|max_length[50]|is_unique[users.username]');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[users.email]');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
		$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');
		if ($this->form_validation->run() === FALSE) {
			// Validation failed
			echo json_encode(['status' => false, 'errors' => validation_errors()]);
			return;
		}
		$data = [
			'role' => $this->input->post('role', TRUE),
			'name' => $this->input->post('fullName', TRUE),
			'phone' => $this->input->post('mobileNumber', TRUE),
			'username' => $username,
			'email' => $email,
			'password' => hash("sha256", post('password')),
			'created_at' => date('Y-m-d H:i:s')
		];
		$this->db->insert('users', $data);
		if ($this->db->affected_rows() > 0) {
			//$user = $this->db->where('username', $username)->or_where('email', $username)->get($this->users_model->table)->row();
			$this->session->set_flashdata('message', 'Registered. Successfully.');
			$this->session->set_flashdata('message_type', 'success'); // 'success', 'info', 'warning', or 'danger'
			redirect('login'); // Redirect back to registration page
		} else {
			echo json_encode(['status' => false, 'message' => 'Registration failed.']);
		}
	}
	public function userlogin()
	{
		$this->load->view('user_dashboard', $this->data, FALSE);
	}
}
/* End of file Login.php */
/* Location: ./application/controllers/Admin/Login.php */
