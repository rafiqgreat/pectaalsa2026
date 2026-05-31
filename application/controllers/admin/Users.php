<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'Users Management';
		$this->page_data['page']->menu = 'users';
		$this->load->model('admin/Users_model', 'admin_users_model');
	}

	public function index()
	{
		ifPermissions('users_list');
		$this->load->library('pagination');
		$per_page = (int) $this->input->get('per_page', true);
		$allowed_per_page = [100, 200, 500];
		if (!in_array($per_page, $allowed_per_page, true)) {
			$per_page = 100;
		}
		$page = (int) $this->input->get('page');
		$page = $page > 0 ? $page : 1;
		$offset = ($page - 1) * $per_page;

		$filters = [
			'name' => (string) $this->input->get('name', true),
			'username' => (string) $this->input->get('username', true),
			'email' => (string) $this->input->get('email', true),
			'role_id' => (int) $this->input->get('role_id', true),
			'per_page' => $per_page,
		];

		$total = $this->admin_users_model->count_users_filtered($filters);
		$config = [
			'base_url' => url('admin/users'),
			'total_rows' => $total,
			'per_page' => $per_page,
			'page_query_string' => true,
			'query_string_segment' => 'page',
			'use_page_numbers' => true,
			'reuse_query_string' => true,
		];
		$this->pagination->initialize($config);

		$this->page_data['users'] = $this->admin_users_model->get_users_page_filtered($filters, $per_page, $offset);
		$this->page_data['pagination_links'] = $this->pagination->create_links();
		$this->page_data['roles'] = $this->roles_model->get();
		$this->page_data['filters'] = $filters;
		$this->load->view('admin/users/list', $this->page_data);
	}

	public function blacklisted()
	{
		ifPermissions('users_list');
		$this->page_data['page']->title = 'Blacklisted Users';
		$this->page_data['page']->menu = 'users';
		$this->page_data['blacklisted_users'] = $this->admin_users_model->get_blacklisted_users();
		$this->load->view('admin/users/blacklisted', $this->page_data);
	}

	public function add()
	{
		ifPermissions('users_add');
		$this->load->view('admin/users/add', $this->page_data);
	}

	// Alias for legacy/expected URL: /admin/users/create
	public function create()
	{
		$this->add();
	}

	public function save()
	{
		ifPermissions('users_add');
		postAllowed();

		$role = (int) post('role');
		if (in_array($role, [18, 19], true) && (int) logged('role') !== 1) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Only Administrator can create/update users for this role.');
			redirect('admin/users/add');
			return;
		}

		$subjects_json = null;
		if (in_array($role, [18, 19], true)) {
			$subjects = (array) $this->input->post('subjects', true);
			$subjects = array_values(array_unique(array_filter(array_map('trim', $subjects), function ($v) { return $v !== ''; })));
			if (count($subjects) < 1) {
				$this->session->set_flashdata('alert-type', 'danger');
				$this->session->set_flashdata('alert', 'Please select at least one subject.');
				redirect('admin/users/add?role=' . $role);
				return;
			}
			$subjects_json = json_encode($subjects);
		}

		$id = $this->users_model->create([
			'role' => $role,
			'name' => post('name'),
			'username' => post('username'),
			'email' => post('email'),
			'phone' => post('phone'),
			'address' => post('address'),
			'subjects' => ($this->db->field_exists('subjects', 'users') ? $subjects_json : null),
			'status' => (int) post('status'),
			'password' => hash( "sha256", post('password') ),
		]);

		if (!empty($_FILES['image']['name'])) {

			$path = $_FILES['image']['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$this->uploadlib->initialize([
				'file_name' => $id.'.'.$ext
			]);
			$image = $this->uploadlib->uploadImage('image', '/users');

			if($image['status']){
				$this->users_model->update($id, ['img_type' => $ext]);
			}else{
				copy(FCPATH.'uploads/users/default.png', 'uploads/users/'.$id.'.png');
			}

		}else{

			copy(FCPATH.'uploads/users/default.png', 'uploads/users/'.$id.'.png');

		}

		$this->activity_model->add('New User $'.$id.' Created by User:'.logged('name'), logged('id'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'New User Created Successfully');
		
		redirect('admin/users');

	}

	public function view($id)
	{

		ifPermissions('users_view');

		$this->page_data['User'] = $this->users_model->getById($id);
		$this->page_data['User']->role = $this->roles_model->getByWhere([
			'id'=> $this->page_data['User']->role
		])[0];
		$this->page_data['User']->activity = $this->activity_model->getByWhere([
			'user'=> $id
		], [ 'order' => ['id', 'desc'] ]);
		$this->load->view('admin/users/view', $this->page_data);

	}

	public function edit($id)
	{

		ifPermissions('users_edit');

		$this->page_data['User'] = $this->users_model->getById($id);
		$this->load->view('admin/users/edit', $this->page_data);

	}


	public function update($id)
	{

		ifPermissions('users_edit');
		
		postAllowed();

		$role = (int) post('role');
		if (in_array($role, [18, 19], true) && (int) logged('role') !== 1) {
			$this->session->set_flashdata('alert-type', 'danger');
			$this->session->set_flashdata('alert', 'Only Administrator can create/update users for this role.');
			redirect('admin/users/edit/' . (int) $id);
			return;
		}

		$subjects_json = null;
		if (in_array($role, [18, 19], true)) {
			$subjects = (array) $this->input->post('subjects', true);
			$subjects = array_values(array_unique(array_filter(array_map('trim', $subjects), function ($v) { return $v !== ''; })));
			if (count($subjects) < 1) {
				$this->session->set_flashdata('alert-type', 'danger');
				$this->session->set_flashdata('alert', 'Please select at least one subject.');
				redirect('admin/users/edit/' . (int) $id);
				return;
			}
			$subjects_json = json_encode($subjects);
		}

		$data = [
			'role' => $role,
			'name' => post('name'),
			'username' => post('username'),
			'email' => post('email'),
			'phone' => post('phone'),
			'address' => post('address'),
		];
		if ($this->db->field_exists('subjects', 'users')) {
			$data['subjects'] = $subjects_json;
		}

		$password = post('password');

		if(logged('id')!=$id)
			$data['status'] = post('status')==1;

		if(!empty($password))
			$data['password'] = hash( "sha256", $password );

		$id = $this->users_model->update($id, $data);

		if (!empty($_FILES['image']['name'])) {

			$path = $_FILES['image']['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$this->uploadlib->initialize([
				'file_name' => $id.'.'.$ext
			]);
			$image = $this->uploadlib->uploadImage('image', '/users');

			if($image['status']){
				$this->users_model->update($id, ['img_type' => $ext]);
			}

		}

		$this->activity_model->add("User #$id Updated by User:".logged('name'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'Client Profile has been Updated Successfully');
		
		redirect('admin/users');

	}

	public function check()
	{
		$email = !empty(get('email')) ? get('email') : false;
		$username = !empty(get('username')) ? get('username') : false;
		$notId = !empty($this->input->get('notId')) ? $this->input->get('notId') : 0;

		if($email)
			$exists = count($this->users_model->getByWhere([
					'email' => $email,
					'id !=' => $notId,
				])) > 0 ? true : false;

		if($username)
			$exists = count($this->users_model->getByWhere([
					'username' => $username,
					'id !=' => $notId,
				])) > 0 ? true : false;

		echo $exists ? 'false' : 'true';
	}

	public function delete($id)
	{

		ifPermissions('users_delete');

		if($id!==1 && $id!=logged($id)){ }else{
			redirect('/','refresh');
			return;
		}

		$id = $this->users_model->delete($id);

		$this->activity_model->add("User #$id Deleted by User:".logged('name'));

		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'User has been Deleted Successfully');
		
		redirect('admin/users');

	}

	public function archive_delete($id)
	{

		if (!hasPermissions('users_delete') && (int) logged('role') !== 15) {
			redirect('errors/permission_denied');
			return;
		}

		if ($id !== 1 && $id != logged($id)) {
			// continue
		} else {
			redirect('/', 'refresh');
			return;
		}

		$reason = trim((string) $this->input->get('reason', true));
		if ($reason === '') {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Delete reason is required.');
			redirect('admin/users');
			return;
		}

		$success = $this->users_model->archive_delete_user($id, [
			'deleted_by_user_id' => logged('id'),
			'delete_reason' => $reason,
			'deleted_from_ip' => $this->input->ip_address(),
			'delete_module' => 'admin/users',
		]);

		if ($success) {
			$this->activity_model->add("User #$id Archived by User:".logged('name'));
			$this->session->set_flashdata('alert-type', 'success');
			$this->session->set_flashdata('alert', 'User archived and deleted from live tables successfully');
		} else {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'Unable to archive user. Please check logs.');
		}

		redirect('admin/users');

	}

	public function mark_draft($id)
	{
		if ((int) logged('role') !== 1) {
			redirect('errors/permission_denied');
			return;
		}

		$user = $this->users_model->getById($id);
		if (empty($user)) {
			$this->session->set_flashdata('alert-type', 'error');
			$this->session->set_flashdata('alert', 'User not found.');
			redirect('admin/users');
			return;
		}

		$this->session->set_flashdata('alert-type', 'error');
		$this->session->set_flashdata('alert', 'Legacy draft reset is disabled in the cleaned project.');

		redirect('admin/users');
	}

	public function change_status($id)
	{
		$this->users_model->update($id, ['status' => get('status') == 'true' ? 1 : 0 ]);
		echo 'done';
	}
	
	public function complete_profile()
	{
		$this->load->library('session');
    	$user_data = $this->session->flashdata('user_data');;
		//print_r($user_data['username']);
		//die();
		if (!$user_data['username']) {
			redirect('admin/login'); // Redirect to login if not logged in
		}
		
		$this->load->view('admin/users/young_enterpeneur_application');
	}


}

/* End of file Users.php */
/* Location: ./application/controllers/Users.php */
