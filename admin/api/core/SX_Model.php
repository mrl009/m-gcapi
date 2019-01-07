<?php
if (!defined('BASEPATH')) {
	exit('No direct access allowed.');
}


class SX_Model extends MY_Model
{
	public $db_shixun = null;

	public function __construct()
	{
		parent::__construct();
	}
}
