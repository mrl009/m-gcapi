<?php
defined('BASEPATH')or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/27
 * Time: 15:28
 */
include_once  __DIR__.'/Publicpay_model.php';

class Debao_model extends Publicpay_model
{
    protected $c_name = 'debao';
    private $p_name = 'DEBAO';
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $sign_type_field = 'sign_type';//签名方式参数名
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 构造支付参数
     */
    protected function getPayData()
    {
        //构造签名参数
        $data = $this->getBase();
        $pk   = $this->p_key;//加密公钥
        $fd   = $this->field;
        $st   = $this->sign_type_field;
        $data = get_open_sign($data,$pk,$fd,$st);
        return $data;
    }

    /*
     * 构造签名参数
     */
    protected function getBase()
    {
        $data['merchant_code'] = $this->merId;//商户号
        $data['service_type'] = $this->getPayType();//支付方式
        $data['notify_url'] = $this->callback;//回调通知地址
        $data['interface_version'] = $this->version();//商户号
        $data['client_ip']  = get_ip();//用户ip
        $data['order_no'] = $this->orderNum;// 订单号
        $data['order_amount'] = $this->money;// 金额
        $data['order_time'] = date('Y-m-d H:i:s', time());// 订单时间
        $data['product_name'] = $this->p_name;// 商品名称
        //网银支付参数
        if (7 == $this->code)
        {
            $data['input_charset'] = 'UTF-8';//编码字符集
            $data['bank_code'] = $this->bank_type;//银行代码
        }
        return $data;
    }

    /*
     * 支付方式对应版本号
     */
    protected function version()
    {
        if (in_array($this->code, [1, 2, 4, 5, 8, 12, 17]))
        {
            return 'V3.1';
        } else {
            return 'V3.0';
        }
    }

    protected function getPayUrl($pay)
    {
        //pay_url = 'https://api.yuanruic.com/gateway';
        $pay_url = trim($pay['pay_url']);
        if (7 == $this->code)
        {
            $pay_url = 'https://pay.yuanruic.com/gateway?input_charset=UTF-8';
        } elseif (in_array($this->code,$this->wap_code)) {
            $pay_url = 'https://api.yuanruic.com/gateway/api/h5apipay';
        } elseif (in_array($this->code,$this->scan_code)) {
            $pay_url = 'https://api.yuanruic.com/gateway/api/scanpay';
        }
        return $pay_url;
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
                return 'weixin_scan';//微信扫码
                break;
            case 2:
                return 'weixin_h5api';//微信H5
                break;
            case 4:
                return 'alipay_scan';//支付宝扫码
                break;
            case 5:
                return 'alipay_h5api';//支付宝H5
                break;
            case 7:
                return 'direct_pay';//网银
                break;
            case 8:
                return 'tenpay_scan';//QQ钱包
                break;
            case 12:
                return 'qq_h5api';//QQ钱包H5
                break;
            case 17:
                return 'ylpay_scan';//银联钱包
                break;
            default:
                return 'weixin_scan';
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式 将数组转化成STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为XML格式 转化为数组
        $data = FromXml($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (empty($data) || empty($data['response']))
        {
            $this->retMsg('错误信息: 接口服务错误！');
        }
        $data = $data['response'];
        if (!isset($data['result_code']) || (0 <> $data['result_code'])
            || (empty($data['qrcode']) && empty($data['payURL'])))
        {
            if (!empty($data['resp_desc'])) $msg = $data['resp_desc'];
            if (!empty($data['result_desc'])) $msg = $data['result_desc'];
            $msg = isset($msg) ? $msg : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回 二维码地址 wap支付返回支付地址
        if (!empty($data['qrcode']))
        {
            $pay_url = $data['qrcode']; //二维码地址
        } else {
            $pay_url = urldecode($data['payURL']); //wap支付地址
        }
        return $pay_url;
    }
}