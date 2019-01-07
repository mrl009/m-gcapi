<?php

defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';

class Hexing_model extends Publicpay_model
{
    protected $c_name = 'hexing';
    private $p_name = 'HEXING';//商品名称
    //参与签名参数
    private $key_string = '&appkey=';
    private $field='sign';
    private $method='X';
    public function __construct()
    {
        parent::__construct();
    }

    protected function returnApiData($data)
    {
            return $this->buildForm($data);
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
        $f = $this->field;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$this->method);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['appid'] = $this->merId;
        $data['orderid'] = $this->orderNum;
        $data['money'] = $this->money;
        $data['paycode'] = $this->getPayType();
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        $data['goodsname'] = $this->p_name;
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
            case 2:
                return 'wxscan';//微信扫码
                break;
            case 4:
            case 5:
                return 'aliscan';//支付宝扫码
                break;
            case 7:
                return 'wypay';//网银
                break;
            case 9:
                return 'jdpay';//京东
                break;
            case 17:
                return 'ylscan';//银联
                break;
            default:
                return 'wxscan';
                break;
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    /*protected function getPayResult($pay_data)
    {

        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        var_dump($data);die;
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        if (empty($data['url']))
        {
            $msg = '返回参数错误';
            if (!empty($data['msg'])) $msg = $data['msg'];
            if (!empty($data['message'])) $msg = $data['message'];
            $msg = mb_convert_encoding($msg,"GBK","UTF-8");
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['url'];
    }*/
}