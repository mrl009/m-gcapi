<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Hengfu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'HENGFUTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'corp_flow_no'; //订单号参数
    protected $mf = 'totalAmount'; //订单金额参数(实际支付金额)
    protected $tf = 'code'; //支付状态参数字段名
    protected $tc = '00'; //支付状态成功的值
    protected $vs = ['merchantId','corp_flow_no']; //参数签名字段必需参数

    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $sign = $data[$this->sf];
        $vSignStr = $data['merchantId'].'pay'.$data['corp_flow_no'];
        $vSign = md5($vSignStr.$key);
        if ($vSign != $sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
