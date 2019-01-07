<?php
/**
 * 数科宝支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/16
 * Time: 14:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Shukebao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'SHUKEBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_no'; //订单号参数
    protected $mf = 'order_amount'; //订单金额参数(实际支付金额)
    protected $tf = 'order_status'; //支付状态参数字段名
    protected $tc = 'success'; //支付状态成功的值
    protected $vs = ['merchant_code','interface_version','trade_no','product_number','order_success_time','order_time','bank_code']; //参数签名字段必需参数
    protected $ks = '~|~'; //参与签名字符串连接符

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
            $data['merchant_code'],$data['interface_version'],
            $data['order_no'],$data['trade_no'],$data['order_amount'],
            $data['product_number'],$data['order_success_time'],
            $data['order_time'],$data['order_status'],$data['bank_code']
        );
        $string = implode('~|~', $sign_data) . $this->ks . $key; 
        $v_sign = md5($string);
        unset($sign_data);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
