<?php

/**
 * 乐无限支付接口
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2019/1/2
 * Time: 11:00
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Lewuxian extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'LEWUXIAN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderIdCp'; //订单号参数
    protected $mf = 'fee'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额
    protected $vt = 'fen';//金额单位
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值

    public function __construct()
    {
        parent::__construct();
    }
    //：fee、orderIdCp、version
    /**
     * 验证签名
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        $sdata = [
            'fee' => $data['fee'],
            'orderIdCp' => $data['orderIdCp'],
            'version' => $data['version'],
        ];
        ksort($sdata);
        //把数组参数以key=value形式拼接最后加上$key值 空值也参与签名
        $sign_string = ToUrlParams($sdata)."&" . $key;
        $v_sign = md5($sign_string);
        if (strtoupper($sign) <>  strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}