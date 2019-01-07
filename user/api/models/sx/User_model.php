<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends GC_Model
{
    public function __construct()
    {
        $this->select_db('shixun');
    }

    public function get_user_info($username, $platform_name)
    {
        return $this->db->select('id,sn,snuid')->where('g_username', $username)->get($platform_name . '_user')->row_array();
    }

    public function getUserById($id, $platform)
    {
        return $this->db->where('id', $id)->select('g_username,g_password')->get($platform . '_user')->row_array();
    }

    public function getCashRecord($username, $platform)
    {
        return $this->db->where('gc_username', $username)->order_by('add_time desc')->get($platform . '_fund')->row_array();
    }
}