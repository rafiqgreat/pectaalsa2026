<?php defined('BASEPATH') OR exit('No direct script access allowed');

class School extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->page_data['page']->title = 'School Management';
		$this->page_data['page']->menu = 'school';
		$this->load->library('functions');
		$this->load->library('PHPExcel');
		require_once APPPATH . 'libraries/PHPExcel/IOFactory.php';

		if (logged('role') != 1) {
			show_error('Unauthorized access', 403);
		}
	}

	public function index()
	{
		ifPermissions('school_list');
		$this->page_data['page']->submenu = '';

		$status = $this->input->get('school_status', true);
		$filters = [
			'q' => trim((string) $this->input->get('q', true)),
			'school_state_id' => $this->input->get('school_state_id', true) ?: '',
			'school_district_id' => $this->input->get('school_district_id', true) ?: '',
			'school_tehsil_id' => $this->input->get('school_tehsil_id', true) ?: '',
			'school_level' => $this->input->get('school_level', true) ?: '',
			'school_gender' => $this->input->get('school_gender', true) ?: '',
			'school_status' => ($status === '0' || $status === '1') ? $status : '',
		];

		$this->page_data['total'] = $this->school_model->count_filtered_schools($filters);
		$this->page_data['schools'] = $this->school_model->get_filtered_schools($filters);
		$this->page_data['filters'] = $filters;
		$this->page_data['states'] = $this->location_model->get_states();
		$this->page_data['districts'] = $this->location_model->get_districts();
		$this->page_data['tehsils'] = $this->location_model->get_tehsils();
		$this->load->view('admin/school/list', $this->page_data);
	}

	public function add()
	{
		ifPermissions('school_add');
		$this->page_data['page']->submenu = 'add';
		$this->page_data['states'] = $this->location_model->get_states();
		$this->page_data['districts'] = [];
		$this->page_data['tehsils'] = [];
		$this->load->view('admin/school/add', $this->page_data);
	}

	private function build_school_payload($with_password = false)
	{
		$district_id = post('school_district_id');
		$tehsil_id = post('school_tehsil_id');

		$district = !empty($district_id) ? $this->location_model->getById($district_id, 'districts', 'district_id') : null;
		$tehsil = !empty($tehsil_id) ? $this->location_model->getById($tehsil_id, 'tehsils', 'tehsil_id') : null;

		$data = [
			'username' => post('username'),
			'school_department' => post('school_department'),
			'school_code' => post('school_code'),
			'school_lsacode' => post('school_lsacode'),
			'school_name' => post('school_name'),
			'school_address' => post('school_address'),
			'school_state_id' => post('school_state_id'),
			'school_district_id' => $district_id ?: null,
			'school_district' => !empty($district->district_name_en) ? $district->district_name_en : null,
			'school_tehsil_id' => $tehsil_id ?: null,
			'school_tehsil' => !empty($tehsil->tehsil_name_en) ? $tehsil->tehsil_name_en : null,
			'school_level' => post('school_level'),
			'school_gender' => post('school_gender'),
			'school_area' => post('school_area'),
			'school_grade' => post('school_grade') !== false ? (int) post('school_grade') : 4,
			'school_students' => post('school_students') !== false && post('school_students') !== '' ? (int) post('school_students') : null,
			'school_status' => post('school_status') !== false ? (int) post('school_status') : 1,
		];

		if ($with_password && post('password') !== false && post('password') !== '') {
			$data['password'] = hash('sha256', post('password'));
		}

		return $data;
	}

	public function save()
	{
		ifPermissions('school_add');
		postAllowed();

		$data = $this->build_school_payload(true);
		$data['school_createdby'] = logged('id');
		$id = $this->school_model->create($data, 'schools');

		$this->activity_model->add('New School $' . $id . ' Created by User:' . logged('name'), logged('id'));
		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'New School Created Successfully');
		redirect('admin/school');
	}

	public function view($id)
	{
		ifPermissions('school_list');
		$this->page_data['page']->submenu = '';
		$this->page_data['school'] = $this->school_model->getById($id, 'schools', 'school_id');

		if (empty($this->page_data['school'])) {
			show_error('School not found', 404);
		}

		$this->page_data['state'] = $this->location_model->getById($this->page_data['school']->school_state_id, 'states', 'state_id');
		$this->page_data['district'] = $this->location_model->getById($this->page_data['school']->school_district_id, 'districts', 'district_id');
		$this->page_data['tehsil'] = $this->location_model->getById($this->page_data['school']->school_tehsil_id, 'tehsils', 'tehsil_id');
		$this->load->view('admin/school/view', $this->page_data);
	}

	public function edit($id)
	{
		ifPermissions('school_edit');
		$school = $this->school_model->getById($id, 'schools', 'school_id');
		if (empty($school)) {
			show_error('School not found', 404);
		}

		$this->page_data['states'] = $this->location_model->get_states();
		$this->page_data['districts'] = !empty($school->school_state_id)
			? $this->location_model->get_distirct_by_state($school->school_state_id)
			: [];
		$this->page_data['tehsils'] = !empty($school->school_district_id)
			? $this->location_model->get_tehsil_by_district($school->school_district_id)
			: [];
		$this->page_data['school'] = $school;
		$this->load->view('admin/school/edit', $this->page_data);
	}

	public function school_update($id)
	{
		ifPermissions('school_edit');
		postAllowed();

		$data = $this->build_school_payload(false);
		if (!empty(post('password'))) {
			$data['password'] = hash('sha256', post('password'));
		}

		$this->school_model->update($id, $data, 'schools', 'school_id');

		$this->activity_model->add('School $' . $id . ' Updated by User:' . logged('name'), logged('id'));
		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'School Updated Successfully');
		redirect('admin/school');
	}

	public function delete($id)
	{
		ifPermissions('school_delete');
		$id = $this->school_model->delete($id, 'schools', 'school_id');
		$this->activity_model->add("School #$id Deleted by User:" . logged('name'));
		$this->session->set_flashdata('alert-type', 'success');
		$this->session->set_flashdata('alert', 'School has been Deleted Successfully');
		redirect('admin/school');
	}

	public function change_status($id)
	{
		$this->school_model->update($id, ['school_status' => get('status') == 'true' ? 1 : 0], 'schools', 'school_id');
		echo 'done';
	}

	public function distirct_by_state()
	{
		echo json_encode($this->location_model->get_distirct_by_state($this->input->post('state_id')));
	}

	public function tehsil_by_district()
	{
		echo json_encode($this->location_model->get_tehsil_by_district($this->input->post('district_id')));
	}

	public function import()
	{
		if ($this->input->post('submit')) {
			if (empty($_FILES['import_file']['name'])) {
				$this->session->set_flashdata('alert-type', 'error');
				$this->session->set_flashdata('alert', 'File does not exist');
				redirect(base_url('school/import'), 'refresh');
			}

			$path = "assets/schools/";
			if (!empty($_FILES['import_file']['name'])) {
				$result = $this->functions->file_insert($path, 'import_file', 'excel', '9097152');
				if ($result['status'] == 1) {
					$data['import_file'] = $path . $result['msg'];
				} else {
					$this->session->set_flashdata('alert-type', 'error');
					$this->session->set_flashdata('alert', 'File did not uploaded');
					redirect(base_url('school/import'), 'refresh');
				}
			}

			$input_file_type = PHPExcel_IOFactory::identify($data['import_file']);
			$obj_reader = PHPExcel_IOFactory::createReader($input_file_type);
			$fileLoading = $obj_reader->load($data['import_file']);
			$fileLoading->getActiveSheet();

			$startIndex = 2;
			$fun_numb = '';

			while ($fileLoading->getActiveSheet()->getCell('A' . $startIndex)->getValue() != '') {
				$nun_numb = $fileLoading->getActiveSheet()->getCell('A' . $startIndex)->getValue() ?: '';
				$username_en = $fileLoading->getActiveSheet()->getCell('B' . $startIndex)->getValue() ?: '';
				$password_en = hash('sha256', $username_en);
				$school_name = $fileLoading->getActiveSheet()->getCell('C' . $startIndex)->getValue() ?: '';
				$school_address = $fileLoading->getActiveSheet()->getCell('D' . $startIndex)->getValue() ?: '';

				$exists = $this->school_model->username_exist($username_en);
				if ($exists) {
					$fun_numb = $nun_numb;
					$startIndex++;
				} else {
					if ($fun_numb != $nun_numb) {
						$sql = 'INSERT INTO schools (username, password, school_name, school_address, school_status, school_createdby) VALUES ("' . $username_en . '","' . $password_en . '","' . $school_name . '","' . $school_address . '","1","' . logged('id') . '")';
						$this->db->query($sql);
					}
					$fun_numb = $nun_numb;
					$startIndex++;
				}
			}

			$this->session->set_flashdata('alert-type', 'success');
			$this->session->set_flashdata('alert', 'File imported Successfully');
			redirect(base_url('school/import'));
		}

		ifPermissions('school_import');
		$this->page_data['page']->submenu = 'import';
		$this->load->view('admin/school/import', $this->page_data);
	}
}
