<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/5
 * Time: 19:43
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Jiefutong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'JIEFUTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'fxsign'; //签名参数
    protected $of = 'fxddh'; //订单号参数
    protected $mf = 'fxfee'; //订单金额参数(实际支付金额)
    protected $tf = 'fxstatus'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值


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
        $sign = $data['fxsign'];
        $sdata = [
          'fxstatus' =>  $data["fxstatus"],
          'fxid' =>  $data["fxid"],
          'fxddh'=>  $data["fxddh"] ,
          'fxfee'=> $data["fxfee"]
        ];
        ksort($data);
        $k =$this->key;
        $string = $this->Params($data) . $key;
        $flag = strtolower(md5($string));
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
            if($k <>$this->field && $v <> ""
                && !is_array($v)&& $v <>null ){
                $buff .= $v;
            }
        }
        return $buff;
    }

}