<?php
/**
 * 和壹付支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Heyifu_model extends Publicpay_model
{
    protected $c_name = 'heyifu';
    protected $p_name = 'HEYIFU';
    //支付接口签名参数
    private $ks = '&key='; //参与签名组成

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
        return $this->buildWap($data);
    }
    
    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        ksort($data);
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        if (25 == $this->code) $data['extend'] = '';
        $data['hmac'] = md5($string);
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['trxMerchantNo'] = $this->merId;//商户号
        $data['trxMerchantOrderno'] = $this->orderNum;
        $data['requestAmount'] = $this->money;
        $data['productNo'] = $this->getPayType();
        $data['noticeSysaddress'] = $this->callback;
        $data['noticeWebaddress'] = $this->returnUrl;
        $data['memberGoods'] = $this->p_name;
        if (7 == $this->code) $data['bankCode'] = $this->bank_type;
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
                return 'WX-JS';//微信
                break;
            case 2:
                return 'WXWAP-JS';//微信
                break;
            case 4:
                return 'ALIPAYQR-JS';//支付宝
                break;
            case 5:
                return 'ALIPAYMOBILE-JS';//支付宝
                break;
            case 7:
                return 'EBANK-JS';//网银
                break;
            case 8:
                return 'QQWALLET-JS';//QQ
                break; 
            case 12:
                return 'QQWALLETWAP-JS';//QQ
                break; 
            case 17:
                return 'UNIONQR';//银联
                break; 
            case 18:
                return 'UNIONWAP';//银联WAP
                break;  
            case 25:
                return 'QUICKPAY-JS';//快捷
                break;
            default:
                return 'ALIPAYQR-JS';
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payUrl'];
    }
}