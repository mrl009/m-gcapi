<?php

/**
 * e呗支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/15
 * Time: 19:50
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Ebei_model extends Publicpay_model
{
    protected $c_name = 'ebei';
    private $p_name = 'EBEI';
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $method = 'X'; //小写
    private $sk='';//签名方式参数名

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
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildWap($data);
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
        $k = $this->sk.$this->key;
        $f = $this->field;
        $s = $this->method;
        $data = get_pay_sign($data,$k,$f,$s);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchantNo']    = $this->merId;// 商户在支付平台的的平台号
        $data['orderNo']       = $this->orderNum;// 订单号
        if(in_array($this->code,[1,2])){
            if(!in_array($this->money,[50,100])){
                $this->retMsg('请支付金额 50或100');
            }
        }
        $data['orderAmount']   = yuan_to_fen($this->money);// 金额
        $data['payType']   = $this->getPayType();// 商户在支付平台支付方式
        if($this->code==7){
            $data['bankName'] = $this->bank_type;
        }
        $data['notifyUrl']     = $this->callback;// 商户通知地址
        $data['callbackUrl']      = $this->returnUrl;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 聚合付支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {//正常使用：1,2,3,5,7,17,18,25
            case 1:
                return '2';//微信扫码
                break;
            case 2:
                return '3';//微信WAP
                break;
            case 4:
                return '4';//支付宝扫码
                break;
            case 5:
                return '13';//支付宝WAP
                break;
            case 7:
                return '11';//网银B2C
                break;
            case 8:
                return '8';//QQ扫码
                break;
            case 9:
                return '14';//京东扫码
                break;
            case 12:
                return '7';//QQWAP
                break;
            case 17:
                return '9';//银联扫码
                break;
            case 18:
                return '6';//银联wap
                break;
            case 25:
                return '5';//快捷支付
                break;
            case 34:
                return '12';//微信wap
                break;
            case 38:
                return '15';//苏宁
                break;
            case 40:
                return '1';//微信公众号
                break;
            default:
                return '13';//支付宝扫码
                break;
        }
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //判断下单是否成功
        if (empty($data['payUrl']) || ('0000' <> $data['code']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['payUrl'];//wap支付地址或者二维码地址
        return $pay_url;
    }
}
