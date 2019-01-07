<?php

/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/25
 * Time: 11:34
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Esinpay extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'ESINPAY';
    //商户处理后通知第三方接口响应信息
    protected $success = "ErrCode=0"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'P_PostKey'; //签名参数
    protected $of = 'P_OrderId'; //订单号参数
    protected $mf = 'P_PayMoney'; //订单金额参数(实际支付金额)
    protected $tf = 'P_ErrCode'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 验证签名 (默认验证签名方法,部分第三方不一样)
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        // 构造验证签名字符串
        $flag =$data['P_PostKey'];
       $data =array(
            'P_UserId'=>$data['P_UserId'],
            'P_OrderId'=>$data['P_OrderId'],
            'P_CardId'=>$data['P_CardId'],
            'P_CardPass'=>$data['P_CardPass'],
            'P_FaceValue'=>$data['P_FaceValue'],
            'P_ChannelId'=>$data['P_ChannelId'],
            'P_PayMoney'=>$data['P_PayMoney'],
            'P_ErrCode'=>$data['P_ErrCode']
        );
        $string = $this-> ToParams($data);
        $string = $string.$key;
        $sign = strtolower(md5($string));
        if ($sign<> $flag)
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }

    /**
     * 将数组的键与值用&符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
    protected  function ToParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if (!is_array($v) && ('P_PostKey' <> $k)
                && ("" <> $v) && (null <> $v)
                && ("null" <> $v))
            {
                $buff .= $v . "|";
            }
        }
        return $buff;
    }

}