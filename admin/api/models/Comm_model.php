<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Comm_model extends GC_Model {
	/**
     * 获取一个用户的信息
     * @auther frank
     * @return array
     **/
	public function get_one_user($username='',$field='*')
	{
		if(is_numeric($username)){
			$where['id'] = $username;
		}
		else{
			$where['username'] = $username;
		}
		return $this->get_one($field,'user',$where);
	}
}