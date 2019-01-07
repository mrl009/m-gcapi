
<?php

/**
 * 微支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/16
 * Time: 16:43
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Weizhifu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'WEIZHIFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderno'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = '1';//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'result_code'; //支付状态参数字段名
    protected $tc = '200'; //支付状态成功的值
    protected $mt = 'D'; //返回签名是否大写 D/X

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
        $sign  = $data[$this->sf];
        $vsign = array(
            'notifyid'=>$data['notifyid'],
            'orderno'=>$data['orderno'],
            'amount'=>$data['amount'],
            'body'=>$data['body'],
        );
        $flag = strtoupper(md5(data_value($vsign).$key));
        if ($flag <> strtoupper($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}