<?php
/**
 * 全球付支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 10:24
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Quanqiufu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'QUANQIUFU'; 
    //商户处理后通知第三方接口响应信息
    protected $error = 'FAIL'; //错误响应
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'mchorderid'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $vm = '1';//是否验证金额 
    protected $vt = 'fen';//金额单位
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
}
