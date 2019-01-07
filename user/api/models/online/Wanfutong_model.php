<?php

/**
 * 万付通支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/3
 * Time: 13:55
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Wanfutong_model extends Publicpay_model
{
    protected $c_name = 'wanfutong';
    private $p_name = 'WFT';//商品名称
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 部分第三方支付不一样
     *
     * @param array
     *
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
        $data['paytype'] = $this->getPayType();
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
        $data['total_fee'] = intval($this->money);
        $data['sdorderno'] = $this->orderNum;
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     *
     * @param string code
     *
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code) {
            case 1:
                return 'wxsm';//微信扫码
                break;
            case 2:
                return 'wxsm';//微信WAP
                break;
            case 4:
                return 'alipaysm';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            case 17:
                return 'yinliansao';//银联扫码
                break;
            case 28:
                return 'alipaywap';//支付宝
                break;
            default:
                return 'weixin';//微信扫码
                break;
        }
    }
}