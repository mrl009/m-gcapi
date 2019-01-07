<?php

/**微富宝支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/19
 * Time: 22:26
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Weifubao_model extends  Publicpay_model
{
    protected $c_name = 'weifubao';
    private $p_name = 'WFB';
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
        ksort($data);
        $string = ToUrlParams($data) . $k;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['service']   = $this->getPayType();//接口标识
        $data['mch_id'] = $this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;// 订单号
        $data['amount'] = $this->money;//金额 单位分
        $data['nonce_str'] = create_guid();//随机字符串
        $data['notify_url'] = $this->callback;//异步返回地址
        $data['spbill_create_ip'] = get_ip();//终端IP
        $data['product_info'] = $this->p_name;//商品描述
        $data['charset']   = 'utf-8';
        $data['sign_type'] = 'MD5';
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
                return 'pay.wechat.qrcode';//微信扫码
                break;
            case 2:
                return 'pay.wechat.app';//微信wap
                break;
            case 4:
                return 'pay.alipay.qrcode';//支付宝扫码
                break;
            case 5:
                return 'pay.alipay.mweb';//支付宝扫码
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
            case 34:
                return 'pay.wechat.mweb';//微信
                break;
            case 36:
                return 'pay.alipay.gateway';//支付宝
                break;
            default:
                return 'pay.alipay.qrcode';
                break;
        }
    }

    /**
     * @param $data 支付参数
     * @return return  二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为json格式
        $pay_data = json_encode($pay_data, JSON_UNESCAPED_UNICODE);
        $data = post_pay_data($this->url,$pay_data,'json','utf-8');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        //判断下单是否成功
        if (!isset($data['return_code']) || ('SUCCESS' <> $data['return_code'])
            || (empty($data['pay_info'])))
        {
            $msg = isset($data['error_msg']) ? $data['error_msg'] : '返回参数错误';
            $this->retMsg("下单失败: {$msg}");
        }
        //扫码支付返回 二维码地址 wap支付返回支付地址
       $payinfo = base64_decode($data['pay_info']);
        return $payinfo;
    }
}