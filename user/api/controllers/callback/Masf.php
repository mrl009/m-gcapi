<?php

/**
 * 马上付支付回调
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/7
 * Time: 16:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Masf extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'MSF';
    //商户处理后通知第三方接口响应信息
    protected $error = 'error'; //错误响应
    protected $success = "success"; //成功响应SF
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'receipt_amount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'trade_state'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $key_string = '&key='; //支付状态成功的值

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
        //获取签名字段并删除不参与签名字段mch_id={0}&out_trade_no={1}&fee_type={2}&pay_type={3}&total_amount={4}&receipt_amount={5}&device_info={6}&key={7}
        $sign = $data[$this->sf];
        $k = $this->key_string . $key;
        unset($data[$this->sf]);
        $sdata = array(
              'mch_id' => $data['mch_id'],
              'out_trade_no' => $data['out_trade_no'],
              'fee_type' => $data['fee_type'],
              'pay_type' => $data['pay_type'],
              'total_amount' => $data['total_amount'],
              'receipt_amount' => $data['receipt_amount'],
              'device_info' => $data['device_info'],
        );
        //获取签名字符串
        $string = ToUrlParams($sdata) . $k;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtolower($sign) <> strtolower($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}