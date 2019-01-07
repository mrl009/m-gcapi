<?php
/**
 * 八度支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/25
 * Time: 11:13
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Badu_model extends Publicpay_model
{
    protected $c_name = 'badu';
    private $p_name = 'BADU';//商品名称
    //支付接口签名参数
    private $ks = '&'; //参与签名组成

    public function __construct()
    {
        parent::__construct();
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
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['get_code'] = 1;
        $data['paytype'] = $this->getPayType();
        //网银参数
        if (7 == $this->code)
        {
            $data['bankcode'] = $this->bank_type;
        }
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['customerid'] = $this->merId;//商户号
        $data['total_fee'] = yuan_to_fen($this->money);
        $data['sdorderno'] = $this->orderNum;
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
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
                return 'weixin';//微信扫码
                break;
            case 2:
                return 'weixin';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银直连
                break;
            case 8:
                return 'qq';//QQ扫码
                break;
            case 12:
                return 'qqwap';//QQwap
                break;
            case 22:
                return 'tenpay';//财付通
                break;
            default:
                return 'alipay';//支付宝扫码
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
        //传递参数
        $post = http_build_query($pay_data);//print_r($post);
        $data = post_pay_data($this->url,$post);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['url']))
        {
            $msg = "返回参数错误";
            if (isset($data['code'])) $msg = $data['code'];
            if (isset($data['msg'])) $msg = $data['msg'];
            $this->retMsg("下单失败：".$data['msg'].$data['code']);
        }
        //扫码支付
        if(in_array($this->code,$this->scan_code)){
            return $data['url'];
        }else{
        //返回WAP支付地址
            return $data['qrcodeurl'];
        }
    }

}