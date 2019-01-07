
<?php

/** 新金通支付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/31
 * Time: 18:27
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Xjintong extends Publicpay
{
    //redis 错误记录
    protected $r_name = 'XJINTONG';
    protected $success = '{"code":200,"msg":"已收到回调"}'; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'out_trade_no'; //订单号参数
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $ks = ''; //参与签名字符串连接符

      public function  __construct()
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
        $k = $this->ks . $key;
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        unset($data['sign_type']);
        $string = $this->String($data,$key);
        $v_sign = md5($string);
        if (strtolower($v_sign)<>strtolower($sign))
        {
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }

    private function String($data,$k){
        $arr = [];
        $string = '';
        foreach($data as $key => $val)
        {
            if (!is_array($val))
            {
                $arr[] .= $val;
            }
        }
        //将参数值排序
        sort($arr,SORT_STRING);
        $string =  implode($arr).$k;
        return $string;
    }
}