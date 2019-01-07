<?php
/**
 * jk支付接口调用
 * User: lqh
 * Date: 2018/06/15
 * Time: 17:05
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jk_model extends Publicpay_model
{
    protected $c_name = 'jk';
    private $p_name = 'JK';//商品名称

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $data = array_filter($data);
        ksort($data);
        $string = json_encode($data);
        $string = str_replace('\/\/','//',$string);
        $string = str_replace('\/','/',$string);
        $string = $this->key . $string . $this->key;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['merchant_order_no'] = $this->orderNum;
        $data['merchant_no'] = $this->merId;//商户号
        $data['callback_url'] = $this->callback;
        $data['order_smt_time'] = date('YmdHis');
        $data['order_type'] = '02';
        $data['trade_amount'] = (string)yuan_to_fen($this->money);
        $data['goods_name'] = $this->p_name;
        $data['goods_type'] = '02';
        $data['trade_desc'] = $this->p_name;
        $data['sign_type'] = '01';
        return $data;
    }

    
    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //构造支付网关地址，每一种支付方式都不一样 
        $base_url = $pay['pay_url'];
        switch ($this->code)
        {
            case 4:
                $url = 'alipay/direct_pay.tran';//支付宝扫码
                break;
            case 5:
                $url = 'alipay/wap_pay.tran';//支付宝H5
                break;
            default:
                $url = 'alipay/wap_pay.tran';
                break;
        }
        //构造实际支付网关地址(包含token信息)
       return $base_url . $url;
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['body']['params']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['body']['params'];
    }
}
