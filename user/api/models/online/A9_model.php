<?php

/**
 * A9支付接口调用
 * User: Tailand
 * Date: 2019/1/2
 * Time: 16:12
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class A9_model extends Publicpay_model
{
    protected $c_name = 'a9';
    protected $p_name = 'A9';

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
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['inputCharset'] = 'UTF-8';
        $data['MerchantId'] = $this->merId;
        $data['amount'] = $this->money;
        $data['out_trade_no'] = $this->orderNum;
        $data['attach'] = $this->p_name;
        $data['gateway']=$this->getPayType();//充值方式
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['signType'] = 'MD5';
        if (7 == $this->code) $data['defaultBank'] = $this->bank_type;
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
                return 'wxpayH5';//微信wap
                break;
            case 4:
                return 'alipay';//支付宝
                break;
            case 5:
                return 'alipayH5';//支付宝H5
                break;
            case 7:
                return 'cyberbank';//网银
                break;
            case 8:
                return 'qqpay';//QQ
                break;
            case 9:
                return 'jdpay';//京东
                break;
            case 12:
                return 'qqpayH5';//QQWAP
                break;
            case 13:
                return 'jdpayH5';//京东WAP
                break;
            case 17:
                return '919';//银联扫码
                break;
            case 25:
                return 'quickpayment';//快捷
                break;
            default:
                return 'alipay';
        }
    }
}