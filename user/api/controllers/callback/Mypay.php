<?php
/**
 * MyPay支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/05
 * Time: 15:55
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Mypay extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'MYPAY';
    //商户处理后通知第三方接口响应信息
    protected $success = "MyPay"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'merchantNo'; //订单号参数
    protected $mf = 'realAmount'; //订单金额参数(实际支付金额)
    protected $vs = ['amount']; //参数签名字段必需参数
    protected $ks = '&key='; //参与签名字符串连接符
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
        //获取签名信息
        $sign = $data[$this->sf];
        // 构造验证签名字符串
        unset($data['sign']);
        unset($data['extra']);
        unset($data['clientIp']);
        ksort($data);
        $k = $this->ks . $key;
        $string = ToUrlParams($data) . $k;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
