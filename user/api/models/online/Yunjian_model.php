<?php
/**
 * 云尖支付接口调用
 * User: lqh
 * Date: 2018/07/16
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yunjian_model extends Publicpay_model
{
    protected $c_name = 'yunjian';
    private $p_name = 'YUNJIAN';//商品名称
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成

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
        $string = ToUrlParams($data) . $k;
        $data['get_code'] = 1;
        $data['paytype'] = $this->getPayType();
        //网银参数
        if (7 == $this->code) 
        {
           $data['bankcode'] = $this->bank_type;
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
        $data['version'] = '1.0';
        $data['customerid'] = $this->merId;//商户号
        $data['total_fee'] = $this->money;
        $data['sdorderno'] = $this->orderNum;
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code 
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return 'weixin';//微信扫码
                break; 
            case 2:
                return 'wxh5';//微信WAP
                break;   
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银直连
                break;
            case 8:
                return 'qqrcode';//QQ扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqwallet';//QQWAP
                break;
            case 13:
                return 'jdpaywap';//京东WAP
                break;
            case 17:
                return 'yinlian';//银联扫码
                break;
            case 22:
                return 'tenpay';//财付通扫码
                break;
            case 25:
                return 'kuaijie';//快捷支付
                break;     
            default:
                return 'weixin';//微信扫码
                break;
        }
    }
}
