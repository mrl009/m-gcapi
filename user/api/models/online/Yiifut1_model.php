<?php
/**
 * 逸付支付接口调用
 * User: lqh
 * Date: 2018/08/14
 * Time: 17:00
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yiifut1_model extends Publicpay_model
{
    protected $c_name = 'yiifu';
    private $p_name = 'YIIFU';//商品名称
    private $f_money = [100,200,300,400,500,600,700,800,900,1000];//微信支付整百金额
    //支付接口签名参数 
    private $key_string = '&paySecret='; //参与签名组成

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
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildScan($data);
        } elseif(in_array($this->code,$this->wap_code) || (25 == $this->code)) {
            return $this->buildWap($data);
        } else {
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
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['payKey'] = $this->merId;
        if(in_array($this->code,[1,2]))
        {if(in_array($this->money,$this->f_money)){}else
        {  $this->retMsg("微信通道请支付100到1000的整百金额！");}}
        $data['orderPrice'] = $this->money;
        $data['outTradeNo'] = $this->orderNum;
        $data['productType'] = $this->getPayType();
        $data['orderTime'] = date('YmdHis');
        $data['productName'] = $this->p_name;
        $data['orderIp'] = get_ip();
        if (7 == $this->code)
        {
            $data['bankCode'] = $this->bank_type;
            $data['bankAccountType'] = 'PRIVATE_DEBIT_ACCOUNT';
        }
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['remark'] = $this->p_name;
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
                return '10000101';//微信扫码
                break;
            case 2:
                return '10000201';//微信WAP
                break;
            case 4:
                return '20000301';//支付宝扫码
                break; 
            case 5:
                return '20000201';//支付宝WAP
                break;
            case 7:
                return '50000101';//网银支付
                break;
            case 8:
                return '70000101';//QQ扫码
                break;
            case 9:
                return '80000101';//京东扫码
                break;
            case 12:
                return '70000201';//QQWAP
                break;
            case 13:
                return '80000201';//京东WAP
                break;
            case 17:
                return '60000101';//银联扫码
                break;
            case 18:
                return '60000201';//银联WAP
                break;
            case 25:
                return '40000101';//网银快捷支付
                break;
            case 40:
                return '10000501';//微信条码
                break;
            case 41:
                return '20000501';//支付宝条码
                break;
            default:
                return '20000301';//支付宝扫码
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
        if (7 == $this->code)
        {
            $pay_url .= 'b2cPay/initPay';
        } else {
            $pay_url .= 'scanPay/initPay';
        }
        return $pay_url;
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
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payMessage']))
        {
            $msg = isset($data['errMsg']) ? $data['errMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['payMessage'];
    }
}
