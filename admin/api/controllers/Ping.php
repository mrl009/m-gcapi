<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ping extends MY_Controller {
	public function __construct(){
		parent::__construct();
	}

	public function ping()
	{
		$this->return_json(OK,'API连接成功');
	}
}
