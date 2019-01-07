<?php

/**
 * 易联支付微信接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/10/27
 * Time: 16:04
 */
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';
class Yilian2_model extends Publicpay_model
{
    //redis 错误记录
    protected $c_name = 'yilian2';
    private $p_name = 'YILIAN2';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
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
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
        $data['pay_amount'] = $this->money;
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
                return '902';//微信扫码
                break;
            case 2:
                return '901';//微信Wap/h5
                break;
            case 4:
                return '903';//支付宝扫码
                break;
            case 5:
                return '904';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '908';//QQ扫码
                break;
            case 9:
                return '910';//京东扫码
                break;
            case 10:
                return '909';//百度扫码
                break;
            case 12:
                return '905';//QQWAP
                break;
            case 17:
                return '917';//银联钱包
                break;
            case 18:
                return '912';//银联钱包WAP
                break;
            default:
                return '903';//支付宝扫码
                break;
        }
    }

}