<?php
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/6/27
 * Time: 11:18
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Qilin extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'Qilin';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_id'; //订单号参数
    protected $vd = 1; //是否使用用户商户号信息
    protected $mf = 'real_price'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'code'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $vs = ['paysapi_id','order_id','is_type','price','real_price','mark','code']; //参数签名字段必需参数
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

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
        //获取加密验证字段
        $key =$this->ks.$pay['pay_key'];
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        unset($data['messages']);
        //验证签名字符串
        $data['api_code'] = $pay['pay_id'];
        ksort($data);
        $flag = get_pay_sign($data,$key,$this->sf,$this->mt);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}
