<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Yunzhifu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'YUNZHIFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'outTradeNo'; //订单号参数
    protected $mf = 'totalFee'; //订单金额参数(实际支付金额)
    protected $vm = '1';//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'orderState'; //支付状态参数字段名
    protected $tc = '支付成功'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X
    protected $vo = 0; //订单号参数是否直接获取订单号
    protected $md = 'merchantId'; //第三方平台返回的商户号字段

    public function __construct()
    {
        parent::__construct();
    }

}
