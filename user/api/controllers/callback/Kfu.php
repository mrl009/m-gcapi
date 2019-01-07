<?php
/**
 * K付宝支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/02
 * Time: 12:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Kfu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'KFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
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

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    protected function verifySign($data,$key,$name)
    {
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

    /**
     * 根据参数获取加密结果sign
     * @param string code
     * @return string sign
     */
    private function getSign($sk, $data)
    {
        $st = sprintf("%.2f", $data['amount']);
        $sd = md5($st . $data['out_trade_no']);
        $ky = [];
        $by = [];
        $string = '';
        $pl = strlen($sk);
        $sl = strlen($sd);
        for ($i = 0; $i < 256; $i++)
        {
            $ky[$i] = ord($sk[$i % $pl]);
            $by[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $by[$i] + $ky[$i]) % 256;
            $tmp = $by[$i];
            $by[$i] = $by[$j];
            $by[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $sl; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $by[$a]) % 256;
            $tmp = $by[$a];
            $by[$a] = $by[$j];
            $by[$j] = $tmp;
            $k = $by[(($by[$a] + $by[$j]) % 256)];
            $string .= chr(ord($sd[$i]) ^ $k);
        }
        return md5($string);
    } 
}