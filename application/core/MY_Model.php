<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model {

	/**
	  * Default Table Primary Key
	  */
	public $table_key = 'id';

	/**
	  * Get Data from table
	  *
	  * @return array Data
	  */
	public function get()
	{
		return $this->db->get($this->table)->result();
		//die($this->db->last_query());
	}

	/**
	  * Get Data from table by id
	  *
	  * @return object Data Ex. {}
	  */
	/*public function getById($id)
	{
		return $this->db->get_where($this->table, [ $this->table_key => $id ])->row();
	}*/
	
	public function getById($id, $table = null, $primaryKey = null)
	{
		$table = $table ?? $this->table;
		$primaryKey = $primaryKey ?? $this->table_key;
		return $this->db->get_where($table, [$primaryKey => $id])->row();
	}


	/**
	  * Get a particular field/row from table by its primary key eg. id
	  *
	  * @param int $id Primary Key Example - id 
	  * @param string $row coloumn name Example - name 
	  * 
	  * @return string
	  * 
	  */

	public function getRowById($id, $row)
	{
		return $this->db->get_where($this->table, [ $this->table_key => $id ])->row()->{$row};
		//die($this->db->last_query());
	}

	/**
	  * Create/Insert the row in Table
	  * 
	  * @param array $data
	  *
	  * @return int Inserted Id
	  */
	/*function create($data)
	{
		$this->db->insert($this->table, $data);
		return $this->db->insert_id();
	}*/
	
	public function create($data, $table = null)
	{
		//$table = $table ?? $this->table; // Use provided table name or default table
		$table = $table ? $table : $this->table;
		$this->db->insert($table, $data);
		return $this->db->insert_id();
		//die($this->db->last_query());
	}
	

	/**
	  * Create/Insert the multiple rows in Table
	  * 
	  * @param array $data
	  *
	  * @return int Inserted Id
	  */
	function create_batch($data)
	{
		$this->db->insert_batch($this->table, $data);
		return $this->db->insert_id();
	}

	/**
	  * Update the row in Table by id
	  * 
	  * @param array $data
	  *
	  * @return int Updated Id
	  */
	function update($id, $data, $table = null, $pri_key = null)
	{
		$table = $table ? $table : $this->table;
		$tid = $pri_key ? $pri_key : 'id';
		$this->db->where($tid, $id);
		$this->db->update($table, $data);
		return $id;
	}

	/**
	  * Delete the row in Table by id
	  * 
	  * @param int $id
	  *
	  * @return boolean true
	  */
	function delete($id, $table = null, $primaryKey=null)
	{
		$table = $table ? $table : $this->table;
		$primaryKey = $primaryKey ? $primaryKey : 'id';
		$this->db->where($primaryKey, $id);
		$this->db->delete($table);
		return true;
	}
	
	/*
	function delete($id)
	{
		$this->db->where('id', $id);
		$this->db->delete($this->table);
		return true;
	}
	*/


	/**
	  * Get Data Using Where condition from Table
	  * Quick Function to extract information from table
	  * 
	  * @param array $whereArg
	  * @param array $args Other conditions like order
	  *
	  * @return boolean true
	  */
	public function getByWhere($whereArg, $args = [])
	{
		if(isset($args['order']))
			$this->db->order_by($args['order'][0], $args['order'][1]);

		return $this->db->get_where($this->table, $whereArg)->result();
	}

	/**
	  * Predict Id of table using simple algo
	  * 
	  * @return int
	  */
	public function predictId()
	{
		$this->db->order_by($this->table_key, 'desc');
		return ($query = $this->db->get_where($this->table)) && $query->num_rows() > 0 ? $query->row()->id + 1 : 1;
	}

	/**
	  * Return the total number of rows in the table
	  * 
	  * @return int
	  */
	public function countAll()
	{
		return $this->db->count_all_results($this->table);
	}

}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */