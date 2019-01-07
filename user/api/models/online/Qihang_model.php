<?php

/**
 * 启航支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/3
 * Time: 18:29
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Qihang_model extends Publicpay_model
{
    protected $c_name = 'qihang';
    protected $p_name = 'QIHANG';

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
        return $this->buildForm($data,'get');
    }

    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        $string = implode('',array_values($data));
        $data['refer'] = $this->returnUrl;
        $data['notify_url'] = $this->callback;
        $data['sign'] = md5($string);
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['money'] = sprintf('%.2f',$this->money) ;
        $data['record'] = $this->orderNum;
        $data['sdk'] = $this->merId;
        return $data;
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //傳遞參數為json數據
        $pay_data = json_encode($pay_data,true);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        if (empty($data['image']))
        {
            $msg = isset($data['msgInfo']) ? $data['msgInfo'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }

        return $data['image'];
    }
}