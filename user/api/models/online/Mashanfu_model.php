<?php

/**
 * 码闪付支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/23
 * Time: 11:57
 */
defined('BASEPATH')or exit('No direct script access allowed');
include_once  __DIR__.'/Publicpay_model.php';
class Mashanfu_model extends Publicpay_model
{
    protected $c_name = 'mashanfu';
    private $p_name = 'MASHANFU';//商品名称


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
        //构造参与加密参数
        $data = $this->getBaseData();
        $params= $this->getdat();
        //参数转化成json数据
        $string = json_encode($params,320);
        $string = str_replace('\/\/','//',$string);
        $string = str_replace('\/','/',$string);
        $data['data'] = $this->encodePay($string);
        $string .= $this->key;
        $data['sign'] = strtoupper(md5($string));
        //改版根据版本选提交参数
        if(in_array($this->code,[36])){
            $data['version'] = '1.0';
        }else if(in_array($this->code,[41])){//个人收款
            $data['version'] = '3.0';
            //$data['mode']    = 'json';
        }
        //删除中间变量
        unset($params,$string,$rsaMsg);
        return $data;
    }


    /*
     * 秘钥加密方式
     */
    private function encodePay($data)
    {
        $str = '';
        $encryptData = '';
        $bk = openssl_pkey_get_public($this->b_key);
        foreach (str_split($data, 117) as $chunk)
        {
            openssl_public_encrypt($chunk, $encryptData, $bk);
            $str = $str . $encryptData;
        }
        return base64_encode($str);
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merNo'] = $this->merId;
        return $data;
    }

    /**
     * 获取构造支付基本参数
     * @return array
     */
    private function getdat()
    {
        $data['amount'] = yuan_to_fen($this->money);
        $data['channelCode'] = $this->getPayType();
        $data['goodsName'] = $this->p_name;
        $data['orderNum'] = $this->orderNum;
        $data['organizationCode'] = $this->merId;
        $data['payResultCallBackUrl'] = $this->callback;
        $data['payViewUrl'] = $this->returnUrl;
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
                return 'Wechat';//微信扫码
                break;
            case 2:
                return 'WechatWap';//微信WAP
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFBWAP';//支付宝WAP
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 12:
                return 'QQH5';//QQWAP
                break;
            case 41:
                return 'ZFB';//支付宝WAP
                break;
            case 36:
                return 'ZFB';//支付宝收银台h5
                break;
            default:
                return 'ZFB';//支付宝扫码
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
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data'])||$data['status']<>200)
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return strtolower($data['data']);
    }
}