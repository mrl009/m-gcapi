<?php
/**
 * 汇祥支付接口调用
 * User: lqh
 * Date: 2018/08/12
 * Time: 13:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Huixiang_model extends Publicpay_model
{
    protected $c_name = 'huixiang';
    private $p_name = 'HUIXIANG';//商品名称
    //支付接口签名参数 
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
        $data['Version'] = 'V1.0.0';
        $data['OrderType'] = 1;
        $data['ComeIp'] = get_ip();
        $data['Attach'] = $this->p_name;
        $data['Format'] = 1;
        $data['NotifyUrl'] = $this->callback;
        $data['RenturnUrl'] = $this->returnUrl;
        $data['Sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['MerId'] = $this->merId;
        $data['MerOrderId'] = $this->orderNum;
        $data['BankId'] = $this->getPayType();
        $data['OrderTime'] = date('YmdHis');
        $data['OrderAmount'] = $this->money;
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
                return '10027';//微信扫码
                break;
            case 2:
                return '10029';//微信WAP
                break;
            case 4:
                return '10026';//支付宝扫码
                break; 
            case 5:
                return '10028';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '10031';//QQ扫码
                break;
            case 12:
                return '10032';//QQWAP
                break;
            case 25:
                return '10030';//网银快捷支付
                break;
            default:
                return '10026';//支付宝扫码
                break;
        }
    }
}
