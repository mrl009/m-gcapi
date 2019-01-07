<?php

/**
 * 宜兴支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/24
 * Time: 11:34
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Yixing extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'YIXING';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'tran_amount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'resp_code'; //支付状态参数字段名
    protected $tc = '00'; //支付状态成功的值
    protected $ks = ''; //参与签名字符串连接符

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
        // 获取签名字符串 并去除不参与加密参数
        /*
         * amount=[]&appid=[]& currency_type=[] &goods_name=[]&out_trade_no=[] &pay_id=[]&pay_no=[] &payment=[] &resp_code=[] &resp_desc =[]&sign_type=[] &tran_amount =[]&version=[]MD5key*/
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //构造验证签名字符串
        ksort($data);
        $string = ToUrlParams($data) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}