<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class System_model extends GC_Model {
	/**
     * 获取域名
     * @auther frank
     * @return array
     **/
	public function get_domain()
	{
		$res = array();
		$domain = $this->get_list('domain','set_domain');
		foreach ($domain as $d) {
			$res[] = $d['domain'];
		}
		return $res;
	}

	/**
     * 检测版本更新
     * @auther frank
     * @return array
     **/
	public function get_version($app_type)
	{
		$where['app_type'] = $app_type;
		$where['is_last_version'] = 1;
		$data = $this->get_one('*','version',$where);
		return $data;
	}
	/**
     * 录入bug系统
     * @auther frank
     * @return bool
     **/
	public function add_bug($data)
	{
		return $this->write('bug',$data);
	}
}