<?php

/**
 * 多付支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/14
 * Time: 16:05
 */
class Duopay extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'DUOPAY';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'merOrderNo'; //订单号参数
    protected $mf = 'orderAmount'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'tradeStatus'; //支付状态参数字段名
    protected $tc = 'SUCCESS';//支付状态成功的值
    protected $method = 'D';//支付状态成功的值
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
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        $f = $this->sf;
        $s = $this->method;
        $flag = verify_pay_sign($data,$k,$f,$s);
        //验证签名是否一致
        if ($sign <> $flag)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}