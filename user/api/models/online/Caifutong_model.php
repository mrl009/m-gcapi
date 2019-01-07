<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Caifutong_model extends Publicpay_model
{
    protected $c_name = 'caifutong';
    private $p_name = 'CAIFUTONG';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '#'; //参与签名组成
    private $field = 'sign'; //签名参数名

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
        $signStr = "amount={$data['amount']}&bank={$data['bank']}&orderNo={$data['orderNo']}".$k;
        $data['sign'] = md5($signStr);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchantNo'] = $this->merId;//商户号
        $data['orderNo'] = $this->orderNum;
        $data['amount'] = $this->money;
        $data['bank'] = $this->getPayType();
        $data['name'] = $this->p_name;
        $data['count'] = '1';
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
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
                return 'WXSCAN';//微信扫码
                break;   
            case 2:
                return 'WXH5';//微信WAP
                break;
            case 4:
                return 'ALISCAN';//支付宝扫码
                break;
            case 5:
                return 'ALIH5';//支付宝WAP
                break; 
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'QQSCAN';//QQ扫码
                break;
            case 9:
                return 'JDSCAN';//京东钱包
                break;
            case 10:
                return 'BDSCAN';//百度钱包
                break;
            case 12:
                return 'QQH5';//QQwap
                break; 
            case 13:
                return 'JDH5';//京东wap
                break; 
            case 17:
                return 'USCAN';//银联钱包
                break;
            case 18:
                return 'UH5';//银联钱包
                break;
            case 20:
                return 'BDH5';//百度钱包wap
                break;
            case 25:
                return 'UQUICK';//快捷
                break;
            default:
                return 'WXSCAN';//微信扫码
                break;
        }
    }
}
