<?php

/**
 * E支付文件
     */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Efu_model extends Publicpay_model
{
     protected  $c_name = 'Efu';
     protected  $p_name = 'Efu';
     //构造签名参数
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'signMsg'; //签名参数名
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 充值跳转方式
     * @param $data
     */
    protected  function returnApiData($data){
        //扫码支付
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildWap($data);
        //wap支付
        } elseif (in_array($this->code,$this->wap_code)) {
            return $this->useForm($data);
        }
    }
    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data);
        $data ['signMsg'] = $this ->get_sign($string, $this->p_key);//商户私钥加密
        $data['signType'] = '1';
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['inputCharset'] = '1';
        $data['partnerId'] = $this->merId;
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->callback;
        $data['orderNo'] = $this->orderNum;
        $data['orderAmount'] = yuan_to_fen($this->money);
        $data['orderCurrency'] = '156';
        $data['orderDatetime'] = date('YmdHis',time());
        $data['payMode']=$this->getPayType();//充值方式
        $data['subject'] = $this->p_name;
        $data['body'] = time();
        $data['ip'] = get_ip();
        if (7 == $this->code){
            $data['cardNo'] = $this->bank_type;
            $data['bnkCd'] = $this->bank_type;
            $data['accTyp'] = '1';
        }
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
                return '1';//微信扫码
                break;
            case 2:
                return '7';//微信h5
                break;
            case 4:
                return '2';//支付宝
                break;
            case 5:
                return '8';//支付宝H5
                break;
            case 7:
                return '3';//网银
                break;
            case 8:
                return '5';//QQ
                break;
            case 9:
                return '14';//京东
                break;
            case 12:
                return '9';//QQWAP
                break;
            case 13:
                return '11';//京东WAP
                break;
            case 18:
                return '10';//银联WAP
                break;
            case 17:
                return '6';//银联扫码
                break;
            case 25:
                return '4';//快捷
                break;
            default:
                return '2';
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
        if ($data['errCode']<>'0000')
        {
            $msg = isset($msg) ? $msg : '返回参数错误'.$data['errMsg'];
            $this->retMsg("下单失败：{$msg}");
        }
        if(in_array($this->code,$this->scan_code)){
            $pay_url = isset($data['qrCode'])?$data['qrCode']:'';
        }else{
            $pay_url = isset($data['retHtml'])?$data['retHtml']:'';
        }
        return $pay_url;
    }

    /**
     * rsa签名
     * @param        $data
     * @param        $private_key
     * @param string $code
     *
     * @return bool|string
     */
    protected function get_sign($data, $privateKey,$code = 'base64'){

        $key = loadPubPriKey('',$privateKey);
        $privateKey = $key['privateKey'];
        $ret = false;
        if (openssl_sign($data, $ret, $privateKey,OPENSSL_ALGO_SHA1)){
            $ret =  base64_encode(''.$ret);
        }
        return $ret;
    }
}