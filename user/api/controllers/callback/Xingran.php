<?php
/**
 * 星染支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/21
 * Time: 11:35
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Xingran extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'XINGRAN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'hmac'; //签名参数
    protected $of = 'orderno'; //订单号参数
    protected $mf = 'amunt'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'state'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $vs = ['orderid','date']; //参数签名字段必需参数


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
        //构造签名字符串 (参数顺序不能错)
        $string = $data['orderno'] . $data['state'];
        $string .= $data['amunt'] . $data['orderid'] . $data['date'];
        $v_sign = hash_hmac("md5",$string,$key,false);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
