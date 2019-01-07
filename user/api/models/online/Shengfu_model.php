<?php
/**
 * 盛付支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Shengfu_model extends Publicpay_model
{
    protected $c_name = 'shengfu';
    private $p_name = 'SHENGFU';//商品名称

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
        $data['pay_version'] = 'vb1.0';
        $data['pay_applydate'] = date("YmdHis");
        $data['pay_productname'] = $this->p_name;
        $data['pay_md5sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_amount'] = $this->money;
        $data['pay_orderid'] = $this->orderNum;
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
                return '1004';//微信宝扫码
                break; 
            case 2:
                return '1005';//微信宝WAP
                break;
            case 4:
                return '992';//支付宝扫码
                break; 
            case 5:
                return '1006';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '1593';//QQ
                break; 
            case 9:
                return '1008';//京东
                break;
            case 12:
                return '1594';//QQWAP
                break;
            case 17:
                return '1007';//银联钱包
                break;
            case 18:
                return '2088';//银联WAP
                break;
            case 25:
                return '2087';//快捷
                break;
            default:
                return '992';//支付宝扫码
                break;
        }
    }
}
