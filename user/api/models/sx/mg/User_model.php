<?php
//session_start();
defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH.'api/core/SX_Model.php';

class User_model extends SX_Model
{

	public function user_exists( $platform_name, $username )
	{
		$this->select_db('shixun');
		return $this->db->where( 'g_username', $username )->from( $platform_name . '_user' )->count_all_results() > 0 ? true : false;
	}
    //注册会员
    public function add_user( $platform_name = 'mg', $data )
    {
        $this->select_db('shixun_w');
        return  $this->db->insert( $platform_name . '_user', $data );
        //var_dump($this->db->last_query());exit();
    }
	public function update_userinfo( $platform_name, $data )
	{
		$this->select_db('shixun_w');
		return $this->db->where( 'g_username', $data['g_username'] )->update( $platform_name . '_user', $data );
	}
	public function insert_transfer( $platform_name, $data )
	{
		$this->select_db('shixun_w');
		return $this->db->insert( $platform_name . '_fund', $data );
	}

}