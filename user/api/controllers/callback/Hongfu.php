<?php

/**
 * 鸿付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/9
 * Time: 19:48
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Hongfu extends  Publicpay
{
    //redis错误标识名称
    protected $r_name = 'HONGFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order_no'; //订单号参数
    protected $mf = 'order_amount'; //订单金额参数(实际支付金额)
    protected $tf = 'trade_status'; //支付状态参数字段名
    protected $tc = 'SUCCESS'; //支付状态成功的值
    protected $vm = 0;//部分第三方实际支付金额不一致，以实际金额为准
    protected $vd = 1; //是否使用用户商户号信息
    protected $ks = '&'; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X
    protected $pk = 'pay_server_key'; //第三方key对应的数据库中的字段

    public function __construct()
    {
        parent::__construct();
    }

    protected function getReturnData()
    {

        //获取返回数据
        if (!empty(file_get_contents("php://input"))){
            $data = file_get_contents('php://input');
            $this->PM->online_erro("{$this->r_name}_PUT", "数据：{$data}");
            if (empty($data))
            {
                $this->PM->online_erro("{$this->r_name}_MUST", '未获取到需要的数据');
                exit($this->error);
            }
            //转化json格式数据
            $data = json_decode($data,true);
        }
        if(!empty($_REQUEST)){
            if(is_array($_REQUEST))
            {
                //数组转化成json 录入数据
                $temp = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
                $this->PM->online_erro("{$this->r_name}_REQUEST_array", '数据:' . $temp);
                unset($temp);
                $data = $_REQUEST;
            }
            //json格式数据 转为数据
            if (is_string($_REQUEST))
            {
                $this->PM->online_erro("{$this->r_name}_REQUEST_json", '数据:' . $_REQUEST);
                $data = json_decode($_REQUEST,true);
            }
        }
        if(empty($data)) $this->PM->online_erro("{$this->r_name}_REQUEST 数据：未获取到数据");
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
        //获取签名
        $sign = base64_decode($data[$this->sf]);
        //删除不参与签名参数
        unset($data[$this->sf]);
        unset($data['sign_type']);
        //数组去重排序并获取加密字符串
        $data = array_filter($data);
        ksort($data);
        $str = ToUrlParams($data);
        $sk = openssl_get_publickey($pay['pay_server_key']);//平台公钥
        //验证签名
        $flag = openssl_verify($str,$sign,$sk,OPENSSL_ALGO_MD5);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }

}