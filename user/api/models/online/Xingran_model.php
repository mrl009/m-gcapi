<?php
/**
 * 星染支付接口调用
 * User: lqh
 * Date: 2018/06/21
 * Time: 10:05
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xingran_model extends Publicpay_model
{
    protected $c_name = 'xingran';
    private $p_name = 'XINGRAN';//商品名称

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
        return $this->Redirect($data);
        //return $this->buildForm($data);
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
        $string = implode('',array_values($data));
        $data['hmac'] = hash_hmac("md5",$string,$this->key,false);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['servicetype'] = 'buy';
        $data['partnerId'] = $this->merId;//商户号
        $data['orderno'] = $this->orderNum;
        $data['amount'] = yuan_to_fen($this->money);
        $data['currency'] = 'CNY';
        $data['notify'] = $this->callback;
        $data['type'] = $this->getPayType();
        $data['needresponse'] = 1;
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
            case 2:
                return 'wxwap';//微信WAP
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break; 
            case 25:
                return 'unpay';//银联快捷
                break;
            default:
                return 'alipaywap';//微信扫码
                break;
        }
    }
}
