<?php

/**
 * 金城支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2019/1/2
 * Time: 10:16
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jincheng_model extends Publicpay_model
{
    protected $c_name = 'Jincheng';
    private $p_name = 'JINCHENG';//新顺畅reids4
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $method = 'X'; //小写
    private $sk='&key=';//签名方式参数名

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        return $this->buildForm($data);
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
        $k = $this->sk.$this->key;
        $f = $this->field;
        $s = $this->method;
        $data = get_pay_sign($data,$k,$f,$s);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_code'] = $this->merId;// 商户在支付平台的的平台号
        $data['order_no'] = $this->orderNum;// 订单号
        $data['trade_type'] = $this->getPayType();// 商户在支付平台支付方式
        $data['amount'] = $this->money;// 金额
        $data['create_time'] = date('Y-m-d H:i:s',time());// 订单时间
        $data['source_ip'] = get_ip();// 订单时间
        //$data['source_ip'] = '102.3.11.17';// 订单时间
        $data['notify_url'] = $this->callback;// 商户通知地址
        $data['return_url'] = $this->returnUrl;//通知地址
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 聚合付支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code) {
            case 1:
                return '1';//微信扫码
                break;
            case 4:
                return '1';//支付宝扫码
                break;
            case 8:
                return '3';//qq扫码
                break;
            default:
                return '1';
        }
    }
}