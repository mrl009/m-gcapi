<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Aipay extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'AIPAY';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'paymoney'; //订单金额参数(实际支付金额)
    protected $vt = 'fen';//金额单位
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = [1,2,3]; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X
    protected $vo = 0; //订单号参数是否直接获取订单号
    protected $md = 'merch_id'; //第三方平台返回的商户号字段

    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $ks = $this->ks . $key;
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        ksort($data);
        $vSignStr = '';
        foreach ($data as $k => $v){
            if (!is_array($v) && ('sign' <> $k)){
                $vSignStr .= "{$k}={$v}&";
            }
        }
        $vSignStr = trim($vSignStr, '&');
        $vSignStr = $vSignStr . $ks;
        $vSign = md5($vSignStr);

        if (strtoupper($vSign) != strtoupper($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
