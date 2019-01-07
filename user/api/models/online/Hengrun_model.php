<?php

/**
 *恆润支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/4
 * Time: 16:01
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Hengrun_model extends Publicpay_model
{
    protected $c_name = 'hengrun';
    protected $p_name = 'HENGRUN';
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '|'; //参与签名组成
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
        $string = data_value($data,$this->key_string).$k;
        $data[$f] = strtoupper(md5($string));
        $param  = 'ApplyParams='.json_encode($data,JSON_UNESCAPED_SLASHES);
        return $param;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['appID']       = $this->merId;//商户号
        $data['outTradeNo']  = $this->orderNum;
        $data['tradeCode']   = $this->getPayType();
        $money = yuan_to_fen($this->money);
        $data['totalAmount'] = (string)$money;
        $data['randomNo']    = create_guid();
        $data['productTitle']= $this->c_name;
        $data['tradeIP']     = get_ip();
        //$data['tradeIP']     = '219.141.153.11';
        $data['notifyUrl']   = $this->callback;
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
                return '60001';//微信扫码
                break;
            case 2:
                return '80001';//微信WAP
                break;
            case 4:
                return '60002';//支付宝扫码
                break;
            case 5:
                return '80002';//支付宝WAP
                break;
            case 8:
                return '60003';//qq扫码
                break;
            case 9:
                return '60004';//京东扫码
                break;
            case 12:
                return '80003';//qqwap
                break;
            case 13:
                return '80004';//京东wap
                break;
            case 17:
                return '60005';//银联扫码
                break;
            case 18:
                return '80005';//银联wap
                break;
            case 25:
                return '60006';//银联快捷
                break;
            case 28:
                return '30002';//个人支付宝
                break;
            case 33:
                return '30001';//个人微信
                break;
            default:
                return '60002';//
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
        $data = post_pay_data($this->url,$pay_data);
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payURL']) || $data['stateCode'] <> '0000')
        {
            $msg = isset($data['stateInfo']) ? $data['stateInfo'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['payURL'];
    }
}