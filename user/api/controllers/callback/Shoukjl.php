<?php
/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/26
 * Time: 22:06
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Shoukjl extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'SHOUKjl';
    //商户处理后通知第三方接口响应信息
    protected $error = 'ERROR'; //错误响应
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'shop_no'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $mt = 'X'; //返回签名是否大写 D/X
    protected $vd = 1; //是否使用用户商户号信息
    protected $vm = 0; //入款实际金额为准

    public function __construct()
    {
        parent::__construct();
    }
    protected function verifySign($data,$pay,$name)
    {
        // 构造验证签名字符串shop_id + user_id + order_no +sign_key+money+type
        $sign = $data[$this->sf];
        $string = $pay['pay_id'].$data['user_id'].$data['order_no'].$pay['pay_key'].$data['money'].$data['type'];
        $vsign = md5($string);
        if (strtolower($vsign) <> strtolower($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}