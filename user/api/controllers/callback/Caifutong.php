<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Caifutong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'CAIFUTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = '1';//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'payStatus'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '#'; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }

    protected function verifySign($data, $key, $name)
    {
        $sign = $data['sign'];
        $vSignStr = "amount={$data['amount']}&orderNo={$data['orderNo']}&transactionNo={$data['transactionNo']}$this->ks{$key}";
        $vSign = md5($vSignStr);

        if ($sign <> $vSign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
