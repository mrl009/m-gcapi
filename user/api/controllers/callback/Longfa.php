<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/26
 * Time: 11:35
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Longfa extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'LONGFA';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'payStateCode'; //支付状态参数字段名
    protected $tc = '00'; //支付状态成功的值

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
        //redis记录支付错误信息标识
        $name = $this->r_name;
        //获取异步返回数据
        $data = $_REQUEST;
        //判断数据格式
        //如果是数组 转化成json记录数据库
        if(is_array($_REQUEST))
        {
            //数组转化成json 录入数据
            $temp = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
            $this->PM->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
            unset($temp);
            $data = $_REQUEST;
        }
        //如果json格式 记录数据 同时转化成数组
        if (is_string($_REQUEST) && (false !== strpos($_REQUEST,'{'))
            && (false !== strpos($_REQUEST,'}')))
        {
            $this->PM->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST);
            //json格式数据先进行转码
            $data = string_decoding($_REQUEST);
        }
        //判断是否是需要的数据
        //转化对象数组
        if (is_object($data)) $data = $this->object_to_array($data);
        if (empty($data) || empty($data['data']) || empty($data['orderNo'])) 
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //根据商户订单号获取当前支付模型的信息(加密key值等)
        $pay = $this->PM->order_detail($data['orderNo']);
        if (empty($pay))
        {
            $msg = "无效的订单号:{$order_num}";
            $this->PM->online_erro($name, $msg);
            exit($this->error);
        }
        //根据商户私钥解密数据
        $temp = base64_decode($data['data']);
        if (empty($temp))
        {
            $this->PM->online_erro("{$name}", '返回数据不是64加密数据');
            exit($this->error);
        }
        //使用商户是要进行解密数据
        $data = $this->decodePay($temp,$pay['pay_private_key']);
        $data = json_decode($data,true); 
        if (empty($data))
        {
            $this->PM->online_erro("{$name}", '解密出来数据不是json数据');
            exit($this->error);
        }
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
        $string = json_encode($data,320) . $key;
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
    private function decodePay($data,$pk)
    {
        $crypto = '';
        $pk = openssl_pkey_get_private($pk);
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

    /*
    ** @把对象转转成数组
     */
    private function object_to_array($array) 
    {  
        if (is_object($array)) 
        {  
            $array = (array)$array;  
        }
        if (is_array($array)) 
        {  
            foreach ($array as $key => $value) 
            {  
                $array[$key] = object_to_array($value);  
            }  
        }  
        return $array;  
    }
}