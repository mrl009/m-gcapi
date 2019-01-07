<?php
/**
 * 15173支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/01
 * Time: 14:12
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Yao15173 extends Publicpay
{
    //redis错误标识名称
    protected $r_name = '15173';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'sp_billno'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额
    protected $tf = 'pay_result'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vs = ['pay_result','attach','bargainor_id']; 
    protected $ks = '&key='; //参与签名字符串连接符

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
        //获取签名
        $sign = $data[$this->sf];
        //参与签名的字段信息
        $sign_data['pay_result'] = $data['pay_result'];
        $sign_data['bargainor_id'] = $data['bargainor_id'];
        $sign_data['sp_billno'] = $data['sp_billno'];
        $sign_data['total_fee'] = $data['total_fee'];
        $sign_data['attach'] = $data['attach'];
        //构造签名字符串
        $k = $this->ks . $key;
        $sign_string = ToUrlParams($sign_data) . $k;
        $v_sign = strtoupper(md5($sign_string));
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
