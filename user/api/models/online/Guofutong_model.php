<?php
/**
 * 国富通支付接口调用
 * User: lqh
 * Date: 2018/07/01
 * Time: 11:41
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Guofutong_model extends Publicpay_model
{
    protected $c_name = 'guofutong';
    private $p_name = 'GUOFUTONG';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'signature'; //签名参数名

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
        return $this->buildForm($data);
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['merchno'] = $this->merId;//商户号
        $data['traceno'] = $this->orderNum;
        $data['amount'] = $this->money;
        $data['settleType'] = 1;//默认固定值 固定值T+0结算
        $data['notifyUrl'] = $this->callback;//通知异步回调接收地址
        //网关支付参数
        if (7 == $this->code)
        {
            $data['channel'] = 2;//固定值 直连银行
            $data['bankCode'] = $this->bank_type;
            $data['settleType'] = 2; //固定值T+1结算
        //快捷支付参数
        } elseif (25 == $this->code) {
            $data['interType'] = 1; //固定值 APP支付
            $data['cardType'] = $this->cardType; //银行卡类型
            $data['cardno'] = $this->cardNo; //银行卡号
            $data['transType'] = 1; //固定值 普通消费
        //微信wap特殊参数
        } elseif(2 == $this->code) {
            $data['ip'] = get_ip();
            $data['payType'] = $this->getPayType();
        //扫码支付参数
        }else {
            $data['payType'] = $this->getPayType();
        }
        return $data;
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
            case 2;
                return '2';//微信扫码、wap
                break;
            case 4:
            case 5:
                return '1';//支付宝扫码、wap
                break;
            case 8:
            case 12:
                return '8';//QQ钱包扫码、wap
                break;
            case 9:
            case 13:
                return '16';//京东扫码、wap
                break;
            case 10:
            case 20:
                return '4';//百度扫码、wap
                break;  
            case 17:
            case 18:
                return '32';//银联钱包、wap
                break;
            case 38:
            case 39:
                return '64';//苏宁钱包、wap
                break;
            default:
                return '2';//微信扫码
                break;
        }
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $payUrl = '';
        if (!empty($pay['pay_url']))
        {
           $payUrl = trim($pay['pay_url']); 
        }
        //网关支付地址参数
        if (7 == $this->code)
        {
            $payUrl .= 'gateway.do?m=order';
        //wap类地址
        } elseif (in_array($this->code,$this->wap_code)) {
            $payUrl .= 'wapPay';
        //扫码类接口地址
        } else {
            $payUrl .= 'passivePay';
        }
        return $payUrl;
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['barCode']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付/WAP支付返回支付地址
        return $data['barCode'];
    }
}
