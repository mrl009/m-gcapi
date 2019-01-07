<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Bank_model extends MY_Model
{
    private $tb = 'bank_card';
    private $ptb = 'bank';
    private $ltb = 'level_bank';
    

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {

    }

    //获取层级中的 公司入款数据 (添加数据时显示)
    public function get_level_a()
    {
        $sdata = $this->get_private_bank();
        if (empty($sdata)) return [];
        $sdata = $this->set_common_level_data($sdata);
        return $sdata;
    }


    //获取层级中的 公司入款数据 (添加数据时显示)
    public function get_level_e($level_id)
    {
        $sdata = $this->get_compare_level_data($level_id);
        if (empty($sdata)) return [];
        $sdata = $this->set_common_level_data($sdata);
        return $sdata;
    }

    //添加公司入款数据
    public function add_bank_data($bank_id,$level_id)
    {
        $sdata = $this->get_insert_data($bank_id,$level_id);
        $this->db->insert_batch($this->ltb,$sdata);
    }

    //更新公司入款数据
    public function update_bank_data($bank_id,$level_id)
    {
        //删除原先层级中数据
        $where['level_id'] = $level_id;
        $this->db->where($where)->delete($this->ltb);
        //新增数据层级数据
        if (!empty($bank_id))
        {
            $sdata = $this->get_insert_data($bank_id,$level_id);
            $this->db->insert_batch($this->ltb,$sdata);
        }
    }

    //获取公库中公司入款的数据
    private function get_public_bank()
    {
        $sdata = [];
        $this->select_db('public');
        $where['status'] = 1;
        $field = 'id,bank_name,is_qcode';
        $data = $this->get_all($field,$this->ptb,$where); 
        $this->select_db('private');
        //構造數據
        if (!empty($data))
        {
             foreach($data as $key => $val)
            {
                $sdata[$val['id']]['name'] = $val['bank_name'];
                $sdata[$val['id']]['is_qcode'] = $val['is_qcode'];
            }
        }
        return $sdata;
    }


    //获取私库中公司入款支付的数据
    private function get_private_bank()
    {
        $where['status'] = 1;
        $field = 'id,bank_id,card_num,card_username';
        $data = $this->get_all($field,$this->tb,$where);
        return $data;
    }
    
    //获取层级中对应的数据
    private function get_private_level_bank($level_id)
    {
        $field = 'a.id,a.bank_id,a.card_num,a.card_username';
        $where['a.status'] = 1;
        $where['b.level_id'] = $level_id;
        $where2['join'] = array(
            array('table' => $this->ltb .' as b', 'on' => 'a.id = b.card_id')
        );
        $data = $this->get_all($field,$this->tb,$where,$where2);
        //echo $this->db->last_query();
        return $data;
    }

    //把从数据库获取的数据 构造成需要的数据
    private function set_common_level_data($sdata)
    {
        //獲取公庫中銀行數據
        $base = $this->get_public_bank();
        //循環構造層級中需要格式數據
        foreach($sdata as $key => $val)
        {
            //公司入款中為掃碼入款的名稱加上掃碼名字
            $name = $val['card_username'];
            if (1 == $base[$val['bank_id']]['is_qcode'])
            {
                $name .= '/' . $base[$val['bank_id']]['name'];
            }
            $sdata[$key]['name'] = $name;
            $sdata[$key]['num'] = $val['card_num'];
            if (!empty($val['check']))
            {
                $sdata[$key]['check'] = $val['check'];
            } else {
                $sdata[$key]['check'] = '';
            }
            /**
             * 為防止數據庫中多表聯查字段混亂,
             * 此處使用循環賦值而不是SQL查詢起別名
             */
            unset($sdata[$key]['bank_id']);
            unset($sdata[$key]['card_num']);
            unset($sdata[$key]['card_username']);
        }
        return $sdata;
    }

    
    // 比较公司入款列表数据和实际已经选择的层级数据
    private function get_compare_level_data($level_id)
    {
        //獲取數據列表(不含層級)
        $sdata = $this->get_private_bank();
        //獲取數據列表(包含層級)
        $ldata = $this->get_private_level_bank($level_id);
        //如果包含層級數據
        if (!empty($ldata)) $check_id = array_column($ldata,'id');
        //循環構造數據
        foreach($sdata as $key => $val)
        {
            if(isset($check_id) && in_array($val['id'],$check_id))
            {
                $sdata[$key]['check'] = 'checked';
            } else {
                $sdata[$key]['check'] = '';
            }
        }
        return $sdata;
    }

    //根据传递的数据 bank_id 构造层级数据数据
    private function get_insert_data($bank_id,$level_id)
    {
        $sdata = [];
        foreach ($bank_id as $key => $val) 
        {
            $sdata[$key]['card_id'] = $val;
            $sdata[$key]['level_id'] = $level_id;
        }
        return $sdata;
    }
}


