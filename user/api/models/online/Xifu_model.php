<?php
/**
 * 喜付支付接口调用
 * User: lqh
 * Date: 2018/05/04
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xifu_model extends Publicpay_model
{
    protected $c_name = 'xifu';
    private $p_name = 'XIFU';//商品名称

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据
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
        ksort($data);
        $string = ToUrlParams($data).$this->key;
        $data['sign'] = strtoupper(sha1($string));
        $data['signType'] = 'SHA';
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['body'] = $this->p_name;
        $data['charset'] = 'UTF-8';
        $data['defaultbank'] = $this->getPayType();
        $data['isApp'] = $this->getIsApp();
        $data['merchantId'] = $this->merId;//商户号
        $data['notifyUrl'] = $this->callback;
        $data['orderNo'] = $this->orderNum;
        $data['paymentType'] = 1; //固定值1
        $data['paymethod'] = 'directPay';
        $data['returnUrl'] = $this->returnUrl;
        $data['service'] = 'online_pay'; //固定值online_pay
        $data['title'] = $this->p_name;
        $data['totalFee'] = $this->money;
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $url = '';
        if (!empty($pay['pay_url']))
        {
            //构造特殊网关提交地址
            $md = $this->merId;
            $od = $this->orderNum;
            $url = trim($pay['pay_url']) . "/{$md}-{$od}";
        }
        return $url;
    }

    /**
     * 根据code值获取支付类型
     * @param string code 
     * @return string 支付类型 参数
     */
    private function getIsApp()
    {
        //扫码类型 web
        if (in_array($this->code,$this->scan_code))
        {
            return 'web';
        } else{
            return 'H5';
        }
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
            case 2:
                return 'WXPAY';//微信扫码、WAP
                break; 
            case 4:
            case 5:
                return 'ALIPAY';//支付宝扫码、WAP
                break;     
            case 8:
            case 12:
                return 'QQPAY';//QQ钱包扫码、WAP
                break;    
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 9:
                return 'JDPAY';//京东扫码
                break;    
            case 10:
                return 'BDPAY';//百度扫码
                break;
            case 17:
                return 'UNIONQRPAY';//银联钱包
                break;    
            case 18:
                return 'UNIONPAY';//银联钱包WAP
                break;
            default:
                return 'ALIPAY';//支付宝扫码
                break;
        }
    }
}
