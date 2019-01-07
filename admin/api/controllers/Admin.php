<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Controller {

	public function index()
	{
		unset($this->admin['pwd']);
		unset($this->admin['addtime']);
		unset($this->admin['update_time']);
		unset($this->admin['ip']);
		$this->return_json(OK,$this->admin);
	}
}