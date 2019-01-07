<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Shidai extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'SHIDAI';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'merordernum'; //订单号参数
    protected $mf = 'orderamt'; //订单金额参数(实际支付金额)
    protected $tf = 'respcode'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&'; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        $vSignData = [
            'merchantid'  =>  $data['merchantid'],
            'respcode'    =>  $data['respcode'],
            'ordernum'    =>  $data['ordernum'],
            'merordernum' =>  $data['merordernum'],
            'orderamt'    =>  $data['orderamt'],
            'paytype'     =>  $data['paytype'],
        ];
        $vSignStr = data_to_string($vSignData).$k;
        $vSign = md5($vSignStr);
        if ($vSign != $sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
