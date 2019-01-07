<?php
/**
 * 易迅捷支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 09:08
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Yixunjie extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'YIXUNJIE';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderNo'; //订单号参数
    protected $mf = 'payAmt'; //订单金额参数(实际支付金额)
    protected $tf = 'retCode'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符
    protected $vs = ['transNo','userId'];

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
        //获取加密验证字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $k = $this->ks . $key;
        $sign_data = array(
            'orderNo' => $data['orderNo'],
            'payAmt' => $data['payAmt'],
            'retCode' => $data['retCode'],
            'transNo' => $data['transNo'],
            'userId' => $data['userId']
        );
        $string = data_to_string($sign_data) . $k;
        $v_sign = md5($string);
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}