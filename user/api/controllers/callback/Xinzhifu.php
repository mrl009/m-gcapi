
<?php

/**
 * 鑫支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/23
 * Time: 10:33
 */
defined('BASEPATH') or  exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Xinzhifu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'XZF';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'link_id'; //订单号参数
    protected $mf = 'bill_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'feeResult'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vt = 'fen';//金额单位
    protected $ks = '&key='; //参与签名字符串连接符
    protected $vm = 0;
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
        $sdata = array(
            'bill_no' => $data['bill_no'],
            'bill_fee' => $data['bill_fee'],
        );
       $vsign = md5(ToUrlParams($sdata) . $k);
        if (strtoupper($vsign) <> strtoupper($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}