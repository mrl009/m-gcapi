<?php
/**
 * 聚宝盆支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jubaopen_model extends Publicpay_model
{
    protected $c_name = 'jubaopen';
    private $p_name = 'JBP';//商品名称
    private $isImgUrl = 0; //默认返回的不是二维码地址

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
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
        //扫码支付 需要判断是否返回的是在一个二维码地址
        } elseif (in_array($this->code,$this->scan_code)) {
            if (!empty($this->isImgUrl) && (1 == $this->isImgUrl))
            {
                return $this->buildWap($data);
            } else {
                return $this->buildScan($data);
            }
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
        $string = ToUrlParams($data) . $this->key;
        if (7 == $this->code) $data['bank_code'] = $this->bank_type;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();
        $data['value'] = $this->money;
        $data['orderid'] = $this->orderNum;
        $data['callbackurl'] = $this->callback;
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
                return '6003';//微信扫码
                break;   
            case 2:
                return '6004';//微信WAP
                break;
            case 4:
                return '6002';//支付宝扫码
                break;
            case 5:
                return '6001';//支付宝WAP
                break;
            case 7:
                return '6006';//网银支付
                break;
            case 25:
                return '6005';//快捷
                break;
            default:
                return '6002';//支付宝扫码
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
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //确认下单是否成功
        if (empty($data['code_url']))
        {
            $msg = isset($data['code']) ? $data['code'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //如果code_url_type为redirect 直接返回二维码图片地址
        if (!empty($data['code_url_type']) && ('redirect' == $data['code_url_type']))
        {
            $this->isImgUrl = 1; 
        }
        return $data['code_url'];
    }
}
