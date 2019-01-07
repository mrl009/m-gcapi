<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/16
 * Time: 16:34
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Quanyuntong extends Publicpay
{
    //redis错误标识名称QUANYUNTO
    protected $r_name = 'QUANYUNTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'hmac'; //签名参数
    protected $of = 'r6_Order'; //订单号参数
    protected $mf = 'r3_Amt'; //订单金额参数(实际支付金额)
    protected $tf = 'r1_Code'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    //protected $vs = ['partner']; //参数签名字段必需参数


    public function __construct()
    {
        parent::__construct();
    }

}