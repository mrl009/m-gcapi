
<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/31
 * Time: 22:07
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Mashangfu extends Publicpay
{
    //redis 错误记录
    protected $r_name = 'MASHANGFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_id'; //订单号参数
    protected $mf = 'pay_price'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '02'; //支付状态成功的值
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)

    public function  __construct()
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
        unset($data[$this->sf]);
        $sdata = array(
            'appid'    => $data['appid'],
            'aoid'     => $data['aoid'],
            'order_id' => $data['order_id'],
            'extend'   => $data['extend'],
            'price'    => $data['price'],
            'pay_price'=> $data['pay_price']
        );
        $string = data_value($sdata).$key;
        $v_sign = md5($string);
        if (strtolower($v_sign)<>strtolower($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}