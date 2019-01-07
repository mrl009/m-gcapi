<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/5
 * Time: 10:05
 */
include_once __DIR__.'/Publicpay_model.php';

class Zhongtietong_model extends Publicpay_model
{
    protected $c_name = 'zhongtietong';
    private $p_name = 'ZHONGTIETONG';//商品名称
    private $key_string = '&key='; //参与签名组成

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
        ksort($data);
        $k =$this->key;
        $string = $this->Params($data) . $k;
        $data['sign'] = strtolower(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_no'] = $this->merId;
        $data['version'] = 'v1';
        $data['channel_no'] = '01';
        $data['out_trade_no'] = $this->orderNum;
        $data['amount'] = yuan_to_fen($this->money);
        $data['channel'] = $this->getPayType();
        $data['goods_name'] = $this->p_name;
        $data['remark'] = 'LuyiPay';
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
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
                return 'WXZF';//微信扫码 1,2,4,5,8,9,12,17,25
                break;
            case 2:
                return 'WXWAP';//微信WAP
                break;
            case 4:
                return 'ZFBZF';//支付宝扫码
                break;
            case 5:
                return 'ZFBWAP';//支付宝WAP
                break;
            case 8:
                return 'QQZF';//QQ扫码
                break;
            case 9:
                return 'JDZF';//京东扫码
                break;
            case 12:
                return 'QQWAP';//QQWAP
                break;
            case 17:
                return 'YLZF';//银联钱包扫码
                break;
            case 25:
                return 'FASTPAY';//快捷支付
                break;
            case 26:
                return 'QPAY';//快捷支付卡
                break;
            default:
                return 'ZFBWAP';//支付宝扫码
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
        $data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['return_url'])|| '1'<>$data['return_code'])
        {
            $msg = "返回参数错误";
            if (isset($data['return_msg'])) $msg = $data['return_code'].$data['return_msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        $codeurl = $data['return_url'];
        return $codeurl;
    }
    /**
     * 将数组的键与值用符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
    protected function Params($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k <>$this->field && $v <> ""
                && !is_array($v)&& $v <>null ){
                $buff .= $v;
            }
        }
        return $buff;
    }

}