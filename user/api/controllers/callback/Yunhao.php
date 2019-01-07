<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/3
 * Time: 16:31
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Yunhao extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'Yunhao';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $tf = 'returncode'; //支付状态参数字段名
    protected $tc = '00'; //支付状态成功的值
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
        // 构造验证签名字符串
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        unset($data['reserved1']);
        unset($data['reserved2']);
        unset($data['reserved3']);
        ksort($data);
        //把数组参数以key=value形式拼接最后加上$key_string值
        $sign_string = $this->Params($data).$k;
        $flag =strtoupper(md5($sign_string));
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }

    /**
     * 将数组的键与值用符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
    protected function Params($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != $this->field && $v != "" && !is_array($v)){
                $buff .= $k . "=>" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
}