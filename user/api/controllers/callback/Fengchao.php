
<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/19
 * Time: 12:49
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Fengchao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'FENGCHAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'fxsign'; //签名参数
    protected $of = 'fxddh'; //订单号参数
    protected $mf = 'fxfee'; //订单金额参数(实际支付金额)
    protected $tf = 'fxstatus'; //支付状态参数字段名
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
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串md5(fxstatus + 商户ID + fxddh + fxfee + 商户密钥)
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        $vsign = strtolower(md5($data['fxstatus'].$data['fxid'].$data['fxddh'].$data['fxfee'].$key));
        if (strtolower($sign) <> strtolower($vsign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}