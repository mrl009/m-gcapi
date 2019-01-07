<?php

/**
 * 新金通支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/31
 * Time: 14:08
 */
defined('BASEPATH')or exit('No direct script access allowed');
//调用公共文件
include_once  __DIR__.'/Publicpay_model.php';
class Xjintong_model extends Publicpay_model
{
    protected  $c_name ='xjintong';
    protected  $p_name = 'XJINTONG';

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
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            return $this->buidImage($data);
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
        $k = $this->key;
        $string = $this->String($data,$k);
        $data['sign'] = md5($string);
        $data['sign_type'] = 'md5';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['id'] = $this->merId;//商户号
        $data['money'] = $this->money;
        $data['name'] = $this->c_name;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['out_trade_no'] = $this->orderNum;
        $data['user_name'] = $this->user['id'];
        $data['type'] = $this->getPayType();
        $data['sitename'] = $this->p_name;
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
            case 2:
                return 'wechat';//微信扫码
                break;
            case 4:
            case 5:
                return 'alipay';//支付宝扫码
                break;
            default:
                return 'alipay';//微信扫码
                break;
        }
    }
    private function String($data,$k){
        $arr = [];
        $string = '';
        foreach($data as $key => $val)
        {
            if (!is_array($val))
            {
                $arr[] .= $val;
            }
        }
        //将参数值排序
        sort($arr,SORT_STRING);
        $string =  implode($arr).$k;
        return $string;
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
        if (empty($data['data'] || $data['code']<>1))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }

        if(in_array($this->code,$this->scan_code)){
            //扫码支付
            $payUrl = $data['data']['qrcode_url'];
        }else if(in_array($this->code,$this->wap_code)){
            //wap支付域名
            $payUrl = $data['data']['pay_url'];
        }
        //扫码支付返回支付二维码连接地址
        return $payUrl;
    }

}