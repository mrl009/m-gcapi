
<?php

/**
 * 德兴支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/28
 * Time: 14:15
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Dexin extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'ZLXT';
    //商户处理后通知第三方接口响应信息
    protected $success = "opstate=0"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'ovalue'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'opstate'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vs = ['orderid','opstate','ovalue']; //参数签名字段必需参数

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
        $k = $key;
        $sign = $data[$this->sf];
        //删除非签名的字段
        unset($data[$this->sf]);
        $cdata = [
            'orderid' => $data['orderid'],
            'opstate' => $data['opstate'],
            'ovalue'  => $data['ovalue'],
        ];
        //构造签名字符串
        $string = ToUrlParams($cdata).$k;
        $v_sign = md5($string);
        //验证签名是否一致
        if (strtolower($sign) <> strtolower($v_sign) )
        {
            $this->PM->online_erro($name, $sign.'签名验证失败:' . $v_sign);
            exit($this->error);
        }
    }
}