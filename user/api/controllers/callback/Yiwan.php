<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/12
 * Time: 16:06
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Yiwan extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'YIWAN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_order_id'; //订单号参数
    protected $mf = 'realprice'; //订单金额参数(实际支付金额)
    protected $vd = 1; //是否使用用户商户号信息
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 验证签名 (默认验证签名方法,部分第三方不一样)
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$pay,$name)
    {
        ksort($data);
        $string ='';
        $data['mchid'] =$pay['pay_id'];
        foreach($data as $key => $val)
        {
            if (!is_array($val) && ('sign' <> $key)
                && ("" <> $val) && (null <> $val)
                && ("null" <> $val))
            {
                $string .= $val;
            }
        }
        $string = md5($string). $pay['pay_key'];
        //拼接字符串进行MD5大写加密
        $sign =strtolower(md5($string)) ;
        if ($sign<>$data['sign'])
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }

    }



}