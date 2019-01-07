<?php

/**
 * ABC支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/13
 * Time: 15:24
 */
defined('BASEPATH')or exit('No direct script access allowed');
//公共接口调用
include_once  __DIR__.'/Publicpay_model.php';
class Abc_model extends Publicpay_model
{
    protected $c_name = 'abc';
    protected $p_name = 'ABC';
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
        if (7 == $this->code){
            $data['userId'] = $this->user['id'];
            $data['bankName'] = $this->bank_type;
        }
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['tenantNo'] = $this->merId;//商户号
        $data['tenantOrderNo'] = $this->orderNum;
        $data['payType'] = $this->getPayType();
        $data['amount'] = $this->money;
        $data['pageUrl'] = $this->returnUrl;
        $data['notifyUrl'] = $this->callback;
        $data['remark'] = $this->c_name;
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
                return 'wxpay';//微信扫码
                break;
            case 2:
                return 'wxpay';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            default:
                return 'alipay';//微信扫码
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
        //传递参数{ "status":200, "url":"http://test.test.com/something" }
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['url']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['url'];
    }
}