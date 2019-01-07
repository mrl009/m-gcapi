<?php
/**
 * Created by PhpStorm.
 * 支付model
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Pay_model extends MY_Model
{

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取对应层级下面的支付方式
     *
    */
    public function get_method($level_id)
    {
        //
        $base_bank   = $this->base_bank_online('bank');
        $base_online = $this->base_bank_online('bank_online');

        //获取公司入款
        $str = "b.id,b.describe,b.bank_id,b.is_confirm,b.card_num,b.card_username,b.qrcode,b.card_address";
        $where  = [
            'b.status'   =>1,
            'a.level_id' => $level_id
        ];
        $where2 = [
            'join' => 'bank_card',
            'on'   => 'b.id = a.card_id',
            'orderby' => ['re_order'=>'desc','b.id' => 'desc']
        ];
        $temp   = $this->get_all($str, 'level_bank', $where, $where2);
        $bank = [];
        foreach ($temp as $k => $v) {
            $bank[$v['id']] = $v;
        }
        //获取线上支付 (显示方式按照后台设置的排序值排序，值越大约靠前)
        $ord = ['re_order'=>'desc','online_id'=>'desc','a.pay_code'=>'ASC'];
        $str = "a.pay_code pay_codex,b.id,b.bank_o_id,b.describe";
        $where2 = [
            'join' => 'bank_online_pay',
            'on'   => 'a,online_id = b.id',
            'orderby' => $ord
        ];
        $online = [];
        $temp = $this->get_all($str, 'level_bank_online', $where, $where2);
        $onlineDate = [];
        foreach ($temp as $k => $v) {
            if (!isset($base_online[$v['bank_o_id']])) {
                continue;
            }
            unset($base_online[$v['bank_o_id']]['id']);
            $data = array_merge($v, $base_online[$v['bank_o_id']]);
            array_push($onlineDate, $data);
        }

        $bankData = [];
        foreach ($bank as $k => $v) {
            if (isset($base_bank[$v['bank_id']])) {
                $data = array_merge($base_bank[$v['bank_id']], $v);
                array_push($bankData, $data);
            }

        }

        return ['bank' => $bankData,'online' => $onlineDate];
    }




    /***
     * 获取基础的银行和支付平台信息
     * $type
    */
    public function base_bank($type = null)
    {
        $where = [
            'status' =>1
        ];
        $data['online'] = [];
        $data['bank']   = [];

        $this->select_db('public');
        if (empty($type)) {
            $data['bank']   =  $this->get_all('', 'bank', $where);
            $data['online'] =  $this->get_all('', 'bank_online', $where);
        } elseif ($type == 'bank') {
            $data['bank']   =  $this->get_all('', 'bank', $where);
        } else {
            $data['online'] =  $this->get_all('', 'bank_online', $where);
        }
        $this->select_db('private');
        foreach ($data['online'] as $k => $v) {
            $data['online'][$v['id']] = $v ;
        }

        $temp = [];
        foreach ($data['bank'] as $k => $v) {
            $temp[$v['id']] = $v ;
        }
        $data['bank'] = $temp;
        return $data;
    }
}
