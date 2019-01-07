<?php

/**
 * 686支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/11
 * Time: 10:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';

class Pay686_model extends Publicpay_model
{
    protected $c_name = 'pay686';
    private $p_name = 'PAY686';//商品名称
    //参与签名参数
    private $key_string = '&key=';
    private $field='sign';
    private $method='X';
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
        ksort($data);
        $f = $this->field;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$this->method);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['code'] = $this->merId;
        $data['outOrderNo'] = $this->orderNum;
        $data['goodsClauses'] = $this->p_name;
        if(($this->money)%10!=0){
            $this->retMsg('请输入10-500的整十的整数！');
        }
        $data['tradeAmount'] = $this->money;
        $data['payCode'] = $this->getPayType();
        $data['notifyUrl'] = $this->callback;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 聚合付支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return 'wxpay';//微信扫码
                break;
            case 2:
                return 'wxh5';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式 将数组转化成STRING格式
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        if (empty($data['url']))
        {
            $msg = '返回参数错误';
            if (!empty($data['msg'])) $msg = $data['msg'];
            if (!empty($data['message'])) $msg = $data['message'];
            $msg = mb_convert_encoding($msg,"GBK","UTF-8");
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['url'];
    }
}