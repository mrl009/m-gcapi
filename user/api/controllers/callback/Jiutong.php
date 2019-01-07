<?php
/**
 * 久通支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/16
 * Time: 14:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Jiutong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'JIUTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "0"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNum'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'payResult'; //支付状态参数字段名
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
        //获取异步返回数据
        $data = $_REQUEST;
        //redis记录支付错误信息标识
        $name = $this->r_name;
        if (empty($data) || empty($data['data']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //转换并解密数据
        $data = base64_decode($data['data']);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}", '返回数据不是64加密数据');
            exit($this->error);
        }
        //获取当前支付模型文件的私钥和公钥
        $this->get_key();
        $data = $this->decodePay($data,$this->p_key);
        $data = json_decode($data,true);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}", '解密出来数据不是json数据');
            exit($this->error);
        } 
        return $data;
    }

    /*
     * 秘钥加密方式
     */
    private function decodePay($data,$pk)
    {
        $crypto = '';
        $pk = openssl_pkey_get_private($pk);
        //分段解密   
        foreach (str_split($data, 128) as $chunk) 
        {
            openssl_private_decrypt($chunk, $decryptData, $pk);
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    // 获取公钥和私钥
    private function get_key()
    {
        $name = $this->r_name;
        //切换数据库
        $this->PM->select_db('public');
        //设置查询条件
        $where = ['model_name'=>'Jiutong'];
        //获取公库支付配置信息
        $pid = $this->PM->get_one('id', 'bank_online', $where);
        if (empty($pid) || empty($pid['id']))
        {
            $this->PM->online_erro($name, '该支付方式不存在');
            exit($this->error);
        }
        //切换私库
        $this->PM->select_db('private');
        //获取该支付方式的key值
        $id = intval($pid['id']);
        $where = ['bank_o_id'=> $id];
        $key = $this->PM->get_one('*','bank_online_pay',$where);
        if (empty($key))
        {
            $this->PM->online_erro($name, '该支付方式未设置');
            exit($this->error);
        }
        //设置使用的支付key值
        $this->s_key = $key['pay_server_key'];
        $this->b_key = $key['pay_public_key'];
        $this->p_key = $key['pay_private_key'];
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
        ksort($data);
        $string = json_encode($data,320) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
