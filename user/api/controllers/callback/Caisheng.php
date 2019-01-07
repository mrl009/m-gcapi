
<?php

/**
 * 财神支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/29
 * Time: 10:10
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共接口调用
include_once __DIR__.'/Publicpay.php';
class Caisheng extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'Caisheng';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'outTradeNo'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $vt = 'fen';//金额单位
    protected $tf = 'success'; //支付状态参数字段名
    protected $tc = true; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        $vdata = array(
            'merchantNo' => $data['merchantNo'],
            'no' => $data['no'],
            'nonce' => $data['nonce'],
            'timestamp' => $data['timestamp'],
        );
        ksort($data);
        //把数组参数以key=value形式拼接最后加上$ks值
        $string = ToUrlParams($vdata) . $k;
        //拼接字符串进行MD5大写加密
        $v_sign = strtoupper(md5($string));
        if (strtoupper($v_sign) <> strtoupper($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}