<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Online_model extends MY_Model
{
    private $tb = 'bank_online_pay';
    private $ptb = 'bank_online';
    private $ltb = 'level_bank_online';

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {

    }

    //获取层级中的 第三方支付数据 (添加层级数据时显示)
    public function get_level_a()
    {
        $sdata = $this->get_private_online();
        if (empty($sdata)) return [];
        $sdata = $this->set_common_level_data($sdata);
        return $sdata;
    }  

    //获取层级中的 第三方支付数据 (编辑层级数据时显示)
    public function get_level_e($level_id)
    {
        $sdata = $this->get_compare_level_data($level_id);
        if (empty($sdata)) return [];
        $sdata = $this->set_common_level_data($sdata);
        return $sdata;
    }

    //添加第三方支付数据
    public function add_online_data($online_id,$level_id)
    {
        $sdata = $this->get_insert_data($online_id,$level_id);
        $this->db->insert_batch($this->ltb,$sdata);
    }

    //更新第三方支付数据
    public function update_online_data($online_id,$level_id)
    {
        //删除原先层级中数据
        $where['level_id'] = $level_id;
        $this->db->where($where)->delete($this->ltb);
        //新增数据层级数据
        if (!empty($online_id))
        {
            $sdata = $this->get_insert_data($online_id,$level_id);
            $this->db->insert_batch($this->ltb,$sdata);
        }
    }

    //获取公库中在线支付的数据
    private function get_public_online()
    {
        $sdata = [];
        $this->select_db('public');
        $where['status'] = 1;
        $field = 'id,online_bank_name,pay_code';
        $data = $this->get_all($field,$this->ptb,$where); 
        $this->select_db('private');
        //構造數據
        if (!empty($data))
        {
            foreach($data as $key => $val)
            {
                //$code = explode(',',$val['pay_code']);
                $sdata[$val['id']]['name'] = $val['online_bank_name'];
                $sdata[$val['id']]['code'] = $val['pay_code'];
            }
        }
        return $sdata;
    }
    
    //获取私库中在线支付的数据
    private function get_private_online()
    {
        $where['status'] = 1;
        $field = 'id,bank_o_id';
        $data = $this->get_all($field,$this->tb,$where);
        //構造數據
        if (!empty($data))
        {
            $base = $this->get_public_online();
            foreach($data as $key => $val)
            {
                $name = $base[$val['bank_o_id']]['name'];
                $code = $base[$val['bank_o_id']]['code'];
                $data[$key]['name'] = $name;
                $data[$key]['code'] = $code;
                unset($data[$key]['bank_o_id']);
            }
        }
        return $data;
    }

    //获取层级中对应的数据
    private function get_private_level_online($level_id)
    {
        $sdata = [];
        $field = 'a.id,a.bank_o_id,b.pay_code';
        $where['a.status'] = 1;
        $where['b.level_id'] = $level_id;
        $where2['join'] = array(
            array('table' => $this->ltb .' as b', 'on' => 'a.id = b.online_id')
        );
        $data = $this->get_all($field,$this->tb,$where,$where2);
        //echo $this->db->last_query();
        //構造數據
        if (!empty($data))
        {
            foreach($data as $key => $val)
            {
                $sdata[$val['id']]['pay_code'][] = $val['pay_code'];
            }
        }
        return $sdata;
    }

    //把从数据库获取的数据 构造成需要的数据
    private function set_common_level_data($sdata)
    {
        $ot = [];
        $pay_code = $this->set_pay_code();
        //循環構造層級中需要格式數據
        foreach($sdata as $key => $val)
        {
            $code = explode(',',$val['code']);
            foreach($code as $v)
            {
                $ot[$v]['code'] = $v;
                $ot[$v]['name'] = $pay_code[$v];
                if (!empty($val['pay_code']) && 
                   in_array($v,$val['pay_code']))
                {
                    $ot[$v]['check'] = 'checked';
                } else {
                    $ot[$v]['check'] = '';
                }
            }
            $sdata[$key]['code'] = $ot;
            $ot = [];
            unset($sdata[$key]['pay_code']);
        }
        return $sdata;
    }

    // 比较第三方支付列表数据和实际已经选择的层级数据
    private function get_compare_level_data($level_id)
    {
        //獲取數據列表(不含層級)
        $sdata = $this->get_private_online();
        //獲取數據列表(包含層級)
        $ldata = $this->get_private_level_online($level_id);
        //如果包含層級數據
        foreach($sdata as $key => $val)
        {
            //判斷層級中是是否有已经选择支付方式的數據
            if (isset($ldata[$val['id']]))
            {
                $sdata[$key]['pay_code'] = $ldata[$val['id']]['pay_code'];
            } else {
                $sdata[$key]['pay_code'] = '';
            }
        }
        return $sdata;
    }

    //根据传递的数据 online_id 构造层级数据数据
    private function get_insert_data($online_id,$level_id)
    {
        $i = 1;
        $sdata = [];
        foreach($online_id as $key => $val)
        {
            foreach($val as $v)
            {
                $sdata[$i]['online_id'] = $key;
                $sdata[$i]['level_id'] = $level_id;
                $sdata[$i]['pay_code'] = $v;
                $i++;
            }
        }
        return $sdata;
    }

    //设置支付code对应关系
    private function set_pay_code()
    {
        $pay_code = array(
           '1' => '微信扫码支付(web跳转)',
           '2' => '微信WAP',
           '4' => '支付宝扫码支付(web跳转)',
           '5' => '支付宝WAP',
           '7' => '网银支付',
           '8' => 'QQ钱包扫码支付(web跳转)',
           '9' => '京东钱包扫码支付(web跳转)',
           '10' => '百度钱包扫码支付(web跳转)',
           '12' => 'QQ钱包WAP',
           '13' => '京东钱包WAP',
           '15' => '京东钱包公众号二维码',
           '16' => 'QQ钱包公众号二维码',
           '17' => '云闪付/银联钱包扫码支付(web跳转)',
           '18' => '云闪付/银联钱包WAP',
           '19' => '银联钱包公众号二维码',
           '20' => '百度钱包WAP',
           '21' => '百度钱包公众号二维码',
           '22' => '财付通扫码支付(web跳转)',
           '23' => '财付通WAP',
           '24' => '财付通公众号二维码',
           '25' => '快捷支付/信用卡(web跳转)',
           '26' => '收银台/聚合付(web跳转)',
           '33' => '微信公众号二维码',
           '36' => '支付宝公众号二维码',
           '38' => '苏宁钱包扫码支付(web跳转)',
           '39' => '苏宁钱包WAP',
           '40' => '微信条形码',
           '41' => '支付宝条形码'
        ); 
        return $pay_code;
    }
}


