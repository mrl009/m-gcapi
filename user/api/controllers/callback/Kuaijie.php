
<?php

/**
 * 快捷支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/16
 * Time: 14:52
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Kuaijie extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'KUAIJIE';
    protected $success = "ok"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $ks = '&appkey='; //参与签名字符串连接符
    protected $of = 'tradeNo'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vd = 1; //是否使用用户商户号信息
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '100'; //支付状态成功的值
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 获取异步返回数据
     */
    protected function getReturnData(){
        header("Content-Type:text/html;charset=UTF-8");
        $data = [];
        //加载支付Online_model类 记录支付相关错误信息
        $m = &get_instance();
        $m->load->model('pay/Online_model','PM');
        //redis记录 异步接口返回的数据信息
        $name = $this->r_name;
        //GET,POST方式
        if (!empty($_REQUEST) && empty($data))
        {
            //如果是数组 转化成json记录数据库
            if(is_array($_REQUEST['paramData']))
            {
                //数组转化成json 录入数据
                $temp = json_encode($_REQUEST['paramData'],JSON_UNESCAPED_UNICODE);
                $m->PM->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
                unset($temp);
                $data = $_REQUEST['paramData'];
            }
            //如果json格式 记录数据 同时转化成数组
            if (is_string($_REQUEST['paramData']) && (false !== strpos($_REQUEST['paramData'],'{'))
                && (false !== strpos($_REQUEST['paramData'],'}')))
            {
                $m->PM->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST['paramData']);
                //json格式数据先进行转码
                $data = string_decoding($_REQUEST['paramData']);
            }
        }
        //判断是否获取到数据
        if (empty($data))
        {
            $msg = "三种方式都没获取到任何数据";
            $m->PM->online_erro("{$name}_MUST", $msg);
            exit('ERROR');
        }
        //判断是否含有必要参数（签名参数/订单号参数/订单金额参数）
        if (empty($data[$this->sf]) || empty($data[$this->of]) || empty($data[$this->mf]))
        {
            $msg = "缺少必要参数：{$this->sf}、{$this->of}、{$this->mf}";
            $m->PM->online_erro("{$name}_MUST", $msg);
            exit('ERROR');
        }
        return $data;
    }
    /**
     * 验证签名 (默认验证签名方法,部分第三方不一样)
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$pay,$name)
    {
        // 构造验证签名字符串
        $k = $this->ks . $pay['pay_key'];
        $sign = $data[$this->sf];
        $data['merchantNo'] = $pay['pay_id'];
        $flag = verify_pay_sign($data,$k,$this->sf);
        if (empty($flag))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}