<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/6/19
 * Time: 16:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Jinmu extends  Publicpay
{ //redis错误标识名称
    protected $r_name = 'JINMU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderno'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $vm = '1';//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位为分
    protected $tf = 'errcode'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vs = ['attach']; //参数签名字段必需参数

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
        $sign = $data[$this->sf];
        //删除不参与签名的数据
        unset($data[$this->sf]);
        //构造签名字符串
        ksort($data);
        $string = implode('',array_values($data)) . $key;
        $v_sign = strtoupper(md5($string));
        //验证签名是否一致
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}