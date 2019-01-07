<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/27
 * Time: 16:46
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Lemei extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'LEMEI';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'OrderID'; //订单号参数
    protected $mf = 'FaceValue'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额
    protected $tf = 'PayState'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符
    protected $pk = 'pay_server_num'; //第三方key对应的数据库中的字段

    public function __construct()
    {
        parent::__construct();
    }

}