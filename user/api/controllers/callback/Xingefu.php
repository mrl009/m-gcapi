<?php

/**
 * 新e付支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/27
 * Time: 14:54
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Xingefu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'XEF';
    //商户处理后通知第三方接口响应信息
    protected $success = "000000"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'signData'; //签名参数
    protected $of = 'orderId'; //订单号参数
    protected $mf = 'totalAmount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
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
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        //获取签名字符串
        $v_sign =md5($data['msgData'].$key);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
    /**
     * 获取异步返回数据
     * @access protected
     * @return array data
     */
    protected function getReturnData()
    {
        //获取异步返回数据
        $data = $_REQUEST;
        //redis记录支付错误信息标识
        $name = $this->r_name;
        if (empty($data) || empty($data['msgData']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //转换并解密数据
        $msgData = base64_decode($data['msgData'],true);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}", '返回数据不是64加密数据');
            exit($this->error);
        }
        $msgData['msgData']  = $data['msgData'];
        $msgData['signData'] = $data['signData'];
        if (empty($msgData))
        {
            $this->PM->online_erro("{$name}", '解密出来数据不是json数据');
            exit($this->error);
        }
        return $msgData;
    }
}