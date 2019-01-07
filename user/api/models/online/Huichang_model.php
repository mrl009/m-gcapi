<?php
/**
 * 会昶支付接口调用
 * User: lqh
 * Date: 2018/05/09
 * Time: 09:27
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Huichang_model extends Publicpay_model
{
    protected $c_name = 'huichang';
    private $p_name = 'HUICHANG';//商品名称

    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $field = 'sign'; //签名参数名*/

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数  参数顺序不能修改
     * @return array
     */
    private function getBaseData()
    {
        $data['merchNo'] = $this->merId;
        $data['orderNo'] = $this->orderNum;
        //网关支付 特殊参数
        if (7 == $this->code) $data['bankChannel'] = $this->bank_type;
        $data['transAmount'] = (string)yuan_to_fen($this->money);//单位分
        if (7 <> $this->code) $data['productName'] = $this->p_name;
        if (in_array($this->code,[7,12]))  
        {
            $data['pageUrl'] = $this->returnUrl;
        }
        $data['notifyUrl'] = $this->callback; 
        if (in_array($this->code,[2,5,12]))
        {
            $data['deviceIp'] = get_ip(); 
        }
        return $data;
    }

    /**
     * 获取支付网关地址 
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay_data=[])
    {
        switch ($this->code)
        {
            case 1: 
                return 'http://47.98.115.134:8080/app/doWXPay.do';//微信扫码
                break;
            case 2: 
                return 'http://47.98.115.134:8080/app/doWXH5Pay.do';//微信WAP
                break;
            case 4: 
                return 'http://47.98.115.134:8080/app/doALIPay.do';//支付宝扫码
                break;
            case 5: 
                return 'http://47.98.115.134:8080/app/doALIH5Pay.do';//支付宝WAP
                break;
            case 7: 
                return 'http://47.98.115.134:8080/app/doGWPay.do';//网银支付
                break;
            case 8: 
                return 'http://47.98.115.134:8080/app/doQQPay.do';//QQ钱包扫码
                break;
            case 9: 
                return 'http://47.98.115.134:8080/app/doJDPay.do';//京东扫码
                break;
            case 12: 
                return 'http://47.98.115.134:8080/app/doQQH5Pay.do';//QQ钱包WAP
                break;    
            case 17: 
                return 'http://47.98.115.134:8080/app/doCUPPay.do';//银联扫码
                break;
            default:
                return 'http://47.98.115.134:8080/app/doWXPay.do';//微信扫码
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
        //传递参数为JSON格式 将数组转化成JSON格式
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (!isset($data['respCode']) || ('00000' <> $data['respCode'])
           || empty($data['qrcodeUrl']))
        {
            $msg = isset($data['respDesc']) ? $data['respDesc'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}"); 
        }
        return $data['qrcodeUrl'];
    }
}
