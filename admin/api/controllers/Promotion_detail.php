<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Promotion_detail控制器
 * 
 * @author      ssm
 * @package     controllers
 * @version     v1.0 2017/10/16
 */
class Promotion_detail extends MY_Controller
{
	public function __construct()
    {
        parent::__construct();
        $this->load->model('Promotion_detail_model', 'core');
    }

    /**
	 * 获取全部数据
	 *
	 * @access public
	 * @return Array
	 */
	public function all()
	{
		$query = [
			'username' 	=> $this->G('username'),
			'start_date' => $this->G('start_date'),
			'end_date' 	=> $this->G('end_date'),
			'page' 		=> $this->G('page'),
			'rows' 		=> $this->G('rows'),
		];
		$result = $this->core->all($query);
		$this->return_json(OK, $result);
	}



}