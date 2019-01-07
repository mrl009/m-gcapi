<?php

/**
 * 新E付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/26
 * Time: 19:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Xingefu_model extends Publicpay_model
{
    protected $c_name = 'xingefu';
    private $p_name = 'XEF';//商品名称

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
        $json = json_encode($data,JSON_UNESCAPED_SLASHES);
        $data['partner']   = $this->merId;
        $data['encryptType']= 'md5';
        $data['msgData']   = base64_encode($json);
        $data['signData']  = md5($json.$this->key);
        $data['version']   = 'V2.0';
        $data['reqMsgId']  = $this->orderNum;
        //$data['accountNo'] = $this->user['id'];
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchId'] = $this->merId;
        $data['orderId'] = $this->orderNum;
        $data['payWay'] = $this->getPayType();
        $data['totalAmt'] = yuan_to_fen($this->money);
        $data['curType'] = 'CNY';
        $data['tranTime'] = date('YmdHis',time());
        $data['title'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['clientIp'] = get_ip();
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
                return 'weixin';//微信
                break;
            case 2:
                return 'weixinH5';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipayH5';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'qqpay';//QQ钱包扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqpaywap';//QQWAP
                break;
            case 13:
                return 'jdwap';//QQWAP
                break;
            case 18:
                return 'unionpay';//银联钱包
                break;
            default :
                return 'alipay';
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
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        //接收参数为JSON格式 转化为数组
        $data = base64_decode($data['msgData']);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrCode'])|| $data['respCode'] == "0000")
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['qrCode'];
    }
}