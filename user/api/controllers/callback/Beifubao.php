<?php
/**
 * 北付宝支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/29
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Beifubao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'BEIFUBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUC"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'memberOrderNumber'; //订单号参数
    protected $mf = 'tradeAmount'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'orderStatus'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值

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
        $data = file_get_contents('php://input');
        $this->PM->online_erro("{$name}_PUT", "数据：{$data}");
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        $data = json_decode($data,true);
        if (empty($data) || empty($data['context']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到指定格式的数据');
            exit($this->error);
        }
        //获取当前支付模型文件的私钥和公钥
        $this->get_key();
        //解密密文数据
        $data = base64_decode($data['context']);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}_MUST", '返回数据不是64加密数据');
            exit($this->error);
        }
        $data = $this->decodePay($data);
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '密文解析错误');
            exit($this->error);
        }
        //解密的json数据转化成数组
        $data = json_decode($data,true);
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '密文格式解析错误');
            exit($this->error);
        }
        //确认需要密文数据是否存在
        if (empty($data['businessContext']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到businessContext信息');
            exit($this->error);
        }
        if (empty($data['businessHead']['sign']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取签名参数sign数据信息');
            exit($this->error);
        }
        //含有订单信息的数据重新赋值
        $return = $data['businessContext'];
        $return['sign'] = $data['businessHead']['sign'];
        unset($data);
        return $return;
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
        //获取签名数据
        $sign = base64_decode($data['sign']);
        if (empty($sign)) 
        {
            $this->PM->online_erro("{$name}", 'sign参数解析错误');
            exit($this->error);
        }
        unset($data['sign']);
        //对其他数据进行排序
        ksort($data);
        $string = json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $sk = openssl_get_publickey($this->s_key);
        if (empty($sk)) 
        {
            $this->PM->online_erro("{$name}", '第三方服务端公钥解析错误');
            exit($this->error);
        }
        $verify = openssl_verify($string, $sign, $sk, OPENSSL_ALGO_MD5);
        //验证签名是否正确
        if (!$verify)
        {
            $this->PM->online_erro($name, '签名验证失败');
            exit($this->error);
        }
    }


    // 获取公钥和私钥
    private function get_key()
    {
        $name = $this->r_name;
        //切换数据库
        $this->PM->select_db('public');
        //设置查询条件
        $where = ['model_name'=>'Beifubao'];
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

    /*
     * 秘钥解密方式
     */
    private function decodePay($data)
    {
        $crypto = '';
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
