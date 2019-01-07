<?php
/**
* 码闪付回调接口
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/23
 * Time: 14:46
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Mashanfu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'MASHANFU';
    //商户处理后通知第三方接口响应信息
    protected $error = 'false'; //错误响应
    protected $success = "true"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNum'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'payStateCode'; //支付状态参数字段名
    protected $tc = '10'; //支付状态成功的值

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取异步返回数据
     * @access protected
     * @return array data
     */
    protected function getReturnData()
    {
        header("Content-Type:text/html;charset=UTF-8");
        //redis记录支付错误信息标识
        $name = $this->r_name;
        //获取数据
        $data = $_REQUEST;
        $temp = json_encode($data);
        $this->PM->online_erro("{$name}_REQUEST", "数据：{$temp}");
        if (empty($data) || empty($data['data']) 
            || empty($data['orderNo']) || empty($data['sign']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //根据订单号来获取秘钥
        $pay = $this->PM->order_detail($data['orderNo']);
        if (empty($pay) || empty($pay['pay_private_key']))
        {
            $msg = "无效的订单号:{$data['orderNo']}";
            $this->PM->online_erro($name, $msg);
            exit($this->error);
        }
        $this->p_key = $pay['pay_private_key'];
        //对接收到的参数进行转码
        $sign = $data['sign'];
        $data = base64_decode($data['data']);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}_MUST", '返回数据不是64加密数据');
            exit($this->error);
        }
        //使用商户私钥进行解密数据
        $json_data = $this->decodePay($data);
        if (empty($json_data)) 
        {
            $this->PM->online_erro("{$name}", '解密数据错误');
            exit($this->error);
        }
        $data = json_decode($json_data,true);
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}", '解密数据不是json格式');
            exit($this->error);
        }
        //根据需要构造加密参数数据
        $data['sign'] = $sign;
        $data['json'] = $json_data;
        return $data;
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    protected function verifySign($data,$key,$name)
    {
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $string = $data['json'] . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
    
    /*
     * 秘钥解密方式
     */
    private function decodePay($data)
    {
        $crypto = '';
        $name = $this->r_name;
        $pk = openssl_pkey_get_private($this->p_key);
        if (empty($pk)) 
        {
            $this->PM->online_erro("{$name}", '商户私钥解析错误');
            exit($this->error);
        }
        //分段解密   
        foreach (str_split($data, 128) as $chunk) 
        {
            openssl_private_decrypt($chunk, $decryptData, $pk);
            $crypto .= $decryptData;
        }
        return $crypto;
    }
}