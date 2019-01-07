<?php

/**
 * OKpay微信通道回调
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/1
 * Time: 11:16
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Okpay extends  Publicpay
{
    //redis错误标识名称
    protected $r_name = 'Okpay';
    //redis错误标识名称
    //商户处理后通知第三方接口响应信息
    protected $error = 'false'; //错误响应
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'record'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    protected function verifySign($data,$key,$name)
    {
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $string = md5($data['old_money'].$data['order_id'].$data['sdk'].$key);
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtolower($sign) <> strtolower($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}