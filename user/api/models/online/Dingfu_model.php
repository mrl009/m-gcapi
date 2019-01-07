<?php
/**
 * 鼎付支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Dingfu_model extends Publicpay_model
{
    protected $c_name = 'dingfu';
    protected $p_name = 'DINGFU';

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
        $data['signType'] = 'MD5';
        $data['sign'] = md5($string);
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['service'] = 'directPay';
        $data['merchantId'] = $this->merId;
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['transAmt'] = $this->money;
        $data['outOrderId'] = $this->orderNum;
        $data['payMethod']=$this->getPayType();//充值方式
        $data['inputCharset'] = 'UTF-8';
        $data['cardAttr'] = '01';
        $data['channel'] = 'B2C';
        $data['body'] = $this->p_name;
        $data['attach'] = $this->p_name;
        $data['subject'] = $this->p_name;
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
                return '900';//微信扫码
                break;
            case 4:
                return '917';//支付宝
                break;
            case 5:
                return '918';//支付宝H5
                break;
            case 7:
                return '905';//网银
                break;
            /*case 8:
                return '907';//QQ
                break;
            case 9:
                return '908';//京东
                break;
            case 12:
                return '910';//QQWAP
                break;
            case 13:
                return '913';//京东WAP
                break;
            case 18:
                return '916';//银联WAP
                break;*/
            case 17:
                return '919';//银联扫码
                break;    
            case 25:
                return '916';//快捷
                break;
            default:
                return '917';
        }
    }
}