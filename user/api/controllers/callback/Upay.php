<?php
/**
 * UPAY支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/22
 * Time: 11:48
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Upay extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'UPAY';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'merchantTradeNo'; //订单号参数
    protected $mf = 'totalAmount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'orderStatus'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取异步返回数据
     * @access protected
     * @return array data 
     */
    protected function getReturnData()
    {
        //redis记录支付错误信息标识
        $name = $this->r_name;
        //获取异步返回数据
        $data = file_get_contents('php://input');
        $this->PM->online_erro("{$name}_PUT", "数据：{$data}");
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //对数据进行urldecode解码(默认两次)
        $data = urldecode($data);
        //判断是否已经是json格式数据
        if (is_string($data) && (false !== strpos($data,'{')) 
            && (false !== strpos($data,'}')))
        {
            $data = json_decode($data,true);
        } else {
            $data = urldecode($data);
            $data = json_decode($data,true);
        }
        //返回解码以后的数组数据
        return $data;
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
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        ksort($data);
        $k = $this->ks . $key;
        $string = data_to_string($data) . $k;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
