<?php

/**
 * 随时付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/27
 * Time: 15:37
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Suifu_model extends Publicpay_model
{
    protected $c_name = 'suifu';
    private $p_name = 'SF';//
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $sign_type_field = 'sign_type';//签名方式参数名

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
        $k = $this->p_key;
        $f = $this->field;
        $s = $this->sign_type_field;
        $data = get_open_sign($data,$k,$f,$s);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_code'] = $this->merId;// 商户在支付平台的的平台号
        $data['service_type'] = $this->getPayType();// 商户在支付平台的的平台号
        $data['notify_url'] = $this->callback;// 商户通知地址
        $data['interface_version'] = $this->getVersion();// 接口版本
        $data['order_no'] = $this->orderNum;// 订单号
        $data['order_amount'] = $this->money;// 金额
        $data['order_time'] = date('Y-m-d H:i:s', time());// 订单时间
        $data['product_name'] = $this->p_name;// 商品名称
        $data['client_ip'] = get_ip();// IP
        //网银支付参数
        if (7 == $this->code)
        {
            $data['input_charset'] = 'UTF-8';//编码字符集
            $data['bank_code'] = $this->bank_type;//银行代码
        }
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
                return 'weixin_scan';//微信扫码weixin_scan
                break;
            case 2:
                return 'h5_wx';//微信H5 h5_qq    h5_wx  h5_ali  h5_union（银联H5） h5_jd
                break;
            case 4:
                return 'alipay_scan';//支付宝扫码alipay_scan
                break;
            case 5:
                return 'alipay_h5api';//支付宝H5
                break;
            case 7:
                return 'direct_pay';//网银
                break;
            case 8:
                return 'tenpay_scan';//QQ钱包tenpay_scan
                break;
            case 12:
                return 'h5_qq';//QQ钱包H5
                break;
            case 13:
                return 'h5_jd';//jdh5
                break;
            case 17:
                return 'ylpay_scan';//银联钱包
                break;
            case 18:
                return 'h5_union';//银联钱包h5
                break;
            case 38:
                return 'snpay_scan';//苏宁钱包
                break;
            default:
                return 'weixin_scan';
        }
    }

    /**
     * 获取支付参数版本号
     * @param $code
     * @return string
     */
    private function getVersion()
    {
        if (in_array($this->code, [1,4,5,8,9,10,16,17,19,21,22,24,33,36]))
        {
            return 'V3.1';
        } else {
            return 'V3.0';
        }
    }

    /**
     * 获取支付网关地址
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $pay_url = trim($pay['pay_url']);
        }
        if (in_array($this->code, [1, 4, 8, 17, 38]))
        {
            return $pay_url.'/gateway/api/scanpay';//https://api.suifupay.com/gateway/api/scanpay
        } elseif (in_array($this->code, [2, 5, 12])) {
            return $pay_url.'/gateway/api/h5apipay';
        } elseif (7 == $this->code) {
            return $pay_url.'/gateway?input_charset=UTF-8';
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
        if (!isset($data['result_code']) || ( '0' <> $data['result_code'])
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