<?php

/**
 * 秒速支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/30
 * Time: 17:50
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Miaosu_model extends Publicpay_model
{
    protected $c_name = 'miaosu';
    private   $p_name = 'MIAOSU';//商品名称
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $sign_type_field = 'sign_type';//签名方式参数名

    public function __construct()
    {
        parent::__construct();
    }
    protected function returnApiData($data)
    {
        return $this->useForm($data);

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
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $param = $this->getApp();
        $data['appsId'] = $this->key;//应用id(商户密钥)
        $data['prepayId'] = $param['prepay_id'];//应用id(商户密钥)
        $data['payType']  =  $this->getPayType();
        $data['date']     =  time();
        return $data;
    }
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getApp()
    {
        $data['apps_id'] = $this->key;//应用id(商户密钥)
        $data['out_trade_no'] = $this->orderNum;//订单号 唯一
        $data['mer_id'] = $this->merId;//商户id
        $data['total_fee'] = yuan_to_fen($this->money);//金额
        $data['subject'] = $this->c_name;//标题
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data);

        //公 私钥调整位统一格式
        $PubPriKey =loadPubPriKey($this->b_key,$this->p_key);
        $pk = openssl_get_privatekey($this->p_key);
        openssl_sign($string, $sign_info, $pk);
        $data['sign'] = base64_encode($sign_info);
        $data['sign_type'] = 'RSA';
        $url =$this->url[0];
        $accesspid = $this ->getAccess($data,$url);
        return $accesspid;
    }
    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        /*手机接口对应的方式
        支付宝手机网页支付	ali_pay_wap
        个人码手机网页支付	personal_pay_wap
        PC接口对应的接口方式
        支付宝PC网页支付	ali_pay_pc
        个人码PC网页支付	personal_pay_pc
        */
        switch ($this->code)
        {
            case 1:
                return 'weixin_scan';//微信扫码
                break;
            case 2:
                return 'weixin_h5api';//微信WAP
                break;
            case 4:
                return 'ali_pay_pc';//支付宝扫码
                break;
            case 5:
                return 'ali_pay_wap';//支付宝WAP
                break;
            case 7:
                return 'direct_pay';//网关支付
                break;
            case 8:
                return 'tenpay_scan';//QQ扫码
                break;
            case 9:
                return 'jd_scan';//京东扫码
                break;
            case 12:
                return 'qq_h5api';//QQWAP
                break;
            case 13:
                return 'jd_h5api';//京东WAP
                break;
            case 17:
                return 'ylpay_scan';//银联扫码
                break;
            case 36:
                return 'personal_pay_wap';//支付宝
                break;
            case 41:
                return 'personal_pay_pc';//支付宝
                break;
            default:
                return 'ali_pay_pc';//微信扫码
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
        $pay_url = '';
        if (!empty($pay['pay_url'])) {
            $url = trim($pay['pay_url']);
        }
        if(in_array($this->code,[4,41])){
            $pay_url = $url.'/pcpay/getPayInfo';
            //支付宝wap
        }else if(in_array($this->code,[5,36])){
            $pay_url = $url.'/h5pay/getPayInfo';
            //快捷支付
        }
        //动态acesstoken请求地址
        $tokenurl = $url.'/order/saveOrder';
        $r_url = array($tokenurl,$pay_url);
        return $r_url;
    }
    //同步请求获取请求的accessToken
    protected function getAccess($pay,$url)
    {
        //请求数据
        $pay = http_build_query($pay);
        $data = post_pay_data($url,$pay);
        $data = json_decode($data,true);
        if(empty($data['info'])|| $data['status'] <> '1'){
            $msg = isset($data['message'])?$data['message']:'接口无数据返回';
            $this -> retMsg("下单失败：{$msg}");
        }
        $value = $data['info'];
        return $value;
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
        $data = post_pay_data($this->url[1],$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['info']) && $data['status'] <> '1')
        {
            $msg = "返回参数错误";
            if (isset($data['msg'])) $msg = $data['errorCode'] . $data['msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付二维码连接地址或WAP支付地址
        $pay_url = $data['info'];
        return $pay_url;
    }
}