<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller{

	public function index()
	{
		$this->load->model('Auth_model');
		$data = $this->Auth_model->get_auth($this->user['username']);
		//$data = $this->Auth_model->get_auth('ios24');
		$this->return_json(OK,$data);
	}
}
