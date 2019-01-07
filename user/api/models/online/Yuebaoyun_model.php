<?php
/**
 * 粤宝云支付接口调用
 * User: lqh
 * Date: 2018/07/16
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yuebaoyun_model extends Publicpay_model
{
    protected $c_name = 'yuebaoyun';
    private $p_name = 'YUEBAOYUN';//商品名称

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
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildWap($data);
        } else {
            return $this->buildForm($data);
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
        $k = md5($this->key);
        $string = implode("",array_values($data)) . $k;   
        $data['create_ip'] = get_ip();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        if (7 == $this->code)
        {
            $data['card_type'] = 1;
            $data['yl_pay_type'] = 'B2C';
            $data['bank_name'] = $this->bank_type;
        }
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['order_id'] = $this->orderNum;
        $data['money'] = yuan_to_fen($this->money);
        $data['pay_type'] = $this->getPayType();
        $data['time'] = time();
        $data['mch'] = $this->merId;//商户号
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
                return 'wxqrcode';//微信扫码
                break; 
            case 2:
                return 'wxhtml';//微信WAP
                break;   
            case 4:
                return 'aliqrcode';//支付宝扫码
                break;
            case 5:
                return 'aliwap';//支付宝WAP
                break;
            case 7:
                return 'ylggp';//网银直连
                break;
            case 8:
                return 'qqqrcode';//QQ扫码
                break;
            case 9:
                return 'jdqrcode';//京东扫码
                break;
            case 17:
                return 'ylqrcode';//银联扫码
                break;
            case 25:
                return 'ylpay';//快捷支付
                break;     
            default:
                return 'aliqrcode';//支付宝扫码
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['img']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['data']['img'];
    }
}
