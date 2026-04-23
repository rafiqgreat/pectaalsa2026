<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_model extends MY_Model {

	public $table1 = 'states';
	public $table3 = 'tehsils';

	public function __construct()
	{
		parent::__construct();
	}
	
	public function get_states() 
	{
		$query = $this->db->select('*')
                  ->from($this->table1)
                  ->get();
		return $query->result();
    }
	
	public function get_districts() 
	{
		$query = $this->db->select('districts.*, states.state_name_en')
						  ->from('districts') // Ensure the correct table name is used
						  ->join('states', 'states.state_id = districts.district_state_id', 'left')
						  ->get();
		return $query->result();
	}

	
	public function get_tehsils() 
	{
		$query = $this->db->select('tehsils.*, districts.district_name_en, states.state_name_en')
                  ->from('tehsils')
				  ->join('districts', 'districts.district_id = tehsils.tehsil_district_id', 'left')
				  ->join('states', 'states.state_id = tehsils.tehsil_state_id', 'left')
                  ->get();
		return $query->result();
    }
	
	public function update_state($id, $data)
	{
		$this->db->where('state_id', $id);
		$this->db->update('states', $data);
		return $id;
	}
	
	public function update_district($id, $data)
	{
		$this->db->where('district_id', $id);
		$this->db->update('districts', $data);
		return $id;
	}
	
	public function update_tehsil($id, $data)
	{
		$this->db->where('tehsil_id', $id);
		$this->db->update('tehsils', $data);
		return $id;
	}
	
	public function get_distirct_by_state($id){
		$this->db->select('*')
				 ->from('districts')
				 ->where('district_state_id', $id)
				 ->where('district_status', 1);					 
		$query = $this->db->get();			
		return $result = $query->result_array();			
	}
	
	public function get_tehsil_by_district($id){
		$this->db->select('*')
				 ->from('tehsils')
				 ->where('tehsil_district_id', $id)
				 ->where('tehsil_status', 1);					 
		$query = $this->db->get();			
		return $result = $query->result_array();			
	}

}

/* End of file Permissions_model.php */
/* Location: ./application/models/Permissions_model.php */