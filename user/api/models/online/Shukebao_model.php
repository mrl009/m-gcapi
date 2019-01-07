<?php
/**
 * 数科宝付接口调用
 * User: lqh
 * Date: 2018/08/16
 * Time: 11:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Shukebao_model extends Publicpay_model
{
    protected $c_name = 'shukebao';
    private $p_name = 'SHUKEBAO';//商品名称
    //支付接口签名参数 
    private $key_string = '~|~'; //参与签名组成

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
        $k = $this->key_string . $this->key;
        $string = implode('~|~', array_values($data)) . $k; 
        $data['product_name'] = $this->p_name;
        $data['order_userid'] = $this->user['id'];
        $data['order_info'] = $this->p_name;
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            $data['pay_model'] = 'H5';
        //扫码支付
        }  elseif (in_array($this->code,$this->scan_code)) {
            $data['pay_model'] = 'SCODE';
        //网银支付快捷支付
        } else {
            $data['pay_model'] = 'PC';
        }
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_code'] = $this->merId;
        $data['interface_version'] = 'V1.0';
        $data['sign_type'] = 'MD5';
        $data['order_no'] = $this->orderNum;
        $data['order_time'] = date('Y-m-d H:i:s');
        $data['order_amount'] = $this->money;
        $data['product_number'] = 1;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['bank_code'] = $this->getPayType();
        $data['notice_type'] = 0;
        $data['service_type'] = 'connect_service';//直连模式
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
            case 2:
                return 'WEBCHAT';//微信扫码
                break;
            case 4:
            case 5:
                return 'ALIPAY';//支付宝扫码
                break;
            case 7: 
                return $this->bank_type;//网关支付
                break;
            case 8:
            case 12:
                return 'TENPAY';//QQ扫码
                break;
            case 9:
                return 'JDPAY';//京东扫码
                break;
            case 25:
                return 'QUICK';//快捷支付
                break;
            default:
                return 'ALIPAY';//支付宝扫码
                break;
        }
    }
}
