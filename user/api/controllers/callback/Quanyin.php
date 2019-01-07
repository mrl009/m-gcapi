<?php
/**
 * 全银支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 12:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Quanyin extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'QUANYIN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'orderPrice'; //订单金额参数(实际支付金额)
    protected $tf = 'tradeStatus'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值
    protected $ks = '&paySecret='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
}
