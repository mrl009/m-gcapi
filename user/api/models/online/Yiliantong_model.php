<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 易联通支付接口调用
 * User: lqh
 * Date: 2018/08/28
 * Time: 09:30
 */
include_once __DIR__.'/Publicpay_model.php';

class Yiliantong_model extends Publicpay_model
{
    protected $c_name = 'yiliantong';
    private $p_name = 'YILIANTONG';//商品名称
    //支付接口签名参数 
    private $key_string = '&key='; //参与签名组成

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
        return $this->buildWap($data);
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
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['funCode'] = '2005';
        $data['platOrderId'] = $this->orderNum;
        $data['platMerId'] = $this->merId;
        $data['amt'] = yuan_to_fen($this->money);
        $data['body'] = $this->p_name;
        $data['subject'] = $this->p_name;
        $data['tradeTime'] = time();
        $data['payMethod'] = $this->getPayType();
        $data['funName'] = 'prepay';
        $data['orderTime'] = 30;
        $data['notifyUrl'] = $this->callback;
        $data['frontUrl'] = $this->returnUrl;
        if (7 == $this->code) $data['tradeType'] = $this->bank_type;
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
                return '0';//微信
                break;
            case 4:
            case 5:
                return '1';//支付宝
                break;
            case 7:
                return '7';//网银支付
                break;
            case 8:
            case 12:
                return '2';//QQ
                break;
            case 9:
            case 13:
                return '3';//京东
                break;
            case 17:
                return '6';//银联扫码
                break;
            case 25:
                return '4';//快捷
                break;
            default:
                return '1';//支付宝
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
        //传递的参数json数据
        $pay_data = json_encode($pay_data);
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $post['reqJson'] = $pay_data;
        $post = http_build_query($post);
        $data = post_pay_data($this->url,$post);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式(非纯json数据) 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['shortUrl']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['shortUrl'];
    }
}
