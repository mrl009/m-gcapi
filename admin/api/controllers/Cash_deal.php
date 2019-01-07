<?php
/**
 * Created by PhpStorm.
 * User: wuya
 * Date: 2018/7/26
 * Time: 15:05
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Cash_deal extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/Cash_common_model', 'cash');
    }

    /*
     * 自动更新 过期未处理的 公司入款 线上入款
     */
    public function update_notdeal_status($dsn)
    {
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->cash->init($dsn);
        $gcSet = $this->cash->get_gcset(['incompany_timeout']);
        $now = time();
        $time = $now - $gcSet['incompany_timeout']*60;
        $in_company_ids = $this->cash->db->select('id')
            ->from('cash_in_company')
            ->where('addtime <',$time)
            ->where('status = 1')
            ->get()->result_array();
        $in_company_ids = array_column($in_company_ids,'id');
        $in_online_ids = $this->cash->db->select('id')
            ->from('cash_in_online')
            ->where('addtime <',$time)
            ->where('status = 1')
            ->get()->result_array();
        $in_online_ids = array_column($in_online_ids,'id');
        // 公司入款
        if ($in_company_ids) {
            $data = [
                'status' => 3,
                'update_time' => $now,
                'remark' => '过期未处理,自动取消'
            ];
            $ret = $this->cash->db->where_in('id',$in_company_ids)
                ->update('cash_in_company',$data);
            if ($ret) {
                @wlog(APPPATH.'logs/cash_deal_'.$this->cash->sn.'_'.date('Ym').'.log', "自动取消公司入款过期未处理订单 ".json_encode($in_company_ids)." 成功!");
                $rosNum = count($in_company_ids);
                //$this->push(MQ_COMPANY_RECHARGE,'自动取消公司入款'.$rosNum.'笔');
            } else {
                @wlog(APPPATH.'logs/cash_deal_'.$this->cash->sn.'_'.date('Ym').'.log', "自动取消公司入款过期未处理订单 ".json_encode($in_company_ids)." 失败!");
            }
        }
        // 线上入款
        if ($in_online_ids) {
            $data = [
                'status' => 3,
                'update_time' => $now,
                'remark' => '过期未处理,自动取消'
            ];
            $ret = $this->cash->db->where_in('id',$in_online_ids)
                ->update('cash_in_online',$data);
            if ($ret) {
                @wlog(APPPATH.'logs/cash_deal_'.$this->cash->sn.'_'.date('Ym').'.log', "自动取消线上入款过期未处理订单 ".json_encode($in_online_ids)." 成功!");
                $rosNum = count($in_online_ids);
                //$this->push(MQ_COMPANY_RECHARGE,'自动取消线上入款'.$rosNum.'笔');
            } else {
                @wlog(APPPATH.'logs/cash_deal_'.$this->cash->sn.'_'.date('Ym').'.log', "自动取消线上入款过期未处理订单 ".json_encode($in_online_ids)." 失败!");
            }
        }
    }
}
