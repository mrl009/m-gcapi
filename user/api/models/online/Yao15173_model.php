<?php
/**
 * 15173支付接口调用
 * User: lqh
 * Date: 2018/05/04
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yao15173_model extends Publicpay_model
{
    protected $c_name = 'yao15173';
    private $p_name = '15173';//商品名称
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
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['total_fee'] = $this->money;
        $data['select_url'] = $this->callback;
        $data['sign'] = strtoupper(md5($string));   
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['bargainor_id'] = $this->merId;//商户号
        $data['sp_billno'] = $this->orderNum;
        $data['pay_type'] = 'a'; //固定值
        $data['return_url'] = $this->returnUrl;
        $data['attach'] = $this->p_name;
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //微信WAP
        if (2 == $this->code)
        {
            return 'http://wx.15173.net/WechatPayInterfacewap.aspx';
        } else {
            return 'http://wx.15173.com/WechatPayInterface.aspx';
        }
    }
}
