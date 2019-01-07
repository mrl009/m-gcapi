<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 */
include_once __DIR__.'/Publicpay.php';
class Nangua extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'NANGUA';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'payFlag'; //支付状态参数字段名
    protected $tc = '2'; //支付状态成功的值
    protected $vs = []; //参数签名字段必需参数
    protected $ks = '#'; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X
    protected $pk = 'pay_key'; //第三方key对应的数据库中的字段

    public function __construct()
    {
        parent::__construct();
    }
}