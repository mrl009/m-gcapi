<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Jiuyi extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'JIUYI';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'result_code'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值

    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data, $key, $name){
        $sign = $data[$this->sf];
        $signData = [
            'mch_id' => $data['mch_id'],
            'time_end' => $data['time_end'],
            'out_trade_no' => $data['out_trade_no'],
            'ordernumber' => $data['ordernumber'],
            'transtypeid' => $data['transtypeid'],
            'transaction_id' => $data['transaction_id'],
            'total_fee' => $data['total_fee'],
            'service' => $data['service'],
            'way' => $data['way'],
            'result_code' => $data['result_code'],
        ];
        $signStr = implode('', array_values($signData)) . $key;
        $vSign = md5($signStr);
        if (strtoupper($vSign) <> strtoupper($sign)){
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
