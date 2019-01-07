<?php
/**
 * M支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/29
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Mzhifu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'MZHIFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderId'; //订单号参数
    protected $mf = 'orderAmount'; //订单金额参数(实际支付金额)
    protected $tf = 'code'; //支付状态参数字段名
    protected $tc = '000000'; //支付状态成功的值

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
        //对获取的数据进行分割
        $data = explode('|',$data);
        if (empty($data) || empty($data[0]) || empty($data[1]))
        {
            $this->PM->online_erro("{$name}", '返回数据格式错误');
            exit($this->error);
        }
        //参数转化重组
        $temp = json_decode($data[1],true);
        if (!is_array($temp) || !is_array($temp['data']))
        {
            $this->PM->online_erro("{$name}", '返回json数据格式错误');
            exit($this->error);
        }
        $temp_data = $temp['data'];
        unset($temp['data']);
        $return = array_merge($temp,$temp_data);
        unset($temp,$temp_data);
        $return['sign'] = $data[0];
        $return['body'] = md5(base64_encode($data[1]));
        return $return;
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
        $string = $key . $data['body'];
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
