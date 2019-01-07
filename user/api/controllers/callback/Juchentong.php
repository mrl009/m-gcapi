<?php

/**聚诚通支付回调
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/6
 * Time: 15:28
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Juchentong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'juchentong';
    //商户处理后通知第三方接口响应信息
    protected $success = "HTTP CODE 200"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_id'; //订单号参数
    protected $mf = 'real_price'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)

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
        // 构造验证签名字符串
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        $vdata = [
            'oid '=>$data['oid'],
            'uid'  =>$data['uid'],
            'pay_type'  =>$data['pay_type'],
            'pay_id'  =>$data['pay_id'],
            'order_id'  =>$data['order_id'],
            'real_price'  =>$data['real_price'],
            'repeat'  =>$data['repeat'],
            'sign_type'  =>$data['sign_type'],
        ];
        $string = data_value($vdata) . $k;
        //拼接字符串进行MD5小写加密
        $flag = sha1($string);
        if (strtolower($flag) <> strtolower($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}