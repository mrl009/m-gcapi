<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/30
 * Time: 15:00
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Shunchang extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'SHUNCHANG';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'organizationOrderCode'; //订单号参数
    protected $mf = 'orderPrice'; //订单金额参数(实际支付金额)
    protected $tf = 'orderPayStatus'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    private $method = 'X'; //小写
    protected $ks = '&token=';
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
        $f = $this->sf;
        $s = $this->method;
        $flag = get_pay_sign($data,$k,$f,$s);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}