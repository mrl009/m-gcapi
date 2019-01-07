<?php

/**
 * 人人支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/17
 * Time: 19:32
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Renren extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'RENREN';
    //商户处理后通知第三方接口响应信息
    protected $success = "ok"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'oid'; //订单号参数
    protected $mf = 'tamt'; //订单金额参数(实际支付金额)
    protected $tf = 'code'; //支付状态参数字段名
    protected $tc = '100'; //支付状态成功的值
    protected $vm = 0;//验证金额(第三方实际支付可能金额不一致)
    protected $ks = '&paySecret='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 验证签名 (默认验证签名方法,部分第三方不一样)
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串md5($mid.$oid.$amt.$way.$code.$md5key);
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        $vdata = array(
            'mid'=>$data['mid'],
            'oid'=>$data['oid'],
            'amt'=>$data['amt'],
            'way'=>$data['way'],
            'code'=>$data['code'],
        );
        $string = data_value($vdata);
        $vsign = md5($string.$key);
        if (strtolower($vsign) <> strtolower($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}