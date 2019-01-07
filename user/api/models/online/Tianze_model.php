<?php
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/31
 * Time: 11:14
 */
defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Publicpay_model.php';

class Tianze_model extends Publicpay_model
{
    protected $c_name = 'tianze';
    private $p_name = 'TIANZE';//商品名称
    private $p0_Cmd = "Buy";//业务类型支付请求，固定值"Buy"
    private $p9_SAF = "0";//为"1": 需要用户将送货地址留在API支付系统;为"0": 不需要，默认为 "0".
    private $pr_NeedResponse = '1';
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
        ksort($data);
        $string = $this->ToParams($data);
        $data['hmac'] = HmacMd5($string,$this->key);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['p0_Cmd'] = 'Buy'; //接口版本号
        $data['p1_MerId'] = $this->merId;//商户号
        $data['p2_Order'] = $this->orderNum;
        $data['p3_Amt'] = $this->money;
        $data['p4_Cur'] = 'CNY';
        $data['p5_Pid'] = 'TZpay';
        $data['p6_Pcat'] = 'one';
        $data['p7_Pdesc'] = 'XY';
        $data['p8_Url'] = $this->callback;
        $data['p9_SAF'] = '0';
        $data['pa_MP'] = 'xinjin';
        $data['pd_FrpId'] = $this->getPayType();
        //$data['pd_FrpId'] = 'paydesk';
        $data['pr_NeedResponse'] = "1";
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
                return 'wxcode';//微信
                break;
            case 2:
                return 'wxwap';//微信WAP 
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 8:
                return 'qqpay';//QQ钱包扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqpaywap';//QQWAP
                break;
            case 13:
                return 'jdwap';//QQWAP
                break;
            case 17:
                return 'OnLine';//银联钱包扫码
                break;
            case 18:
                return 'unionpay';//银联钱包
                break;
            case 25:
                return 'OnLineKJ';//快捷
                break;
            default :
                return 'alipay';
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
        $url = '';
        $this->from_way = $pay['from_way'];
        if (in_array($this->from_way,[1,2]))
        {
            $url = 'http://101.132.128.241/GateWay/ReceiveBankmobile.aspx';
        }else{
            $url = 'http://101.132.128.241/GateWay/ReceiveBank.aspx';
        }
        return $url;
    }

    /**
     * 将数组的键与值用&符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
    protected function ToParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= "" . $v ;
            }
        }
        $buff = trim($buff, "");
        return $buff;
    }
}