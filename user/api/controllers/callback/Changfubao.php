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

class Changfubao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'CHANGFUBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'order_status'; //支付状态参数字段名
    protected $tc = '2'; //支付状态成功的值
    protected $vd = 0;
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
        $v_sign = $this->get_sign($data,$this->ks.$key);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }

    protected function get_sign($data,$ks){
        if (!empty($data) && is_array($data) && !empty($ks))
        {
            ksort($data);
            //把数组参数以key=value形式拼接最后加上$ks值
            $string = ToUrlParams($data) . $ks;
            //拼接字符串进行MD5大写加密
            $v_sign = md5($string);
            return $v_sign;
        }
    }
}
