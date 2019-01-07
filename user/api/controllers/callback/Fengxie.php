<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Fengxie extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'FENGXIE';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'fx_sign'; //签名参数
    protected $of = 'fx_order_id'; //订单号参数
    protected $mf = 'fx_order_amount'; //订单金额参数(实际支付金额)
    protected $tf = 'fx_status_code'; //支付状态参数字段名
    protected $tc = '200'; //支付状态成功的值
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $ks = '|'; //参与签名字符串连接符

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
        //获取签名字符串
        $sign_data = array(
            'fx_merchant_id' => $data['fx_merchant_id'],
            'fx_order_id' => $data['fx_order_id'],
            'fx_transaction_id' => $data['fx_transaction_id'],
            'fx_order_amount' => $data['fx_order_amount'],
            'fx_original_amount' => $data['fx_original_amount'],
             'key'=> $key,
        );
        $signStr = '';
        foreach ($sign_data as $v){
            $signStr .= $v.$this->ks;
        }
        $signStr = $signStr.$data['fx_status_code'];

        $vSign = md5(md5($signStr));
        //验证签名是否正确
        if ($sign <> $vSign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }

}