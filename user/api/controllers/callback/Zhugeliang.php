<?php
/**
 * 猪哥亮支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/22
 * Time: 11:48
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Zhugeliang extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'ZGL';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'record'; //订单号参数
    protected $vd = 1; //是否使用用户商户号信息
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    

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
    protected function verifySign($data,$pay,$name)
    {
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //验证异步返回的key值和实际设置是否一致
        if (empty($data['key']) || ($data['key'] <> $pay['pay_key']))
        {
            $this->PM->online_erro($name, "异步返回key值{$data['key']}不正确");
            exit($this->error);
        }
        //获取签名字符串
        $string = sprintf('%.2f',$data['money']) . $data['record'];
        $string .= $pay['pay_id'];
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
