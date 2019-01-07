<?php

/**通兴支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/23
 * Time: 11:16
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Tongxing_model extends Publicpay_model
{
    protected $c_name = 'tongxing';
    protected $p_name = 'TONGXING';//商品名称
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
        $string = strtolower(data_to_string($data) . $k);
        $data['sign'] = md5($string);
        $data['paytype'] = $this->getPayType();
        //网银参数
        if ( $this->code==7)
        {
            $data['bankcode'] = $this->bank_type;
        }
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
                return 'weixin';//
                break;
            case 4:
                return 'alipaywap';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银直连
                break;
            case 8:
                return 'qq';//qq扫码
                break;
            case 12:
                return 'qqwap';//qqwap
                break;
            case 22:
                return 'tenpay';//财付通
                break;
            case 33:
                return 'gzhpay';//微信公众号
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
        $data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$data);
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if ('1'<>$data['code']|| empty($data['url']))
        {
            $msg = "返回参数错误";
            if (isset($data['msg'])) $msg = $data['msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        if(in_array($this->code,$this->scan_code)){
            $codeurl = $data['url'];
        }else{
            $codeurl = $data['qrcodeurl'];
        }
        return $codeurl;
    }
}