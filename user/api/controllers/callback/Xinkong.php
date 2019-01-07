<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/30
 * Time: 10:33
 */
include_once __DIR__.'/Publicpay.php';

class Xinkong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'XINKONG';
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderno'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $tf = 'result_code'; //支付状态参数字段名
    protected $tc = '200'; //支付状态成功的值
    protected $vs = ['notifyid','body']; //参数签名字段必需参数

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
        //获取签名数据
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //构造验证签名字符串
        $string = $data['notifyid'] . $data['orderno'];
        $string .= $data['amount'] . $data['body'] . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}