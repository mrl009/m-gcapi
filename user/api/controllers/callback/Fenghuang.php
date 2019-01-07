<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Fenghuang extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'FENGHUANG';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_id'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $tf = 'state'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = ''; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X
    protected $vt = 'fen';//金额单位
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)

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
        unset($data['extend']);
        ksort($data);
        $signStr = data_to_string($data);
        $vSign = md5($signStr.$k);
        if ($vSign != $sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
