<?php
/**
 * 通宇支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/05
 * Time: 16:26
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Tongyu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'TONGYU';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'signature'; //签名参数
    protected $of = 'out_order_no'; //订单号参数
    protected $mf = 'payment_fee'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'ret_code'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值

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
        //获取返回数据
        $data = file_get_contents('php://input');
        $this->PM->online_erro("{$name}_PUT", "数据：{$data}");
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }
        //转化json格式数据
        $data = json_decode($data,true);
        if (empty($data) || empty($data['biz_content'])) 
        {
            $this->PM->online_erro("{$name}_MUST", '获取数据格式错误');
            exit($this->error);
        }
        //获取订单信息数据
        $data = array_merge($data,$data['biz_content']);
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
        $string = json_encode($data['biz_content']);
        $string = str_replace('\/','/',$string);
        $string = str_replace('\/\/','//',$string);
        $string = "biz_content={$string}&key={$key}";
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}