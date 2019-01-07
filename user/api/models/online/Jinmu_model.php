<?php
/**
 * 金木支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/6/18
 * Time: 16:50
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jinmu_model extends Publicpay_model
{
    protected $c_name = 'jinmu';
    private $p_name = 'JINMU';//商品名称

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
        ksort($data);
        $string = implode('',array_values($data)) . $this->key;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['cid'] = $this->merId;//商户号
        $data['orderno'] = $this->orderNum;
        $data['total_fee'] = yuan_to_fen($this->money);
        $data['title'] = "JinmuZhifu";
        $data['attach'] = date('Y-m-d H:i:s');//自定义参数
        $data['platform'] = $this->getPayType();
        $data['token_url'] = $this->callback;
        $data['cburl']  = $this->returnUrl;
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
                return 'CR';//微信扫码
                break;
            case 2:
                return 'CR_WAP';//微信WAP
                break;
            case 4:
                return 'CR_ALI';//支付宝扫码
                break;
            case 5:
                return 'ALI';//支付宝WAP
                break;
            case 7:
                return 'YL_G';//网关
                break;
            case 8:
                return 'CR_QQ';//QQ钱包扫码
                break;
            case 12:
                return 'TEN_CR';//QQWAP
                break;
            case 25:
                return 'YL_KJ';//银联快捷
                break;
            default:
                return 'CR';//微信扫码
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
        //传递参数为JSON格式 将数组转化成JSON格式
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['code_img_url']) && 
            empty($data['code_url']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        if (!empty($data['code_url']))
        {
            $pay_url = $data['code_url'];
        } else {
            $pay_url = $data['code_img_url'];
        }
        return $pay_url;
    }
}