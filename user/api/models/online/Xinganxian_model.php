<?php
/**
 * 新干线支付接口调用
 * User: lqh
 * Date: 2018/08/13
 * Time: 09:30
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinganxian_model extends Publicpay_model
{
    protected $c_name = 'xinganxian';
    private $p_name = 'XINGANXIAN';//商品名称

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
        $string = data_to_string($data);
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_id'] = $this->merId;
        $data['payment_way'] = $this->getPayType();
        $data['order_amount'] = $this->money;
        $data['source_order_id'] = $this->orderNum;
        $data['goods_name'] = $this->p_name;
        $data['client_ip'] = get_ip();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['token'] = $this->key;
        if (7 == $this->code) $data['bank_code'] = $this->bank_type;
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
                return '44';//微信扫码
                break;
            case 2:
                return '24';//微信WAP
                break;
            case 4:
                return '43';//支付宝扫码
                break; 
            case 5:
                return '25';//支付宝WAP
                break;
            case 7:
                return '3';//网银支付
                break;
            case 8:
                return '49';//QQ扫码
                break;
            case 9:
                return '47';//京东扫码
                break;
            case 10:
                return '48';//百度扫码
                break;
            case 12:
                return '22';//QQWAP
                break;
            case 17:
                return '45';//银联扫码
                break;
            case 18:
                return '21';//银联WAP
                break;
            case 25:
                return '12';//网银快捷支付
                break;
            case 38:
                return '46';//苏宁扫码
                break; 
            default:
                return '43';//支付宝扫码
                break;
        }
    }
}
