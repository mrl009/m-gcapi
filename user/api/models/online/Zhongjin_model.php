<?php
/**
 * 中金支付接口调用
 * User: lqh
 * Date: 2018/06/06
 * Time: 13:40
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhongjin_model extends Publicpay_model
{
    protected $c_name = 'zhongjin';
    private $p_name = 'ZHONGJIN';//商品名称

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
        if (in_array($this->code,[2,5,7,13,25]))
        {
            return $this->buildWap($data);
        } else {
            return $this->buildScan($data);
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
        ksort($data);
        $string = ToUrlParams($data);
        $k = pack("H*", $this->key);
        $sign = hash_hmac("sha1",$string,$k,false);
        $data['Sign'] = $sign;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['Merchant'] = $this->merId;//商户号
        $data['Amount'] = yuan_to_fen($this->money);
        $data['Source'] = $this->getPayType();
        $data['Serial'] = $this->orderNum;
        $data['NotifyUrl'] = $this->callback;
        $data['BackUrl'] = $this->callback;
        $data['SignType'] = 'HMAC';
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
                return 'WEIXIN';//微信扫码
                break;   
            case 2:
                return 'WEIXINH5';//微信WAP
                break;
            case 4:
            case 5:
                return 'ALIPAY';//支付宝扫码、WAP
                break; 
            case 7:
                return 'DEBITGW';//网关支付
                break;
            case 8:
                return 'QQ';//QQ钱包扫码
                break;
            case 9:
            case 13:
                return 'JD';//京东扫码、WAP
                break;  
            case 17:
                return 'UNION';//银联钱包
                break;
            case 25:
                return 'FASTPAY';//银联快捷
                break;
            default:
                return 'WEIXIN';//微信扫码
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
        //传递参数为JSON格式 将数组转化成JSON格式
        $pay_data = json_encode($pay_data);
        //对方.net语言无法接受“\/\/”这种转义字符，需要替换成正常的数据
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (!isset($data['Code']) || ('00' <> $data['Code']) 
            || (empty($data['CodeUrl'])))
        {
            $msg = isset($data['Desc']) ? $data['Desc'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['CodeUrl'];
    }
}
