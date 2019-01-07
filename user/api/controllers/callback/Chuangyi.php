<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Chuangyi extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'CHUANGYI';
    //商户处理后通知第三方接口响应信息
    protected $success = "ok"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $vs = ['out_trade_no','money']; //参数签名字段必需参数
    protected $vd = 1; //是否使用用户商户号信息

    public function __construct()
    {
        parent::__construct();
    }

    public function verifySign($data, $pay, $name){
        $sign = $data[$this->sf];
        $appid = $pay['pay_id'];
        $key = $pay['pay_key'];

        $vSignStr = $appid.$key.$data['out_trade_no'].$data['money'];
        $vSign = md5($vSignStr);
        if ($sign != $vSign){
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
