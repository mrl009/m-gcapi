<?php
/**
 * 金汇支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 12:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Jinhui extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'JINHUI';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $tf = 'returncode'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $vs = ['userid','sign2']; //参数签名字段必需参数
    protected $ks = '&keyvalue='; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名 
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        //构造签名字符串
        $k = $this->ks . $key;
        //获取参与签名参数sign2 并验证验证sign2是否一致
        $sign_data = $this->getSignData($data);
        $sign2_string = data_to_string($sign_data) . $k;
        $sign2 = md5(strtolower($sign2_string));
        //获取参与签名参数sign 并验证验证sign是否一致 比sign2少一个money参数
        unset($sign_data['money']);
        $sign_string = data_to_string($sign_data) . $k;
        $sign = md5(strtolower($sign_string));
        //验证签名参数
        if (($sign <> $data['sign']) || ($sign2 <> $data['sign2']))
        {
            $this->PM->online_erro($name, 'sign1或sign2签名验证失败');
            exit($this->error);
        }
    }

    /**
     * 获取异步返回参数中参与签名的参数 
     * @param array data 异步返回参数
     * @return array data 参与签名参数
     */
    private function getSignData($data)
    {
        $sign['money'] = $data['money'];
        $sign['returncode'] = $data['returncode'];
        $sign['userid'] = $data['userid'];
        $sign['orderid'] = $data['orderid'];
        return $sign;
    }
}
