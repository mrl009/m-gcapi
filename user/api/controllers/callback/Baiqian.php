<?php
/**
 * 百钱支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/30
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Baiqian extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'BAIQIAN';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'MD5info'; //签名参数
    protected $of = 'BillNo'; //订单号参数
    protected $mf = 'Amount'; //订单金额参数(实际支付金额)
    protected $tf = 'Succeed'; //支付状态参数字段名
    protected $tc = '88'; //支付状态成功的值
    protected $vs = ['MerNo']; //参数签名字段必需参数
    protected $ks = '&'; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    protected function verifySign($data,$key,$name)
    {
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串 并验证签名
        $sdata = array(
            'MerNo' => $data['MerNo'],
            'BillNo' => $data['BillNo'],
            'Amount' => $data['Amount'],
            'Succeed' => $data['Succeed']
        );
        ksort($sdata);
        $k = $this->ks . strtoupper(md5($key)); 
        $string = ToUrlParams($sdata) . $k;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
