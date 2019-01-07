<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/23
 * Time: 11:31
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Lintongbao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'LINTONGBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $mt = 'D'; //返回签名是否大写 D/X
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }
}