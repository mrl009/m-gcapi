<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/10/17
 * Time: 15:02
 */
include_once __DIR__.'/Publicpay.php';
class Juyou extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'Juyou';
    //商户处理后通知第三方接口响应信息
    protected $success = "opstate=0"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'ovalue'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'opstate'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vs = ['orderid','opstate','ovalue']; //参数签名字段必需参数

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
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $sign_data = array(
            'orderid' => $data['orderid'],
            'opstate' => $data['opstate'],
            'ovalue' => $data['ovalue']
        );
        $string = data_to_string($sign_data) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtolower($sign) <> strtolower($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}