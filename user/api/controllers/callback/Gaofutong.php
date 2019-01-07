<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/24
 * Time: 16:25
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Gaofutong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'GAOFUTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'orderAmount'; //订单金额参数(实际支付金额)
    protected $tf = 'orderStatus'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值
    protected $vt = 'fen';//金额单位
    protected $ks = ''; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
}