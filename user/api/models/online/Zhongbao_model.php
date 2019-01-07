<?php
/**
 * 众宝支付接口调用
 * User: lqh
 * Date: 2018/05/29
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhongbao_model extends Publicpay_model
{
    protected $c_name = 'zhongbao';
    private $p_name = 'ZHONGBAO';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
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
        $string = ToUrlParams($data) . $k;
        $data['israndom'] = 'N';
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchantid'] = $this->merId;//商户号
        //快捷支付没有该参数 且银行参数bankcode不传
        if (25 <> $this->code)
        {
            $data['paytype'] = $this->getPayType();
        } else {
            $data['bankcode'] = '';
        }
        $data['amount'] = $this->money;
        $data['orderid'] = $this->orderNum;
        $data['notifyurl'] = $this->callback;
        $data['request_time'] = date("YmdHis");
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //条码支付
        if (in_array($this->code,[40,41])) 
        {
            $url = 'https://gateway.zbpay365.com/GateWay/BarCode';
        //快捷支付
        } elseif(25 == $this->code) {
            $url = 'https://gateway.zbpay365.com/FastPay/Index';
        //扫码支付
        } else {
            $url = 'https://gateway.zbpay365.com/GateWay/Pay';
        }
        return $url;
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
                return '1000';//微信扫码
                break;   
            case 2:
                return '1002';//微信WAP
                break;
            case 4:
                return '1003';//支付宝扫码
                break; 
            case 5:
                return '1004';//支付宝WAP
                break;    
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 8:
                return '1005';//QQ钱包扫码
                break;
            case 9:
                return '1007';//京东扫码
                break; 
            case 12:
                return '1006';//QQ钱包WAP
                break;
            case 13:
                return '1008';//京东钱包WAP
                break;   
            case 17:
                return '1009';//银联钱包
                break;
            case 18:
                return '1012';//银联WAP
                break;
            case 40:
                return '1011';//微信条码
                break; 
            case 41:
                return '1015';//支付宝条码
                break;   
            default:
                return '1000';//微信扫码
                break;
        }
    }
}