
<?php

/**
 * 千禧翼达支付回调
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/27
 * Time: 11:54
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Qianxiyida extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'QXYD';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'total_fee'; //订单金额参数(实际支付金额)
    protected $tf = 'state'; //支付状态参数字段名
    protected $tc = '00'; //支付状态成功的值
    protected $vm = 0;//部分第三方实际支付金额不一致，以实际金额为准
    protected $vd = 1; //是否使用用户商户号信息
    protected $vt = 'fen';//金额单位
    protected $ks = '&'; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }

    protected function getReturnData()
    {
        header("Content-Type:text/html;charset=UTF-8");
        $name = $this->r_name;
        $data = $_REQUEST;
        if (empty($data)) $data = $data = file_get_contents('php://input');
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //进行base64解码
        $data = base64_decode($data);
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}", '返回数据不是64加密数据');
            exit($this->error);
        }
        // json 转化
        $data = json_decode($data,true);
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}", '返回数据不是json格式数据');
            exit($this->error);
        }
        return $data;

    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$pay,$name)
    {
        //验证并获取获取公钥
        $sk = openssl_get_publickey($pay['pay_server_key']);//平台公钥
        if (empty($sk))
        {
            $this->PM->online_erro($name,'解析第三方服务公钥失败');
            exit($this->error);
        }
        //获取签名
        $sign = base64_decode($data[$this->sf]);
        unset($data[$this->sf]);
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data);
        //验证签名数据
        $flag = openssl_verify($string,$sign,$sk,OPENSSL_ALGO_MD5);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$data[$this->sf]}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}