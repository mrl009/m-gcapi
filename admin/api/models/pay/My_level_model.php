<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class My_level_model extends MY_Model
{
    private $tb = 'level';   

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
    }

    //更新层级数据
    public function update_level_data($sdata,$level_id)
    {
        $where['id'] = $level_id;
        $this->db->update($this->tb,$sdata,$where);
    }

    public function verify_name($level_name,$level_id)
    {
        $where['level_name'] = $level_name;
        $info = $this->get_one('id',$this->tb,$where);
        if (!empty($info) && (empty($level_id) || 
            ($level_id <> $info['id'])))
        {
            return 1;
        } else {
            return 0;
        }
    }

    public function get_is_default()
    {
        $where['is_default'] = 1;
        $info = $this->get_one('id',$this->tb,$where);
        $is_default = empty($info) ? 1 : 0;
        return $is_default;
    }

}


