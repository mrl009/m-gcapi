<?php
/**
 * 和壹付支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/22
 * Time: 11:48
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Heyifu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'HEYIFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'hmac'; //签名参数
    protected $of = 'trxMerchantOrderno'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $tf = 'result'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值
    protected $vs = ['reCode','productNo','memberGoods','trxMerchantNo']; //参数签名字段必需参数
    protected $ks = '&key='; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
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
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $sign_data = array(
            'reCode' => $data['reCode'],
            'trxMerchantNo' => $data['trxMerchantNo'],
            'trxMerchantOrderno' => $data['trxMerchantOrderno'],
            'result' => $data['result'],
            'productNo' => $data['productNo'],
            'memberGoods' => $data['memberGoods'],
            'amount' => $data['amount'],
            'key' => $key
        );
        $string = data_to_string($sign_data);
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
