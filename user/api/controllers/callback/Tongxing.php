<?php

/**
 * 通兴回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/23
 * Time: 14:47
 */
defined('BASEPATH') or exit('No direct script access allowed');
//公共文件调用
include_once __DIR__.'/Publicpay.php';
class Tongxing extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'Tongxing';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'sdorderno'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $vt = 'fen';//金额单位分
    protected $vm = 0;//以实际支付金额为准
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&'; //支付状态成功的值
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 验证签名 (默认验证签名方法,部分第三方不一样)
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        $sdata = [
            'customerid' => $data['customerid'],
            'status' => $data['status'],
            'sdpayno' => $data['sdpayno'],
            'sdorderno' => $data['sdorderno'],
            'total_fee' => $data['total_fee'],
            'paytype' => $data['paytype'],
        ];
        $string = strtolower(data_to_string($sdata) .$k);
        $vsign = md5($string);
        if (strtolower($sign) <> $vsign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}