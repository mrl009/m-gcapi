<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Kuaipay_model extends Publicpay_model
{
    protected $c_name = 'Kuaipay';
    private $p_name = 'KUAIPAY';//商品名称A
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }
    protected function getSignTokenData(){
        $data['merchantNo'] = $this->merId;
        $data['nonce'] = md5(uniqid(microtime(true),true));
        $data['timestamp'] = time();
        $data['key'] = $this->key;
        $signStr = data_to_string($data);
        $data['sign'] = $sign = strtoupper(md5($signStr));
        return $data;
    }
    protected function getToken(){
        $url = 'http://api.qwebank.top/open/v1/getAccessToken/merchant';
        $data = $this->getSignTokenData();
        $tokenPost = json_encode($data);
        $tokenData = post_pay_data($url,$tokenPost,'json');
        if (empty($tokenData)){
            $this->retMsg('获取支付token失败');
        }
        $tokenDataArr = json_decode($tokenData,true);
        if (!$tokenDataArr['success']){
            $msg = $tokenDataArr['message'] ? $tokenDataArr['message'] : '获取支付token失败';
            $this->retMsg($msg);
        }
        $accessToken = $tokenDataArr['value']['accessToken'];
        return $accessToken;

    }
    protected function getPayData()
    {
        $data = $this->getBaseData();
        return $data;
    }

    private function getBaseData()
    {
        $data['outTradeNo'] = $this->orderNum;
        $data['money'] = yuan_to_fen($this->money);
        $data['type'] = 'T0';
        $data['body'] = $this->p_name;
        $data['detail'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['productId'] = uniqid();
        $data['successUrl'] = $this->returnUrl;
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
        {
            case 1:
                return 'wechatScan';//微信扫码
                break;
            case 2:
                return 'wechatWapPay';//微信Wap/h5
                break;
            case 4:
                return 'alipayScan';//支付宝扫码
                break;
            case 5:
                return 'alipayWapPay';//支付宝WAP
                break;
            case 7:
                return 'bankPay';//网银支付
                break;
            case 9:
                return 'jdPay';//京东扫码
                break;
            case 12:
                return 'qqScan';//QQ
                break;
            case 17:
                return 'unionpayScan';//银联扫码
                break;
            default:
                return 'alipayScan';//支付宝扫码
                break;
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($data){
        $url = $this->url.'/'.$this->getPayType();
        $pay_data['accessToken'] = $this->getToken();
        $pay_data['param'] = $data;
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['value']) || !$data['success']){
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['value'];
        return $pay_url;
    }
}
