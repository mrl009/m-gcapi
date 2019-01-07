<?php

/**
 *恆润回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/5
 * Time: 18:01
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay.php';
class Hengrun extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'Hengrun';
    //商户处理后通知第三方接口响应信息、
    protected $success = 'SUCCESS'; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'outTradeNo'; //订单号参数
    protected $mf = 'totalAmount'; //订单金额参数(实际支付金额)
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'payCode'; //支付状态参数字段名
    protected $tc = '0000'; //支付状态成功的值
    protected $key_string = '|'; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }
    protected function getReturnData()
    {
        header("Content-Type:text/html;charset=UTF-8");
        $data = [];
        $name = $this ->r_name;
        //加载支付Online_model类 记录支付相关错误信息
        $m = &get_instance();
        $m->load->model('pay/Online_model','PM');
        //redis记录 异步接口返回的数据信息
        //文件流形式
        if (!empty(file_get_contents("php://input")))
        {
            $put = file_get_contents("php://input");
            //如果是json形式数据 转化成数组
            if (is_string($put) && (false !== strpos($put,'{'))
                && (false !== strpos($put,'}')))
            {
                $m->PM->online_erro("{$name}_PUT_json", '数据:' . $put);
                $data = string_decoding($put);
            }
            //如果是以key=value形式数据 转化成数组
            if (is_string($put) && (false !== strpos($put,'&'))
                && (false !== strpos($put,'=')))
            {
                $m->PM->online_erro("{$name}_PUT_put", '数据:' . $put);
                $data = urldecode($put);//解码url
                parse_str($data,$data);//转换成数组
            }
            //如果是xml格式数据 转化成数组
            if (is_string($put) && (false !== strpos($put,'xml')))
            {
                $m->PM->online_erro("{$name}_PUT_xml", '数据:' . $put);
                $data = FromXml($put);
            }
        }
        //GET,POST方式
        if (!empty($_REQUEST) && empty($data))
        {
            //如果是数组 转化成json记录数据库
            if(is_array($_REQUEST))
            {
                //数组转化成json 录入数据
                $temp = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
                $m->PM->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
                unset($temp);
                $data = $_REQUEST;
            }
            //如果json格式 记录数据 同时转化成数组
            if (is_string($_REQUEST) && (false !== strpos($_REQUEST,'{'))
                && (false !== strpos($_REQUEST,'}')))
            {
                $m->PM->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST);
                //json格式数据先进行转码
                $data = string_decoding($_REQUEST);
            }
        }
        $param = json_decode($data['NoticeParams'],true);
        return $param;
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
        //获取签名字符串 并验证签名
        ksort($sdata);
        $k = $this->key_string . $key;
        $string = data_value($data,$this->key_string).$k;
        $v_sign = strtoupper(md5($string));
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}