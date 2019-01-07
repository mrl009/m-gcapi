<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Dongfang extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'DONGFANG';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'record'; //订单号参数
    protected $mf = 'rmb'; //订单金额参数(实际支付金额)
    protected $vs = ['appid','rmb','record']; //参数签名字段必需参数
    protected $vt = 'fen';//金额单位
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
        $k = $key;
        $sign  = $data[$this->sf];
        //把数组参数以key=value形式拼接最后加上$key_string值
        $signStr = $data['appid'].$data['rmb'].$data['record'].$k;
        $vSign =md5($signStr);
        if ($vSign <> $sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}