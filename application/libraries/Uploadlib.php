<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Uploadlib {

    protected $ci;
    protected $Config;

    public function __construct()
    {
        $this->ci =& get_instance();

        // ✅ Always use absolute path
        $config['upload_path']   = FCPATH . 'uploads/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
        $config['overwrite']     = true;
        $config['remove_spaces'] = true;

        $this->Config = $config;
        $this->ci->load->library('upload', $config);
    }

    public function initialize($config = [])
    {
        $this->Config = array_merge($this->Config, $config);
        return $this->ci->upload->initialize($this->Config);
    }

    public function uploadImage($name = 'image', $path = '/')
    {
        $config = $this->Config;
        // ✅ Build final absolute upload path
        $config['upload_path'] = rtrim($config['upload_path'], '/') . '/' . trim($path, '/');

        // ✅ Ensure folder exists
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }

        $this->ci->upload->initialize($config);

        if (!$this->ci->upload->do_upload($name)) {
            return [
                'status' => false,
                'error'  => $this->ci->upload->display_errors()
            ];
        } else {
            return [
                'status' => true,
                'data'   => $this->ci->upload->data()
            ];
        }
    }

	// public function multiUploadImage($name = 'image', $path = '/')
	// {

	// 	$config = $this->Config;
	// 	$config['upload_path'] = $config['upload_path'].'/'.trim($path, '/');
	// 	$this->ci->upload->initialize($config);

	// 	if ( ! $this->ci->upload->do_upload($name)){
	// 		$return = array( 'status' => false, 'error' => $this->ci->upload->display_errors());
	// 	}
	// 	else{
	// 		$return = array( 'status' => true, 'data' => $this->ci->upload->data());
	// 	}

	// 	return $return;
	
	// }

}

/* End of file Uploadlib.php */
/* Location: ./application/libraries/Uploadlib.php */
