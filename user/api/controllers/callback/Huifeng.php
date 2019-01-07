<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Huifeng extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'HUIFENG';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_id'; //订单号参数
    protected $mf = 'price'; //订单金额参数(实际支付金额)
    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data,$key,$name)
    {
        $sign = $data[$this->sf];
        $vSignStr = $data['order_id'].$data['price'].$data['txnTime'].$key;
        $vSign = md5($vSignStr);

        if ($vSign != $sign){
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }

}
