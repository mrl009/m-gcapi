<?php

/**
 * 派瑞支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/8
 * Time: 10:31
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Pairui_model extends Publicpay_model
{
    protected $c_name = 'pairui';
    private $p_name = 'PAIRUI';//商品名称
    private $key_string = '&key=';

    public function __construct(){
        parent::__construct();
    }
    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        return $this->buildWap($data);

    }
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $k = $this->key_string.$this->key;
        ksort($data);
        $string = http_build_query($data);
        $data['sign'] = strtoupper(md5(urldecode($string).$k));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['merchant_no'] = $this->merId;//商户号
        $data['nonce_str']   = uniqid(); //随机字符串
        $data['request_no'] = $this->orderNum;//订单号
        $data['pay_channel'] =$this->getPayType();//支付通道
        $data['request_time'] = time();
        $data['goods_name'] = $this->p_name;//商品名称
        $data['amount'] = intval($this->money);//订单金额
        $data['ip_addr'] = rand(1,254).'.'.rand(1,254).'.'.rand(1,254).'.'.rand(1,254);
        $data['notify_url'] = $this->callback;//回调地址
        $data['return_url'] = $this->returnUrl;//可选
        return $data;
    }

    private function getPayType(){
        switch ($this->code){
            case 1:
                return 'WXP';//微信扫码
                break;
            case 2:
                return 'WXH5';//微信扫码
                break;
            case 4:
                return 'ALP';//支付宝扫码
                break;
            case 5:
                return 'ALH5';//支付宝H5
                break;
            case 7:
                return 'WYP';//网银
                break;
            case 8:
                return 'QQP';//QQ扫码
                break;
            case 9:
                return 'JDP';//京东扫码
                break;
            case 13:
                return 'JDH5';//京东H5
                break;
            case 17:
                return 'YLP';//银联扫码
                break;
            case 25:
                return 'KJP';//快捷支付
                break;
            default:
                return 'ALP';
                break;
        }

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $this->url = $this->url.'/v1/order';
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if ( $data['success'] <> ture){
            $msg = isset($data['data']['message']) ? $data['data']['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['data']['bank_url'];
        return $pay_url;
    }

}