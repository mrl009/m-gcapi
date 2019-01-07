<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Shidai_model extends Publicpay_model
{
    protected $c_name = 'Shidai';
    protected $p_name = 'SHIDAI';
    //支付接口签名参数
    protected $key_string = '&'; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }

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
        $signStr = data_to_string($data).$this->key_string . $this->key;
        $data['sign'] = md5($signStr);
        $data['paytype'] = $this->getPayType();
        if ($data['paytype'] == 'bank'){
            $data['bankcode'] = $this->bank_type;
        }
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = 'V2.23';
        $data['merchantid'] = $this->merId;
        $data['orderamt'] = $this->money;
        $data['merordernum'] = $this->orderNum;
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
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
                return 'weixin';//微信扫码
                break;
            case 2:
                return 'wxh5';//微信Wap/h5
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'aliwap';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银支付
                break;
            case 8:
                return 'qqpay';//QQ扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqh5';//QQ
                break;
            case 13:
                return 'jdwap';//京东钱包wap
                break;
            case 17:
                return 'Pcunionpay';//银联扫码
                break;
            case 25:
                return 'Kjunionpay';//快捷
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}
