<?php

/**
 * 快捷支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/16
 * Time: 14:00
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Kuaijie_model extends Publicpay_model
{
   protected $c_name ='kuaijie';
   protected $p_name ='KUAIJIE';
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&appkey='; //参与签名组成
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
        //扫码支付
        if (in_array($this->code,[4]))
        {
            return $this->buidImage($data);
            //wap支付
        } else {
            return $this->buildWap($data);
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
   protected function getBaseData()
   {
       $data['amount']    = $this -> money;
       $data['notifyUrl'] = $this ->callback;
       $data['frontUrl']  = $this ->returnUrl;
       $data['spbillCreateIp'] = get_ip();
       $data['payType']   = $this->getPayType();
       $data['tradeNo']   = $this->orderNum;
       $data['merchantNo']= $this->merId;
       $data['version']   = '1.0';
       $data['date']      = time();
       return $data;
   }
   protected function getPayType()
   {
       switch ($this->code)
       {
           case 4:
               return 'qrcode';//支付宝支付
               break;
           case 5:
               return 'H5';//支付宝支付
               break;
           default:
               return 1;//支付宝支付
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
        //传递参数为json格式
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //验证返回数据
        ksort($data);
        $vsign = $data['sign'];
        $sign  =  md5(ToUrlParams($data) .$this->key_string . $this->key );
        if(strtoupper($vsign) <> strtoupper($sign)){
            $this->retMsg('下单返回数据验证签名失败');
        }
        //判断是否下单成功
        if (empty($data['payCode']) || $data['code'] <> '100')
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payCode'];
    }
}