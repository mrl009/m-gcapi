<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';
class Fengxie_model extends Publicpay_model
{
    protected $c_name = 'fengxie';
    private $p_name = 'FENGXIE';//商品名称
    private $ks = '|';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        if (in_array($this->code,$this->wap_code)){
            return $this->buildWap($data);
        }else {
            return $this->buildForm($data);
        }
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $data['fx_pay'] = $this->getPayType();
        $data['fx_back_url'] = $this->returnUrl;
        if (in_array($this->code,$this->wap_code)){
            $data['fx_return_url'] = 100;
        }
        return $data;
    }
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $key = $this->key;
        $data['fx_merchant_id'] =$this->merId;//商户号
        $data['fx_order_id'] = $this->orderNum;
        $data['fx_order_amount'] = $this->money;
        $data['fx_notify_url'] = $this->callback;
        $data['fx_sign'] = $this->getSign($data,$this->ks,$key);
        return $data;
    }

    private function getSign($data,$ks,$key){
        $signStr = '';
        foreach ($data as $v){
            $signStr .= $v.$ks;
        }
        $signStr = $signStr.$key;

        $sign = md5(md5($signStr));
        return $sign;
    }

    protected function getPayResult($data){
        //传递参数
        $pay_data = http_build_query($data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if ($data['fx_status']!=200)
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['fx_cashier_url'];

    }
    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
            case 2:
                return 'wxsm';//微信
                break;
            case 4:
            case 5:
                return 'zfbsm';//支付宝
                break;
            case 8:
            case 12:
                return 'qqsm';//QQ钱包
                break;
            default :
                return 'wxsm';
                break;
        }
    }
}