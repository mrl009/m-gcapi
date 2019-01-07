<?php

/**
 * 聚诚通支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/6
 * Time: 15:03
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Juchentong_model extends Publicpay_model
{
    protected $c_name = 'juchentong';
    protected $p_name = 'JCT';//商品名称

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
        $sign_data = array_values($data);
        $sign_string = implode('',$sign_data);
        $data['sign'] = sha1($sign_string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['uid'] = $this->merId;
        $data['pay_type'] = $this->getPayType();
        $data['pay_id']   = "0";
        $data['group_id'] = "0";
        $data['goods_name'] = $this->c_name;
        $data['order_id'] = $this->orderNum;
        $data['price'] = $this->money;
        $data['notify_url'] = $this->callback;
        $data['exact'] = "false";
        $data['sign_type'] = "sha1";
        $data['token'] = $this->key;
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
            case 2:
                return '1';//微信扫码WAP
                break;
            case 4:
            case 5:
                return '2';//支付宝扫码WAP
                break;
            case 8:
            case 12:
                return '3';//qq钱包
                break;
            default:
                return '2';//支付宝扫码
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
        $this->url = $this->url.'/api/post';
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['success']) || (true> $data['success'])
            || empty($data['qrcode']) )
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['qrcode'];
    }
}