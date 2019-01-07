<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Fast_model extends MY_Model
{
    private $tb = 'bank_fast_pay';
    private $ltb = 'level_bank_fast';
    //設置系統公用私鑰
    private $merch_private_key = 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAK3Tt1h5Dm10ky12mj0emD0jKsKoNXVUKlk43GghlRflZDeIEbn/US1TmBXVFCMElR6O0V5vshd7GaTsn55b4S4zOF4Hk1qqFMOlPl4z22ctCiiCiJ9ibrsG2+nv93NCIhaGbEES3g5RMJ76truzaLUvyYzRXaKDncUUb2Gu0MqxAgMBAAECgYA0DAt+0yhtv5T97OA74rhEvg3koQb4rY3Mj0j3aO7Ca+347qYYIgmFX91O1DEmVw3rS2oHM7yIaVSBXFRizzH568nhJ8A49kGb/7PG2xz+g6klcxGIhSeRlg4YeFx4l4S16ylStOflp0A4fZ+gBtlbUCiIVBsgBQXVrk4ValJ9LQJBAOC7YtxT12WEX7/h5vDNPFhm9+MRFkJENxFCB311K6yBrBsMrLrKMqsNiFhahyUNQLusWQREiBMTGnzhOnLfkcsCQQDGAywR4cTAiCr/ptH/agqf77dBA8WE7ntGlM2HW2dF7NiUr8ZBNpeMIAn+qJSlZ4UvTIN22Hjgh5q9SYJhc1XzAkAkUGNTMwEVWGSYfwpwUtmzd0ALIxGzt44mbcMEFNDv2SxUWqH2tQGm/lLP5CD+bbvOF7VyqRhL7MRU9ZgaQ+ItAkBZ6dDWId2Uy4Ay7E5JG57Ndy2QYSUMsrnZl/In95JShzTld1ef/ykboOTI9UXiQbqRer3rdmqVEh5qu3lvxM6PAkEA2CcyCxCq/Vg+JoNnUdoTId5S2aG9yhjZKtE4IZyzL+94IHr4S8uGdZ8uv8TZHVwL5y+JSW+uTMDd/tnQsRYW2w==';
    //設置系統公用公鑰
    private $merch_public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCt07dYeQ5tdJMtdpo9Hpg9IyrCqDV1VCpZONxoIZUX5WQ3iBG5/1EtU5gV1RQjBJUejtFeb7IXexmk7J+eW+EuMzheB5NaqhTDpT5eM9tnLQoogoifYm67Btvp7/dzQiIWhmxBEt4OUTCe+ra7s2i1L8mM0V2ig53FFG9hrtDKsQIDAQAB';

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {

    }

    //获取层级中的 直通车数据 (添加数据时显示)
    public function get_level_a()
    {
        $sdata = $this->get_private_fast();
        if (empty($sdata)) return [];
        $sdata = $this->set_common_level_data($sdata);
        return $sdata;
    }

    //获取层级中的 直通车数据 (编辑数据时显示)
    public function get_level_e($level_id)
    {
        $sdata = $this->get_compare_level_data($level_id);
        if (empty($sdata)) return [];
        $sdata = $this->set_common_level_data($sdata);
        return $sdata;
    }

    //添加直通车数据
    public function add_fast_data($fast_id,$level_id)
    {
        $sdata = $this->get_insert_data($fast_id,$level_id);
        $this->db->insert_batch($this->ltb,$sdata);
    }

    //更新直通车数据
    public function update_fast_data($fast_id,$level_id)
    {
        //删除原先层级中数据
        $where['level_id'] = $level_id;
        $this->db->where($where)->delete($this->ltb);
        //新增数据层级数据
        if (!empty($fast_id))
        {
            $sdata = $this->get_insert_data($fast_id,$level_id);
            $this->db->insert_batch($this->ltb,$sdata);
        }
    }

    //获取直通车的全部数据
    private function get_private_fast()
    {
        $where['status'] = 1;
        $field = 'id,platform_name as name,pay_code as code';
        $data = $this->get_all($field,$this->tb,$where);
        return $data;
    }

    //获取层级中对应的数据
    private function get_private_level_fast($level_id)
    {
        $sdata = [];
        $field = 'a.id,a.platform_name,a.pay_code AS code';
        $field .= ',b.pay_code AS pay_code';
        $where['a.status'] = 1;
        $where['b.level_id'] = $level_id;
        $where2['join'] = array(
            array('table' => $this->ltb .' as b', 'on' => 'a.id = b.fast_id')
        );
        $data = $this->get_all($field,$this->tb,$where,$where2);
        //echo $this->db->last_query();
        //構造數據
        foreach($data as $key => $val)
        {
            $sdata[$val['id']]['pay_code'][] = $val['pay_code']; 
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

    // 比较直通车列表数据和实际已经选择的层级数据
    private function get_compare_level_data($level_id)
    {
        //獲取數據列表(不含層級)
        $sdata = $this->get_private_fast();
        //獲取數據列表(包含層級)
        $ldata = $this->get_private_level_fast($level_id);
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


    //添加直通车数据时默认系统生成的商户数据
    public function get_merch_info()
    {
        $data = $this->set_merch_info();
        return $data;
    }

    //設定商戶號信息
    private function set_merch_info()
    {
        //商戶秘鑰生成規則
        $md5_key = md5(time());
        //商戶號生成規則
        $m = strtoupper($this->sn) . date('ymd') . substr(time(),8,2);
        $merch = "ZT{$m}";
        //構造系統生成的本機參數信息
        $data['merch'] = $merch;
        $data['pay_key'] = $md5_key;
        $data['pay_private_key'] = $this->merch_private_key;
        $data['pay_public_key'] = $this->merch_public_key;
        //返回系統生成的本機參數
        return $data;
    }

    //根据传递的数据 fast_id 构造层级数据数据
    private function get_insert_data($fast_id,$level_id)
    {
        $i = 1;
        $sdata = [];
        foreach($fast_id as $key => $val)
        {
            foreach($val as $v)
            {
                $sdata[$i]['fast_id'] = $key;
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
           '7' => '网银支付'
        ); 
        return $pay_code;
    }
}


