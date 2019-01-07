<?php
/**
 * 元宝支付接口调用 (更新)
 * User: lqh
 * Date: 2018/08/06
 * Time: 10:01
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yuanbao_model extends Publicpay_model
{
    protected $c_name = 'yuanbao';
    private $p_name = 'YUANBAO';//商品名称

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
        if (in_array($this->code,$this->scan_code))
        {
           return $this->buildScan($data);
        } else {
            return $this->buildForm($data);
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
        $string = data_to_string($data) . $this->key;
        $data['hrefbackurl'] = $this->returnUrl;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['version'] = '3.0';
        $data['method'] = 'Boh.online.interface';
        $data['partner'] = $this->merId;//商户号
        $data['banktype'] = $this->getPayType();
        $data['paymoney'] = $this->money;
        $data['ordernumber'] = $this->orderNum;
        $data['callbackurl'] = $this->callback;
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
                return 'WEIXINWAP';//微信WAP
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAYWAP';//支付宝wap
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JD';//京东扫码
                break;
            case 10:
                return 'BAIDU';//百度扫码
                break;
            case 12:
                return 'QQWAP';//QQwap
                break;
            case 13:
                return 'JDWAP';//京东wap
                break;
            case 17:
                return 'UNIONPAY';//银联扫码
                break;
            case 18:
                return 'UNIONPAYWAP';//银联wap
                break;  
            case 20:
                return 'BAIDUWAP';//百度wap
                break;
            case 25:
                return 'WCKPAY';//银联快捷
                break;
            default:
                return 'WEIXIN';
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
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrurl']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['qrurl'];
    }
}
