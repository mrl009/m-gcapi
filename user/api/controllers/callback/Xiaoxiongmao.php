<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/22
 * Time: 11:31
 */
defined('BASEPATH')or exit('No such script Accesss');
//调用公共文件
include_once __DIR__.'/Publicpay.php';
class Xiaoxiongmao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'XXM';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $vm = 0;//实际到账金额
    protected $ks = ''; //参与签名字符串连接符
    protected $mt = ''; //返回签名是否大写 D/X

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
        $sign = $data[$this->sf];
        $vsign = md5(floatval($data['money']).trim($data['orderid']). $key);
        if (strtolower($vsign) <> strtolower($sign))
        {
            $msg = "签名验证失败:{$vsign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}