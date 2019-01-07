<?php
/**
 * 易卡支付接口调用
 * User: lqh
 * Date: 2018/06/21
 * Time: 16:21
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yika_model extends Publicpay_model
{
    protected $c_name = 'yika';
    private $p_name = 'YIKA';//商品名称

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
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = md5($string);
        $data['value'] = $this->money;
        $data['hrefbackurl'] = $this->returnUrl;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();
        $data['orderid'] = $this->orderNum;
        $data['callbackurl'] = $this->callback;
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
                return 'WEIXIN';//微信扫码
                break;   
            case 2:
                return 'WXWAP';//微信WAP
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIWAP';//支付宝WAP
                break; 
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 8:
                return 'QQCODE';//QQ钱包扫码
                break;
            case 25:
                return 'KUAIJIE';//快捷支付
                break;    
            default:
                return 'WEIXIN';//微信扫码
                break;
        }
    }
}
