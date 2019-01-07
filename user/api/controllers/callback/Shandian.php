<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Shandian extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'SHANDIAN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'sdorderno'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&'; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data,$key,$name)
    {
        $sign = $data[$this->sf];
        $k = $this->ks . $key;
        $signData = [
            'customerid' => $data['customerid'],
            'status' => $data['status'],
            'sdpayno' => $data['sdpayno'],
            'sdorderno' => $data['sdorderno'],
            'total_fee' => $data['total_fee'],
            'paytype' => $data['paytype'],
        ];
        $signStr = data_to_string($signData);
        $vSign = md5($signStr.$k);
        if ($vSign != $sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
