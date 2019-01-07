<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 顺畅支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/30
 * Time: 10:28
 */
include_once __DIR__.'/Publicpay_model.php';

class Shunchang_model extends Publicpay_model
{
    protected $c_name = 'shunchang';
    private $p_name = 'SHUNCHANG';//
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $method = 'X'; //小写
    private $sk='&token=';//签名方式参数名

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
        $k = $this->sk.$this->key;
        $f = $this->field;
        $s = $this->method;
        $data = get_pay_sign($data,$k,$f,$s);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['organizationId'] = $this->merId;// 商户在支付平台的的平台号
        $data['organizationOrderCode'] = $this->orderNum;// 订单号
        $data['payment'] = $this->getPayType();// 商户在支付平台支付方式
        $data['orderPrice'] = $this->money;// 金额
        $data['notifyUrl'] = $this->callback;// 商户通知地址
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
                return 'WxpayQrcode';//微信扫码
                break;
            case 4:
                return 'AlipayH5';//支付宝扫码
                break;
            case 5:
                return 'AlipayH5';//支付宝H5
                break;
            case 8:
                return 'QqpayQrcode';//qq扫码
                break;
            default:
                return 'AlipayH5';
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
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        if (!isset($data['code']) || (0 <> $data['code'])
             ||empty($data['data']['payUrl']))
        {
            if (!empty($data['code'])) $msg = $data['code'];
            if (!empty($data['msg'])) $msg = $data['msg'];
            $msg = isset($msg) ? $msg : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = strtolower($data['data']['payUrl']);//wap支付地址或者二维码地址
        return $pay_url;
    }
}