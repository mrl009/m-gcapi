<?php
/**
 * 威富通支付接口调用
 * User: lqh
 * Date: 2018/05/04
 * Time: 18:32
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Weifutong_model extends Publicpay_model
{
    protected $c_name = 'weifutong';
    private $p_name = 'WFT';
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名
    
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['service'] = $this->getPayType();//接口标识
        $data['mch_id'] = $this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;// 订单号
        $data['body'] = $this->p_name;//商品描述
        $data['total_fee'] = yuan_to_fen($this->money);//金额 单位分
        $data['mch_create_ip'] = get_ip();//终端IP
        $data['notify_url'] = $this->callback;//异步返回地址
        $data['nonce_str'] = create_guid();//随机字符串
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
                return 'pay.weixin.native';//微信扫码
                break;
            case 2:
                return 'pay.weixin.wappay';//微信wap
                break;
            case 4:
                return 'pay.alipay.native';//支付宝扫码
                break;
            case 8:
                return 'pay.tenpay.native';//QQ钱包扫码
                break;
            case 9:
                return 'pay.jdpay.native';//京东扫码
                break;
            case 12:
                return 'pay.tenpay.wappay';//QQ钱包wap
                break;
            case 17:
                return 'pay.unionpay.native';//银联钱包扫码
                break;    
            default:
                return 'pay.weixin.native';
                break;
        }
    }
    
    /**
     * @param $data 支付参数
     * @return return  二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为XML格式 将数组转化成XML格式
        $xml = ToXml($pay_data);
        $data = post_pay_data($this->url,$xml);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为XML格式 转化为数组
        $data = FromXml($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['result_code']) || ('0' <> $data['result_code']) 
           || (empty($data['pay_info']) && empty($data['code_url'])))
        {
            $msg = isset($data['err_msg']) ? $data['err_msg'] : '返回参数错误';
            $this->retMsg("下单失败: {$msg}");
        }
        //扫码支付返回 二维码地址 wap支付返回支付地址
        if (!empty($data['code_url']))
        {
            $pay_url = $data['code_url']; //二维码地址
        } else { 
            $pay_url = $data['pay_info']; //wap支付地址
        }
        return $pay_url;
    }
}
