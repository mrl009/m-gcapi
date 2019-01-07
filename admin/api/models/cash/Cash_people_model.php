<?php
/**
 * @模块   人工存取款
 * @版本   Version 1.0.0
 * @日期   2017-04-04
 * super
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cash_people_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    //获得管理员信息
    public function get_admin_info($data){
        $where = "name = '{$data}' or username = '{$data}'";
        $info = $this->db->select('id')->from('admin')->where($where)->get()->row_array();
        return $info;
    }
}