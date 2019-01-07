<?php
/**
 * 恒通支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Hengtong_model extends Publicpay_model
{
    protected $c_name = 'hengtong';
    private $p_name = 'HT';//商品名称
    private $ks = '&key=';

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
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数(业务数据)
     * @return array
     */
    private function getBaseData()
    {
        $data['customer'] = $this->merId;
        if (25 <> $this->code) 
        {
            $data['banktype'] = $this->getPayType();
        }
        $data['amount'] = $this->money;
        $data['orderid'] = $this->orderNum;
        $data['asynbackurl'] = $this->callback;
        $data['request_time'] = date('YmdHis');
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
                return '1000';//微信扫码
                break;
            case 2:
                return '1002';//微信H5
                break;   
            case 4:
                return '1003';//支付宝扫码
                break;
            case 5:
                return '1004';//支付宝h5
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return '1005';//QQ钱包
                break;
            case 12:
                return '1006';//QQ钱包WAP
                break;
            case 9:
                return '1007';//京东钱包
                break;
            case 13:
                return '1008';//京东钱包H5
                break;
            case 17:
                return '1009';//银联扫码
                break;
            case 40:
                return '1011';//微信条码
                break;
            case 41:
                return '1015';//支付宝条码
                break;
            default:
                return '1003';//支付宝扫码
                break;
        }
    }

    
    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = isset($pay['pay_url']) ? trim($pay['pay_url']) : '';
        if (in_array($this->code,[40,41]))
        {
            $pay_url .= '/GateWay/BarCode';
        } elseif (25 == $this->code) {
            $pay_url .= '/FastPay/Index';
        } else {
            $pay_url .= '/GateWay/Index';
        }
        return $pay_url;
    }
}
