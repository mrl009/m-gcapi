<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Shandian_model extends Publicpay_model
{
    protected $c_name = 'shandian';
    private $p_name = 'SHANDIAN';//商品名称A
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成
    private $field = 'sign'; //签名参数名

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
        $data['sign'] = $this->getSign($data);
        //构造签名参数
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['customerid'] = $this->merId;
        $data['sdorderno'] = $this->orderNum;
        $data['total_fee'] = $this->money;
        $data['paytype'] = $this->getPayType();
        if ($this->code == 7){
            $data['bankcode'] = $this->bank_type;
        }
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        return $data;
    }

    protected function getSign($data){
        $signData = [
            'version' => $data['version'],
            'customerid' => $data['customerid'],
            'total_fee' => $data['total_fee'],
            'sdorderno' => $data['sdorderno'],
            'notifyurl' => $data['notifyurl'],
            'returnurl' => $data['returnurl']
        ];
        $signStr = data_to_string($signData).$this->key_string.$this->key;
        $sign = md5($signStr);
        return $sign;
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
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银支付
                break;
            case 8:
                return 'qqrcode';//QQ扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqwallet';//QQ
                break;
            case 13:
                return 'jdpaywap';//京东钱包wap
                break;
            case 17:
                return 'yinlian';//京东钱包wap
                break;
            case 22:
                return 'tenpay';//财付通
                break;
            case 25:
                return 'kuaijie';//财付通
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}
