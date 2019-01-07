<?php

/**E付回调文件
 * Created by PhpStorm.
 * User: Daxiniu
 * Date: 2018/11/24
 * Time: 18:34
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Efu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'EFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    protected $private_key = ""; //商户私钥
    protected $server_key = ""; //平台公钥
    //异步返回必需验证参数
    protected $sf = 'signMsg'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $vd = 1; //是否使用用户商户号信息
    protected $mf = 'orderAmount'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额
    protected $vt = 'fen';//金额单位
    protected $tf = 'payResult'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符

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
    protected function verifySign($data,$pay,$name)
    {
        //解析用户服务端公钥
        if (empty($pay['pay_server_key']) || empty($pay['pay_private_key']))
        {
            $this->PM->online_erro("{$name}", '用户服务端私钥/公钥不存在');
            exit($this->error);
        }
        $sk = loadPubPriKey($pay['pay_server_key'],'');
        $sk = openssl_get_publickey($sk['publicKey']);
        if (empty($sk))
        {
            $this->PM->online_erro("{$name}", '第三方服务端公钥解析错误');
            exit($this->error);
        }
        //获取签名参数
        $sign = str_replace(" ","+",$data[$this->sf]);
        $sign = base64_decode($sign);
        unset($data['signType']);
        unset($data[$this->sf]);
        //构造签名字符串
        ksort($data);
        $string = ToUrlParams($data);
        //验证用户签名
        $verify = openssl_verify($string, $sign, $sk, OPENSSL_ALGO_SHA1);
        //验证签名是否正确
        if (!$verify)
        {
            $this->PM->online_erro($name, '签名验证失败');
            exit($this->error);
        }
    }
}