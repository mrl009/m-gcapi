<?php
/**
 * wordfod支付接口调用
 * User: lqh
 * Date: 2018/07/22
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Wordfod_model extends Publicpay_model
{
    protected $c_name = 'wordfod';
    private $p_name = 'WORDFOD';//商品名称

    public function __construct()
    {
        parent::__construct();
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
        $string = ToUrlParams($data);
        $pk = openssl_get_privatekey($this->p_key);
        openssl_sign($string, $sign_info, $pk, OPENSSL_ALGO_MD5);
        $data['sign'] = base64_encode($sign_info);
        $data['sign_type'] = 'RSA-S';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_code'] = $this->merId;//商户号
        $data['service_type'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        //网银参数
        if (7 == $this->code)
        {
            $data['input_charset'] = 'UTF-8';
            $data['interface_version'] = 'V3.0';
            $data['bank_code'] = $this->bank_type;
        } else {
            $data['interface_version'] = 'V3.1';
        }
        $data['client_ip'] = get_ip();
        $data['order_amount'] = $this->money;//金额
        $data['order_no'] = $this->orderNum;//订单号 唯一
        $data['order_time'] = date('Y-m-d H:i:s');
        $data['product_name'] = $this->p_name;
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
                return 'weixin_scan';//微信扫码
                break;
            case 2:
                return 'weixin_h5api';//微信WAP
                break;
            case 4:
                return 'alipay_scan';//支付宝扫码
                break;
            case 5:
                return 'alipay_h5api';//支付宝WAP
                break;
            case 7:
                return 'direct_pay';//网关支付
                break;
            case 8:
                return 'tenpay_scan';//QQ扫码
                break; 
            case 9:
                return 'jd_scan';//京东扫码
                break;
            case 12:
                return 'qq_h5api';//QQWAP
                break; 
            case 13:
                return 'jd_h5api';//京东WAP
                break;  
            case 17:
                return 'ylpay_scan';//银联扫码
                break;     
            case 38:
                return 'snpay_scan';//苏宁扫码
                break;
            default:
                return 'weixin_scan';//微信扫码
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
        $url = '';
        if (in_array($this->code,$this->wap_code))
        {
            $url = 'https://api.wordfod.com/gateway/api/h5apipay';
        } elseif (7 == $this->code) {
            $url = 'https://pay.wordfod.com/gateway?input_charset=UTF-8';
        } elseif (in_array($this->code,$this->scan_code)) {
            $url = 'https://api.wordfod.com/gateway/api/scanpay';
        }
        return $url;
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为XML格式 转化为数组
        $data = FromXml($data);
        if (isset($data['response'])) $data = $data['response'];
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrcode']) && empty($data['payURL']))
        {
            $msg = "返回参数错误";
            if (isset($data['resp_desc'])) $msg = $data['resp_desc'];
            if (isset($data['result_desc'])) $msg = $data['result_desc'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付二维码连接地址或WAP支付地址
        if (isset($data['qrcode'])) $pay_url = $data['qrcode'];
        if (isset($data['payURL'])) $pay_url = urldecode($data['payURL']);
        return $pay_url;
    }
}
