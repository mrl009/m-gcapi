<?php
/**
 * MyPay支付接口调用
 * User: lqh
 * Date: 2018/08/05
 * Time: 15:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Mypay_model extends Publicpay_model
{
    protected $c_name = 'mypay';
    private $p_name = 'MYPAY';//商品名称
    private $key_string = '&key='; //参与签名组成

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
        ksort($data);
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['version'] = 'V1.0';
        $data['orgId'] = $this->s_num;
        $data['bankId'] = $this->bank_type;
        $data['sign'] = strtoupper(md5($string));
        $data['signType'] = 'MD5';
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merId'] = $this->merId;
        $data['payType'] = $this->getPayType();
        $data['merchantNo'] = $this->orderNum;
        if (in_array($this->code,$this->wap_code))
        {
            $data['terminalClient'] = 'wap';
        } else {
            $data['terminalClient'] = 'pc';
        }
        $data['tradeDate'] = date("YmdHis");
        $data['amount'] = $this->money;
        $data['clientIp'] = get_ip();
        $data['notifyUrl'] = $this->callback;
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * MYPAY支付 各商户网关地址不一致 取后台商城域名参数
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $url = '';
        if (!empty($pay['shopurl']))
        {
            $url = trim($pay['shopurl']);
        }
        return $url;
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
                return '3';//微信扫码
                break;
            case 2:
                return '4';//微信WAP
                break;
            case 4:
                return '1';//支付宝扫码
                break; 
            case 5:
                return '2';//支付宝WAP
                break;
            case 7:
                return '5';//网银支付
                break;
            case 8:
                return '8';//QQ扫码
                break;
            case 9:
                return '10';//京东扫码
                break;
            case 12:
                return '9';//QQWAP
                break;
            case 13:
                return '11';//京东wap
                break;
            case 17:
                return '16';//银联钱包
                break;
            case 25:
                return '14';//快捷支付
                break;
            default:
                return '1';//支付宝扫码
                break;
        }
    }
}
