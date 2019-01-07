<?php
/**
 * 安逸付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/29
 * Time: 15:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Anyifu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'ANYIFU';
    //商户处理后通知第三方接口响应信息
    protected $error = 'false'; //错误响应
    protected $success = "true"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'md5msg'; //签名参数
    protected $of = 'outTradeNo'; //订单号参数
    protected $mf = 'transAmount'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'transStatus'; //支付状态参数字段名
    protected $tc = '04'; //支付状态成功的值

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
        if (empty($data)) 
        {
            $this->PM->online_erro("{$name}_MUST", '获取数据格式错误');
            exit($this->error);
        }
        //转化对象数组
        if (is_object($data)) $data = $this->object_to_array($data);
        if (empty($data['head']) || empty($data['body']) || empty($data['md5msg']))
        {
            $this->PM->online_erro("{$name}_MUST", '缺少必要参数：head、body、md5msg');
            exit($this->error);
        }
        //构造返回参数
        $return = $data['body'];
        $return['md5msg'] = $data['md5msg'];
        $return['head'] = $data['head'];
        $return['body'] = $data['body'];
        unset($data);
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
        //获取参与签名参数
        $head_data = $data['head'];
        $body_data = $data['body'];
        //金额类型参数 转化成字符串类型
        if (isset($body_data['payAmount']))
        {
            $body_data['payAmount'] = (string)$body_data['payAmount'];
        }
        if (isset($body_data['transAmount']))
        {
            $body_data['transAmount'] = (string)$body_data['transAmount'];
        }
        //数据排序后进行重组 转成待加密字符串
        ksort($head_data);
        ksort($body_data);
        $params['head'] = $head_data;
        $params['body'] = $body_data;
        //参数转化成json数据
        $string = json_encode($params,320);
        $string = urlencode($string) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }

    /*
    ** @把对象转转成数组
     */
    private function object_to_array($array) 
    {  
        if (is_object($array)) 
        {  
            $array = (array)$array;  
        }
        if (is_array($array)) 
        {  
            foreach ($array as $key => $value) 
            {  
                $array[$key] = object_to_array($value);  
            }  
        }  
        return $array;  
    }
}
