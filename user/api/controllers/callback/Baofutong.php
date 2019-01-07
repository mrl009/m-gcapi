<?php
/**
 * 先疯支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/30
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Baofutong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'BAOFUTONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = 'success'; //支付状态成功的值
    protected $vs = ['account_key']; //参数签名字段必需参数

    public function __construct()
    {
        parent::__construct();
    }

    public function verifySign($data,$key,$name){
        //验证系统返回account_key与平台是否一致
        if ($key <> $data['account_key'])
        {
            $msg = "返回的key值:{$data['account_key']}";
            $msg .= "与后台设置key值:{$key}不一致";
            $this->PM->online_erro($name, $msg);
            exit($this->error);
        }
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //根据返回参数和KEY值获取sign
        $v_sign = $this->getSign($key,$data);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }

    private function getSign($key_id, $data)
    {
        $data = md5(number_format($data['amount'],2) . $data['out_trade_no']);
        $key[] ="";
        $box[] ="";
        $pwd_length = strlen($key_id);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($key_id[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        $cipher = '';
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return md5($cipher);
    }
}
