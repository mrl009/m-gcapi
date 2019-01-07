<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/13
 * Time: 16:58
 */
include_once __DIR__.'/Publicpay_model.php';

class Shouxin_model extends Publicpay_model
{
    protected $c_name = 'shouxin';
    private $p_name = 'SHOUXIN';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
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
        if (in_array($this->code,[2,5,7,12,25]))
        {
            return $this->buildWap($data);
        } else {
            return $this->buildScan($data);
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['mch_id'] = $this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;
        //网关支付参数
        if (7 == $this->code) $data['bank_id'] = $this->bank_type;
        $data['body'] = 'SXPay';
        $data['total_fee'] =yuan_to_fen($this->money);//单位分
        $data['mch_create_ip'] = get_ip();
        $data['time_start'] = date('Y-m-d H:i:s');//订单生成时间
        $data['nonce_str'] = create_guid() ;//随机字符串
        $data['attach'] = $this->getPayType();//获取不同的支付方式
        $data['notify_url'] = $this->callback;
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //获取用户登录的token值
        $token = $this->getToken();
        $payUrl = '';
        if (!empty($pay['pay_url']))
        {
            $payUrl = trim($pay['pay_url']);
        }

        if (1 == $this->code)
        {
            $payUrl .= 'wx/native';
        } elseif (2 == $this->code) {
            $payUrl .= 'wx/wappay';
        } elseif (4 == $this->code) {
            $payUrl .= 'alipay/native';
        } elseif (5 == $this->code) {
            $payUrl .= 'alipay/wap';
        } elseif (7 == $this->code) {
            $payUrl .= 'union/net';
        } elseif (8 == $this->code) {
            $payUrl .= 'qq/native';
        } elseif (9 == $this->code) {
            $payUrl .= 'jd/native';
        } elseif (12 == $this->code) {
            $payUrl .= 'qq/wap';
        }  elseif (17 == $this->code) {
            $payUrl .= 'union/native';
        }  else if(25 == $this->code) {
            $payUrl .= 'union/quick';
        }
        $Url = $payUrl."?token={$token}";
        return $Url;
    }
    /**
     * 获取支付网关地址 用户登录token
     * @param array 支付参数
     * @return string token
     */
    private function getToken()
    {
        $data['appid'] = $this->merId;
        $data['secretid'] = $this->key;
        //用户登录获取token的登录地址
        $url = "http://api.sxzf88.com/auth/access-token";
        $return = post_pay_data($url,$data);
        if (empty($return)) $this->retMsg('商户登录接口获取信息失败！');
        //接收参数为XML格式 转化为数组
        $return = FromXml($return);
        if (empty($return['token']))
        {
            $this->retMsg('商户登录接口获取token失败！');
        }
        return $return['token'];
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
                return 'WXZF';//微信扫码http://api.sxzf88.com/sig/v1/wx/native
                break;
            case 2:
                return 'WXZF_H5';//微信WAP http://api.sxzf88.com/sig/v1/wx/wappay
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码 http://api.sxzf88.com/sig/v1/alipay/native
                break;
            case 5:
                return 'ALIPAY_H5';//支付宝WAP http://api.sxzf88.com/sig/v1/alipay/wap
                break;
            case 7:
                return 'GATEWAY';//网关支付 http://api.sxzf88.com/sig/v1/union/net
                break;
            case 8:
                return 'TENPAY';//QQ钱包扫码 http://api.sxzf88.com/sig/v1/qq/native
                break;
            case 9:
                return 'JDPAY';//京东扫码 http://api.sxzf88.com/sig/v1/jd/native
                break;
            case 12:
                return 'TENPAY_H5';//QQWAP http://api.sxzf88.com/sig/v1/qq/wap
                break;
            case 17:
                return 'UNIONQRPAY';//银联钱包扫码 http://api.sxzf88.com/sig/v1/union/native
                break;
            case 25:
                return 'QUICKPAY';//银联快捷 http://api.sxzf88.com/sig/v1/union/quick
                break;
            default:
                return 'WXZF';//微信扫码
                break;
        }
    }

    /**
     * @param $data 支付参数
     * @return return  二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为XML格式 将数组转化成XML格式
        $xml = ToXml($pay_data);
        $data = post_pay_data($this->url,$xml,'xml');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为XML格式 转化为数组
        $data = FromXml($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['result_code']) || ('0' <> $data['result_code'])
            || (empty($data['pay_info']) && empty($data['code_url'])))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败: {$msg}");
        }
        //扫码支付返回 二维码地址 wap支付返回支付地址
        if (!empty($data['code_url']))
        {
            $pay_url = $data['code_url']; //二维码地址
        } else {
            $pay_url = $data['pay_info']; //wap支付地址
        }
        return $pay_url;
    }
    /**
     * 创建扫码支付数据
     * @param array $data 支付参数
     * @return array
     */
    protected function buildScan($data)
    {
        //第三方支付返回 二维码地址
        $qrcode_url = $this->getPayResult($data);
        $res = [
            'jump'      => 2,
            'img'       => $qrcode_url,
            'money'     => $this->money,
            'order_num' => $this->orderNum,
        ];
        return $res;
    }
}