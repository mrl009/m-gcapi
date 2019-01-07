<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/29
 * Time: 13:43
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Shoukuanbao extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'SHOUKUANBAO';
    //商户处理后通知第三方接口响应信息
    protected $error = 'ERROR'; //错误响应
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = ''; //签名参数
    protected $of = 'shop_no'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vs = ['shop_id','user_id','order_no','money','type']; //参数签名字段必需参数
    protected $mt = 'X'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
}