<?php
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/13
 * Time: 11:07
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*引入公共模板文件*/
include_once __DIR__.'/Publicpay.php';

class Shanrubao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'SHANRUBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'key'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'realprice'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)

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
        $sign = $data[$this->sf];
        $sdata = array(
            'orderid'=> $data['orderid'],
            'orderuid'=> $data['orderuid'],
            'paysapi_id'=> $data['paysapi_id'],
            'price'=> $data['price'],
            'realprice'=> $data['realprice'],
            'token'=> $key
        );
        ksort($sdata);
        $signStr = ToUrlParams($sdata);
        $vSign = md5($signStr);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($vSign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}