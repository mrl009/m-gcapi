<?php

/**
 * 超连支付回调
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/10
 * Time: 19:07
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay_model.php';
class Chaolian_model extends Publicpay_model
{
    protected $c_name = 'chaolian';
    private $p_name = 'CHAOLIAN';//商品名称

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
        $k = '&key='.$this->key;
        ksort($data);
        $string = ToUrlParams($data).$k;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_id'] =$this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;
        $data['body'] = $this->p_name;
        $data['amount'] = $this->money;
        $data['type'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['nonce_str'] = uniqid();
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
            case 4:
                return 'alipay';//支付宝
                break;
            case 5:
                return 'alipay';//支付宝app
                break;
            default :
                return 'alipay';
                break;
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if ( $data['status'] <> '1'){
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['data']['result_url'];
        return $pay_url;
    }
}