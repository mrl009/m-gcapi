<?php
/**
 * 千信支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 10:14
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Qianxin extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'QIANXIN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'sdorderno'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $vs = ['status','sdpayno','paytype','customerid']; 
    protected $ks = '&'; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }

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
        //参与签名的字段信息
        $sign_data['customerid'] = $data['customerid'];
        $sign_data['status'] = $data['status'];
        $sign_data['sdpayno'] = $data['sdpayno'];
        $sign_data['sdorderno'] = $data['sdorderno'];
        $sign_data['total_fee'] = $data['total_fee'];
        $sign_data['paytype'] = $data['paytype'];
        //构造签名字符串
        $k = $this->ks . $key;
        $sign_string = ToUrlParams($sign_data) . $k;
        $v_sign = md5($sign_string);
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
