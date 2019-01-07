
<?php

/**
 * 和通付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/21
 * Time: 13:47
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay.php';
class Hetongfu extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'HTF';
    //商户处理后通知第三方接口响应信息
    protected $success = '200'; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $vt = 'fen';//金额单位
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'code'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值

    public function __construct()
    {
        parent::__construct();
    }
}