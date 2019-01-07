<?php
/**
 * 大白支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/22
 * Time: 11:48
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Dabai extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'DABAI';
    //商户处理后通知第三方接口响应信息
    protected $success = 'success'; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'moeny'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'ontype'; //支付状态参数字段名
    protected $tc = '102'; //支付状态成功的值
    protected $vs = ['amount','bankcode','scene','memberid','rand']; //参数签名字段必需参数

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
        //获取异步返回数据 并记录数据
        $data = $_REQUEST;
        $temp = json_encode($data,JSON_UNESCAPED_UNICODE);
        $this->PM->online_erro("{$name}_REQUEST", "数据：{$temp}");
        //判断是否获取到需要数据
        if (empty($data) || empty($data['body']))
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        unset($temp);
        //对数据进行64位和json解密运算
        $data = base64_decode($data['body']);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}_MUST", '数据格式不是base64');
            exit($this->error);
        }
        $data = json_decode($data,true);
        if (empty($data))
        {
            $this->PM->online_erro("{$name}_MUST", '数据格式不是json');
            exit($this->error);
        }
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
        $sign_data = array(
            'amount' => $data['amount'],
            'moeny' => $data['moeny'],
            'bankcode' => $data['bankcode'],
            'scene' => $data['scene'],
            'memberid' => $data['memberid'],
            'orderid' => $data['orderid'],
            'rand' => $data['rand'],
            'ontype' => $data['ontype'],
            'key' => $key 
        ); 
        $string = data_to_string($sign_data);
        $v_sign = md5(md5($string));
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}