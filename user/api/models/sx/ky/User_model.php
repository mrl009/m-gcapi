<?php
//session_start();
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class User_model extends MY_Model {


    //操作用户金额
    public function oper_user_balance( $username, $amount, $platform_name )
    {
        $this->select_db('shixun');
        $sql = 'SELECT `balance` FROM `' . $platform_name . '_user` WHERE `g_username` = \'' . $username . '\'';
        $res = $this->db->query( $sql )->row_array();
        $balance = $res[ 'balance' ];
        $balance += $amount;

        if( $this->update_balance( $username, $balance, $platform_name ) )
        {
            return $balance;
        }
        else
        {
            return false;
        }
    }

    //注册会员
    public function add_user( $platform_name, $data )
    {
        $this->select_db('shixun_w');
        $this->db->insert( $platform_name . '_user', $data );
        return $this->db->insert( $platform_name . '_user', $data );
    }


    public function user_info( $username, $field = '*', $platform = 'ky' )
    {
        $this->select_db('shixun');
        return $this->db->select( $field )->where( 'g_username', $username )->get( $platform . '_user' )->row_array();
    }

    //获取用户余额
    public function get_balance( $username, $platform_name )
    {
        $this->select_db('shixun');
        return $this->db->select( 'balance' )->where( 'g_username', $username )->get( $platform_name . '_user' )->row_array();
    }
    //更改用户密码
    public function reset_password( $username, $password, $platform_name )
    {
        $this->select_db('shixun_w');
        return $this->db->where( 'g_username', $username )->update( $platform_name . '_user', [ 'g_password' => $password ] );
    }
    //冻结玩家
    public function freeze_player( $username, $status, $platform_name )
    {
        $this->select_db('shixun_w');
        return $this->db->where( 'g_username', $username )->update( $platform_name . '_user', [ 'status' => $status ] );
    }


}