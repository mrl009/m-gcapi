<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class User_model extends SX_Model
{

	public function user_exists( $platform_name, $username )
	{
		$this->select_db('shixun');
		return $this->db->where( 'g_username', $username )->from( $platform_name . '_user' )->count_all_results() > 0 ? true : false;
	}
    public function user_add($platform='ag',$insert_data){
        $this->select_db('shixun_w');
        return $this->write($platform.'_user',$insert_data);
    }
}