<?php

/**
 * 新利宝支付回调接口
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/24
 * Time: 14:27
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*调用公共的回调类*/
include_once __DIR__.'/Publicpay.php';
class Xinlibao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'XINLIBAO';
    protected $success = "ok"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'ovalue'; //订单金额参数(实际支付金额)
    protected $tf = 'restate'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vm = 0;

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
        // 构造验证签名字符串 orderid={}&restate={}&ovalue={}key
        $sign = $data[$this->sf];
        $s_data = array(
            'orderid' => $data['orderid'],
            'restate' => $data['restate'],
            'ovalue'  => $data['ovalue'],
        );
        //把数组参数以key=value形式拼接最后加上$key值
        $string = data_to_string($s_data) . $key;
        $vsign  =  md5($string);
        if (strtolower($sign) <> strtolower($vsign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}