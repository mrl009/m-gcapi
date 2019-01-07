<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/19
 * Time: 14:04
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Wanmei extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'WANWEI';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNum'; //订单号参数
    protected $md = 'mchId'; //第三方平台返回的商户号字段
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vt = 'fen';//金额单位
    protected $tf = 'state'; //支付状态参数字段名
    protected $tc = 'success'; //支付状态成功的值

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
        $sign = $data[$this->sf];
        $string = $data[$this->md] .$key. $data[$this->of] . $data[$this->mf];
        $flag = hash('sha256', $string) ;
        if ($flag<>$sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}