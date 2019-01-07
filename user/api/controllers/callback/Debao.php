<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/28
 * Time: 16:14
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Debao extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'DEBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_no'; //订单号参数
    protected $mf = 'order_amount'; //订单金额参数(实际支付金额)
    protected $tf = 'trade_status'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值
    protected $ks = '&'; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X
    protected $pk = 'pay_server_key'; //第三方key对应的数据库中的字段

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
        //获取签名
        $sign = str_replace(" ","+",$data[$this->sf]);
        $sign = base64_decode($sign);
        //删除不参与签名参数
        unset($data[$this->sf]);
        unset($data['sign_type']);
        //数组去重排序并获取加密字符串
        $data = array_filter($data);
        ksort($data);
        $str = ToUrlParams($data);
        $sk = openssl_get_publickey($key);
        //验证签名
        $flag = openssl_verify($str,$sign,$sk,OPENSSL_ALGO_MD5);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}