<?php

/**
 * 和付通支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/21
 * Time: 10:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Hetongfu_model extends Publicpay_model
{
    protected $c_name = 'hetongfu';
    private $p_name = 'HTF';//商品名称

    public function __construct()
    {
        parent::__construct();
    }

    protected function returnApiData($data){
        if(in_array($this->code,$this->scan_code)){
            return $this->buildScan($data);
        }else{
            return $this->buildForm($data);
        }
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
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['partner'] = $this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;
        $data['timestamp'] = date('Y-m-d H:i:s',time());
        $data['total_fee'] = yuan_to_fen($this->money);
        $data['notify_url'] = $this->callback;
        $data['payment_type'] = $this->getPayType();
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
                return 'WECHAT_QRCODE_PAY';//微信扫码
                break;
            case 2:
                return 'WECHAT_WAP_PAY';//微信wap
                break;
            case 4:
                return 'ALIPAY_QRCODE_PAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY_WAP_PAY';//支付宝扫码
                break;
            case 7:
                return 'ONLINE_BANK_PAY';//网关支付
                break;
            case 8:
                return 'QQ_QRCODE_PAY';//QQ钱包扫码
                break;
            case 9:
                return 'JD_QRCODE_PAY';//jd扫码
                break;
            case 12:
                return 'QQ_WAP_PAY';//QQwap
                break;
            case 13:
                return 'JD_WAP_PAY';//jdwap
                break;
            case 17:
                return 'UNIONPAY_QRCODE_PAY';//银联钱包扫码
                break;
            case 18:
                return 'UNIONPAY_WAP_PAY';//银联钱包wap
                break;
            case 25:
                return 'ONLINE_BANK_QUICK_PAY';//银联快捷
                break;
            default:
                return 'ALIPAY_QRCODE_PAY';//微信扫码
                break;
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrcode_url']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['qrcode_url'];
        //扫码支付
        return $pay_url;
    }
}