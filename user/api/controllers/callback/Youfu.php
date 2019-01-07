<?php
/**
 * 优付支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/15
 * Time: 15:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Youfu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'YOUFU';
    //商户处理后通知第三方接口响应信息
    protected $success = "<result>1</result>"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'sdcustomno'; //订单号参数
    protected $mf = 'ordermoney'; //订单金额参数(实际支付金额)
    protected $tf = 'state'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值
    protected $vs = ['customerid','sd51no','mark','resign']; //参数签名字段必需参数
    protected $ks = '&key='; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
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
            'customerid' => $data['customerid'],
            'sd51no' => $data['sd51no'],
            'sdcustomno' => $data['sdcustomno'],
            'mark' => $data['mark']
        );
        $string = ToUrlParams($sign_data) . $this->ks . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
        //二次加密参数验证
        unset($sign_data,$v_sign);
        $resign = $data['resign'];
        $sign_data = array(
            'sign' => $sign,
            'customerid' => $data['customerid'],
            'ordermoney' => $data['ordermoney'],
            'sd51no' => $data['sd51no'],
            'state' => $data['state']
        );
        $string = ToUrlParams($sign_data) . $this->ks . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($resign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '二次签名验证失败:' . $resign);
            exit($this->error);
        }
    }
}
