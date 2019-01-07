<?php
/**
 * 360支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Pay360_model extends Publicpay_model
{
    protected $c_name = 'pay360';
    private $p_name = 'PAY360';//商品名称
    private $ks = '&key='; //参与签名组成

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
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0.0';
        $data['merId'] = $this->merId;
        $data['orderNo'] = $this->orderNum;
        $data['orderAmt'] = $this->money;
        $data['thirdChannel'] = $this->getPayType();
        $data['remark1'] = $this->p_name;
        $data['remark2'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['callbackUrl'] = $this->returnUrl;
        $data['payprod'] = $this->getProdType();
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code 
     * @return string 交易类型 参数
     */
    private function getProdType()
    {
        if (in_array($this->code,$this->wap_code))
        {
            return '10';
        } else {
            return '11';
        }
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
            case 2:
                return 'wxpay';//微信
                break;
            case 4:
            case 5: 
                return 'alipay';//微信WAP
                break;
            default:
                return 'alipay';//支付宝
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
        //傳遞參數為json數據  json数据转义替换
        $pay_data = json_encode($pay_data,true);
        $pay_data = str_replace('\/','/',$pay_data);
        $pay_data = str_replace('\/\/','//',$pay_data);
        $data = post_pay_data($this->url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
         $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['jumpUrl']))
        {
            $msg = isset($data['respMsg']) ? $data['respMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['jumpUrl'];
    }
}
