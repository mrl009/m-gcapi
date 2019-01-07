<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/9
 * Time: 19:44
 */
defined('BASEPATH')or exit('No direct script access allowed');
//调用公共文件
include_once  __DIR__.'/Publicpay_model.php';
class Hongfu_model extends Publicpay_model
{
    protected $c_name = 'hongfu';
    private $p_name = 'HONGFU';
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $sign_type_field = 'sign_type';//签名方式参数名
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
       if (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
        } else {
            return $this->buildForm($data);
        }
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
        //json格式化后在去掉反斜线
        ksort($data);
        $string = ToUrlParams($data);
        $data[$this->field] = $this ->encodePay($string);
        $data['sign_type'] = 'RSA-S';//签名方式
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
        $data['interface_version'] = 'V3.0';//商户号
        $data['input_charset'] = 'UTF-8';//商户号
        $data['client_ip']  = get_ip();//用户ip
        $data['order_no'] = $this->orderNum;// 订单号
        $data['order_amount'] = $this->money;// 金额
        $data['order_time'] = date('Y-m-d H:i:s', time());// 订单时间
        $data['product_name'] = $this->p_name;// 商品名称
        $data['extend_param'] = $this->c_name;
        $data['redo_flag'] = '1';//不允许重复提交订单号
        $data['return_ur'] = $this->returnUrl;//不允许重复提交订单号
        //网银支付参数
        if (7 == $this->code)
        {
            $data['bank_code'] = $this->bank_type;//银行代码
        }
        return $data;
    }
    /**
     * 生成传输密文
     * @return array
     */
    private function encodePay($str)
    {
        $privateKey = $this->p_key;//私钥
        $privateKey = openssl_get_privatekey($privateKey);
        openssl_sign($str, $sign_info, $privateKey, OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
        return $sign;
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
                return 'alipay_h5';//支付宝H5
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
            case 13:
                return 'jd_h5';//jd钱包H5
                break;
            case 17:
                return 'ylpay_scan';//银联钱包
                break;
            case 27:
                return 'unionpay_h5';//银联wap
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
        //传递参数为STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        $data = $data['response'];
        if (!isset($data['result_code']) || ('SUCCESS' <> $data['resp_code'])
            || (empty($data['qrcode'])))
        {
            if (!empty($data['resp_desc'])) $msg = $data['resp_desc'];
            if (!empty($data['result_desc'])) $msg = $data['result_desc'];
            $msg = isset($msg) ? $msg : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回 二维码地址
        $pay_url = $data['qrcode']; //二维码地址
        return $pay_url;
    }
}