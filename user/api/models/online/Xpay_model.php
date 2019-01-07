<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/10
 * Time: 14:10
 */
include_once __DIR__.'/Publicpay_model.php';

class Xpay_model extends Publicpay_model
{
    protected $c_name = 'xpay';
    private $p_name = 'XPAY';//商品名称
    //支付接口签名参数
    private $ks = '&key='; //参与签名组成

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
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data,'^','&') . $k;
        $data['pay_md5sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;
        $data['pay_orderid'] = $this->orderNum;
        $data['pay_amount'] = $this->money;
        $data['pay_applydate'] = date("YmdHis");
        $data['pay_channelCode'] = $this->getPayType();
        $data['pay_notifyurl'] = $this->callback;
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
                return 'WECHAT';//微信扫码
                break;
            case 2:
                return 'WECHAT_WAP';//微信扫码
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY_WAP';//支付宝WAP
                break;
            case 7:
                return 'BANK';//网银支付
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JD';//京东扫码
                break;
            case 25:
                return 'BANK_WAP';//快捷
                break;
            default:
                return 'ALIPAY';//支付宝扫码
                break;
        }
    }
}