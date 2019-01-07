<?php
/**
 * 极付支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/29
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Ai extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'AI';
    //商户处理后通知第三方接口响应信息
    protected $success = "200"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_no'; //订单号参数
    protected $mf = 'order_amount'; //订单金额参数(实际支付金额)
    protected $tf = 'result'; //支付状态参数字段名
    protected $tc = 'S'; //支付状态成功的值
    protected $ks = '&key=';

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
        //获取签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $sdata = array(
            'merchant_no' => $data['merchant_no'],
            'order_no'    => $data['order_no'],
            'order_amount'=> $data['order_amount'],
            'original_amount' => $data['original_amount'],
            'upstream_settle' => $data['upstream_settle'],
            'result' => $data['result'],
            'pay_time' => $data['pay_time'],
            'trace_id' => $data['trace_id'],
            'reserve' => $data['reserve'],
        );
        $string = data_to_string($sdata) . $this->ks.$key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtolower($sign) <> strtolower($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
