<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Hexing extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'Hexing';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $ks = '&appkey='; //参与签名字符串连接符
    protected $mt = 'X'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model','PM');
    }

    protected function getReturnData()
    {
        //redis记录支付错误信息标识
        $name = $this->r_name;
        //获取异步接口返回的数据（数组形式）
        $sf = $this->sf; //签名字段
        $of = $this->of; //订单号字段
        $mf = $this->mf; //订单金额字段(实际支付金额)
        $data = $this->get_return_data($name,$sf,$of,$mf);

        return $data;
    }
    
    protected function get_return_data($name,$sf,$of,$mf){
        header("Content-Type:text/html;charset=UTF-8");
        $data = [];
        //redis记录 异步接口返回的数据信息
        //文件流形式
        if (!empty(file_get_contents("php://input")))
        {
            $put = file_get_contents("php://input");
            //如果是json形式数据 转化成数组
            if (is_string($put) && (false !== strpos($put,'{'))
                && (false !== strpos($put,'}')))
            {
                $this->PM->online_erro("{$name}_PUT_json", '数据:' . $put);
                $data = string_decoding($put);
                //var_dump($data);
            }
            //如果是以key=value形式数据 转化成数组
            if (is_string($put) && (false !== strpos($put,'&'))
                && (false !== strpos($put,'=')))
            {
                $this->PM->online_erro("{$name}_PUT_put", '数据:' . $put);
                $data = urldecode($put);//解码url
                parse_str($data,$data);//转换成数组
            }
            //如果是xml格式数据 转化成数组
            if (is_string($put) && (false !== strpos($put,'xml')))
            {
                $this->PM->online_erro("{$name}_PUT_xml", '数据:' . $put);
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
                $this->PM->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
                unset($temp);
                $data = $_REQUEST;
            }
            //如果json格式 记录数据 同时转化成数组
            if (is_string($_REQUEST) && (false !== strpos($_REQUEST,'{'))
                && (false !== strpos($_REQUEST,'}')))
            {
                $this->PM->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST);
                //json格式数据先进行转码
                $data = string_decoding($_REQUEST);
            }
        }

        //判断是否获取到数据
        if (empty($data))
        {
            $msg = "三种方式都没获取到任何数据";
            $this->PM->online_erro("{$name}_MUST", $msg);
            exit('ERROR');
        }

        $data_base64 = '';
        foreach ($data as $v) {
            $data_base64 = $v;
            }

        $json_data = base64_decode($data_base64);
        $data = json_decode($json_data,JSON_UNESCAPED_UNICODE);

        //判断是否含有必要参数（签名参数/订单号参数/订单金额参数）
        if (empty($data[$sf]) || empty($data[$of]) || empty($data[$mf]))
        {
            $msg = "缺少必要参数：{$sf}、{$of}、{$mf}";
            $this->PM->online_erro("{$name}_MUST", $msg);
            exit('ERROR');
        }
        return $data;
    }

}