<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/29
 * Time: 13:42
 */
include_once __DIR__.'/Publicpay_model.php';

class Shoukuanbao_model extends Publicpay_model
{
    protected $c_name = 'shoukuanbao';
    protected $p_name = 'SHOUKUANBAO';
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
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
    //构造参数
    protected function getPayData()
    {
        $data = $this-> getBaseData();
        //构造签名参数
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data[$this->field]= $this-> Tosign($data);
        $data['shop_no']     = $this->orderNum;
        $data['notify_url']= $this->callback;//通知异步回调接收地址
        $data['return_url']   = $this->returnUrl;//成功后返回的跳转地址
        return $data;
    }
    //基本参数
    private function  getBaseData()
    {
        $data['shop_id']     = $this->merId;//商户号
        $data['user_id']     = $this->user['id'];//商户用户id
        $data['money']        = $this->money;
        $data['type']      = $this->getPayType();
        return $data;
    }
    //根据不同的codeid 选出不同的通道
    protected function getPayType()
    {
        switch ($this->code) {
            case 1:
                return 'wechat';//微信扫码
                break;
            case 2:
                return 'wechat';//微信wap
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            default:
                return 'alipay';//
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
        //$pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrcode_url']) && $data['shop_no']<> $this->orderNum)
        {
            $msg = isset($data['type']) ? $data['type'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }

            //扫码支付返回支付二维码连接地址
            return $data['qrcode_url'];
    }
    protected  function Tosign($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if (!is_array($v) && ('sign' <> $k)
                && ("" <> $v) && (null <> $v)
                && ("null" <> $v))
            {
                $buff .=  $v . "+";
            }
        }
        $sign = strtolower(md5($buff.$this->key));
        return $sign;
    }
}