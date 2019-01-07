<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/31
 * Time: 11:16
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Api extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'TIANZE';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'hmac'; //签名参数
    protected $of = 'r6_Order'; //订单号参数
    protected $mf = 'r3_Amt'; //订单金额参数(实际支付金额)
    protected $tf = 'r1_Code'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值

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
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串

        //去除不参与签名得参数
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        unset($data['rb_BankId']);
        unset($data['rp_PayDate']);
        unset($data['rq_CardNo']);
        //构造签名参数
        ksort($data);
        $string = $this->ToParams($data);
        $flag= HmacMd5($string,$this->key);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }

    protected function ToParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= "" . $v ;
            }
        }
        $buff = trim($buff, "");
        return $buff;
    }
}