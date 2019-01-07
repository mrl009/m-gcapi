<?php
/**
 * 七星支付接口调用
 * User: lqh
 * Date: 2018/08/19
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Qixing_model extends Publicpay_model
{
    protected $c_name = 'qixing';
    private $p_name = 'QIXING';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'sign'; //签名参数名

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
        $data['mch_id'] = $this->merId;
        $data['service'] = $this->getPayType();
        $data['version'] = 'V3.0';
        $data['charset'] = 'UTF-8';
        $data['sign_type'] = 'MD5';
        $data['out_trade_no'] = $this->orderNum;
        $data['body'] = $this->p_name;
        $data['attach'] = $this->p_name;
        $data['total_fee'] = yuan_to_fen($this->money);
        $data['notify_url'] = $this->callback;
        $data['callback_url'] = $this->returnUrl;
        $data['nonce_str'] = create_guid();
        $data['client_ip'] = get_ip();
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
                return 'weixin.native';//微信扫码
                break;
            case 2:
                return 'weixin.wappay';//微信WAP
                break;
            case 4:
                return 'ali.native';//支付宝扫码
                break;
            case 5:
                return 'ali.wappay';//支付宝WAP
                break;
            case 8: 
                return 'qq.native';//QQ扫码
                break;
            case 9:
                return 'jd.native';//京东扫码
                break;
            case 12:
                return 'qq.wap';//QQWAP
                break;
            case 13:
                return 'jd.wap';//京东WAP
                break;
            case 25:
                return 'unionpay.quick';//快捷支付
                break;
            default:
                return 'ali.native';//支付宝扫码
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
        //传递参数为json格式数据
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['pay_info']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['pay_info'];
    }
}
