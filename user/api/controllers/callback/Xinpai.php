<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/4
 * Time: 10:53
 */
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Xinpai extends Publicpay

{
    //redis错误标识名称
    protected $r_name = 'XINPAI';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'outTradeNo'; //订单号参数
    protected $mf = 'orderPrice'; //订单金额参数(实际支付金额)
    protected $tf = 'resultCode'; //支付状态参数字段名
    protected $tc = '0000'; //支付状态成功的值
    protected $vs = [];
    protected $ks = '&paySecret='; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }
}