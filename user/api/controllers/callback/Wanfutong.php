
<?php

/**
 * 万付通支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/3
 * Time: 13:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Wanfutong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'WFT';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'sdorderno'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $vm = 0;//金额不一致
    protected $vs = ['sdpayno']; //参数签名字段必需参数
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
        //获取签名字符串
        $sign_data = array(
            'customerid' => $data['customerid'],
            'status' => $data['status'],
            'sdpayno' => $data['sdpayno'],
            'sdorderno' => $data['sdorderno'],
            'total_fee' => $data['total_fee'],
            'paytype' => $data['paytype']
        );
        $string = data_to_string($sign_data) . $this->ks . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}