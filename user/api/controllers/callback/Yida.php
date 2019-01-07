<?php

/**益达回调文件
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/3
 * Time: 18:34
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Yida extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'YIDA';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    protected $private_key = ""; //商户私钥
    protected $server_key = ""; //平台公钥
    //异步返回必需验证参数
    protected $sf = 'signMsg'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'orderAmount'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额
    protected $vt = 'fen';//金额单位
    protected $tf = 'payResult'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符

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
        //获取返回数据
        $data = $_REQUEST;
        $temp = json_encode($data);
        $this->PM->online_erro("{$name}_PUT", "数据：{$temp}");
        if (empty($data))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        return $data;
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
        $signature = $data['signMsg'];
        unset($data['signMsg']);
        unset($data['signType']);
        ksort($data);
        $this->get_key();
        $string = ToUrlParams($data);
        $result = $this->checkSign($string,$signature ,$this->server_key);
        if (!$result)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
    private  function checkSign($data, $signature, $publicKey)
    {
        $key = loadPubPriKey($publicKey,$this->private_key);
        $pubKey = $key['publicKey'];
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($signature), $res,OPENSSL_ALGO_SHA1);
        openssl_free_key($res);
        return $result;
    }
    // 获取公钥和私钥
    private function get_key()
    {
        $name = $this->r_name;
        //切换数据库
        $this->PM->select_db('public');
        //设置查询条件
        $where = ['model_name' => 'Yida'];
        //获取公库支付配置信息
        $pid = $this->PM->get_one('id', 'bank_online', $where);
        if (empty($pid) || empty($pid['id'])) {
            $this->PM->online_erro($name, '该支付方式不存在');
            exit($this->error);
        }
        //切换私库
        $this->PM->select_db('private');
        //获取该支付方式的key值
        $id = intval($pid['id']);
        $where = ['bank_o_id' => $id];
        $key = $this->PM->get_one('*', 'bank_online_pay', $where);
        if (empty($key)) {
            $this->PM->online_erro($name, '该支付方式未设置');
            exit($this->error);
        }
        //设置使用的支付key值
        $this->server_key = $key['pay_server_key'];
        $this->private_key = $key['pay_private_key'];
    }
}