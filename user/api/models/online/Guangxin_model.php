<?php

/**
 * 广信支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/30
 * Time: 15:15
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Guangxin_model extends Publicpay_model
{
    protected $c_name = 'guangxin';
    protected $p_name = 'GX';//商品名称
    private   $k  = '&key=';

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
    {   //扫码支付
        if (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
         //网银支付快捷,wap支付
        }else {
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
        $param['accessToken'] = $this->get_Token();
        //构造签名参数
        $param['param'] = $data;
        return $param;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['outTradeNo'] = $this->orderNum;
        $data['money'] = yuan_to_fen($this->money);
        $data['type'] = 'T0';
        $data['body'] = $this->p_name;
        $data['detail'] = $this->c_name;
        if($this->code == 7) $data['bankName'] = $this->bank_type;
        $data['notifyUrl'] = $this->callback;
        if($this->code==2)$data['merchantIp'] = get_ip();
        $data['productId'] = $this ->user['id'];
        if($this->code !=25)$data['successUrl'] = $this -> returnUrl;
        if($this->code ==25){
            $data['cardName'] = '123';
            $data['cardNo']   = '123';
            $data['bank']     = '123';
            $data['idType']   = '123';
            $data['cardPhone']= '123';
            $data['cardIdNumber']= '123';
        }
        return $data;
    }
    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $url = trim($pay['pay_url']);
            //微信扫码
            if(in_array($this->code,[1])){
                $pay_url = $url.'/order/wechatScan';
                //微信wap
            }else if(in_array($this->code,[2])){
                $pay_url = $url.'/order/wechatWapPay';
                //支付宝扫码
            }else if(in_array($this->code,[4])){
                $pay_url = $url.'/order/alipayScan';
                //支付宝wap
            }else if(in_array($this->code,[5])){
                $pay_url = $url.'/order/alipayWapPay';
                //快捷支付
            }else if(in_array($this->code,[25])){
                $pay_url = $url.'/quickPay/quick';
                //qq钱包
            }else if(in_array($this->code,[8])){
                $pay_url = $url.'/order/qqScan';
                //京东钱包
            }elseif(in_array($this->code,[9])){
                $pay_url = $url.'/order/jdPay';
                //银联扫码
            }else if(in_array($this->code,[17])){
                $pay_url = $url.'/order/unionpayScan';
            }else if($this->code ==7){
                $pay_url = $url.'/order/bankPay';
            }
        }
        $r_url = array($url,$pay_url);
        return $r_url;
    }
    //动态获取acesstoken
    protected function get_Token(){
        $param['merchantNo'] = $this ->merId;
        $param['nonce']      = create_guid();
        $param['timestamp']  = $_SERVER['REQUEST_TIME'];
        if(!empty($this->s_num)){
            $param['token']      = $this ->s_num;
        }
        $ks = $this->k.$this->key;
        $param = get_pay_sign($param,$ks,'sign','D');
        $param['key'] = $this ->merId;
        $url = $this->url[0].'/getAccessToken/merchant';
        $accessToken = $this ->getAccess($param,$url);
        return $accessToken;

    }

    protected function getAccess($pay,$url)
    {
        //转换成json数据
        $pay = json_encode($pay,JSON_FORCE_OBJECT);
        $head = array(
            'Content-type:application/json',
            'Content-Length: ' . strlen($pay),
        );
        $data = post_pay_data($url,$pay,$head);
        $data = json_decode($data,true);
        if(empty($data['value'])|| $data['success'] <> '1'){
            $msg = isset($data['message'])?$data['message']:'接口无数据返回';
            $this -> retMsg("下单失败：{$msg}");
        }
        $value = $data['value'];
        return $value['accessToken'];
    }
    //支付下单获取返回的数据
    protected  function getPayResult($paydata){
        $url  = $this->url[1];
        $pay  = json_encode($paydata,JSON_FORCE_OBJECT);
        $pay = str_replace("\\/", "/",$pay);
        $head = array(
            'Content-type:application/json',
            'Content-Length: ' . strlen($pay),
        );
        $data = post_pay_data($url,$pay,$head);
        $data = json_decode($data,true);
        if(empty($data['value'])|| empty($data['success'])){
            $msg = isset($data['message'])?$data['message']:'接口无数据返回';
            $this -> retMsg("下单失败：{$msg}");
        }
        $payurl = $data['value'];
        return $payurl;
    }
}