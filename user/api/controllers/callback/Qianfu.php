<?php
/**
 * 乾富支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 09:08
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Qianfu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'QIANFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign_info'; //签名参数
    protected $of = 'order_sn'; //订单号参数
    protected $mf = 'totle_amount'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $vs = ['platform_sn','pay_type','mch_number','this_date'];

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
        //获取加密验证字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $string = $data['order_sn'] . $data['platform_sn'];
        $string .= $data['totle_amount'] . $data['mch_number'];
        $string .= $data['pay_type'] . $data['this_date'] . md5($key);
        $v_sign = md5($string);
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}