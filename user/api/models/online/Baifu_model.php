<?php
/**
 * 佰富支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay_model.php';

class Baifu_model extends Publicpay_model
{
    protected $c_name = 'baifu';
    private $p_name = 'BAIFU';//商品名称

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
        $sign_data = json_encode($data);
        $sign_data = str_replace('\/\/','//',$sign_data);
        $sign_data = str_replace('\/','/',$sign_data);
        $string = $sign_data . $this->key;
        //条码支付特殊参数
        if (in_array($this->code,[40,41]))
        {
            $data['scanType'] = 'Page';
        }
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $money = yuan_to_fen($this->money);
        $data['merchantNo'] = $this->merId;//商户号
        $data['netwayCode'] = $this->getPayType();
        $data['randomNum'] = sprintf("%04d",mt_rand(1,9999));
        $data['orderNum'] = $this->orderNum;
        $data['payAmount'] = (string)$money;
        $data['goodsName'] = $this->p_name;
        $data['callBackUrl'] = $this->callback;
        $data['frontBackUrl'] = $this->returnUrl;
        $data['requestIP'] = get_ip();
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
                return 'WX';//微信扫码
                break;   
            case 2:
                return 'WX_WAP';//微信WAP
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFB_WAP';//支付宝WAP
                break; 
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JDQB';//京东扫码
                break;
            case 12:
                return 'QQ_WAP';//QQwap
                break; 
            case 13:
                return 'JDQB_WAP';//京东wap
                break;
            case 17:
                return 'YL';//银联扫码
                break;
            case 25:
                return 'KJ';//快捷
                break;
            case 40:
                return 'WX_VERSA_SCAN';//微信条码
                break;
            case 41:
                return 'ZFB_VERSA_SCAN';//支付宝条码
                break;
            default:
                return 'WX';//微信扫码
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
        //传递参数JSON格式数据 (paramData的参数内容为json数据)
        $pay_data = json_encode($pay_data);
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $post['paramData'] = $pay_data;
        $post = http_build_query($post);
        $data = post_pay_data($this->url,$post);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['CodeUrl']))
        {
            $msg = "返回参数错误";
            if (isset($data['msg'])) $msg = $data['msg'];
            if (isset($data['resultMsg'])) $msg = $data['resultMsg'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回WAP支付地址
        return $data['CodeUrl'];
    }
}
