<?php
/**
 * 鑫支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/22
 * Time: 20:08
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Xinzhifu_model extends  Publicpay_model
{
      protected  $c_name = 'xinzhifu';
      protected  $p_name = 'XZF';
      protected  $field  = 'sign';
      protected  $method = 'sign';

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
        $ks = '&key='.$this->key;
        $fd = $this-> field;
        //构造签名参数
        ksort($data);
        $data = get_pay_sign($data,$ks,$fd,'D');
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['mchno'] = $this->merId;
        $data['linkId'] = $this->orderNum;
        $data['price'] = yuan_to_fen($this->money);
        $data['pay_type'] = $this->getPayType();
        $data['bill_title'] = $this->p_name;
        $data['bill_body'] = 'XZFPay';
        $data['ip']  = get_ip();
        $data['notify_url'] = $this->callback;
        $data['nonce_str'] = create_guid();
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
                return '7';//微信扫码 1,2,4,5,8,9,12,17,25
                break;
            case 2:
                return '3';//微信WAP
                break;
            case 4:
                return '6';//支付宝扫码
                break;
            case 5:
                return '4';//支付宝WAP
                break;
            case 7:
                return '11';//网关
                break;
            case 8:
                return '9';//QQ扫码
                break;
            case 9:
                return '12';//京东扫码
                break;
            case 17:
                return '13';//银联钱包扫码
                break;
            case 18:
                return '14';//银联wap
                break;
            case 25:
                return '10';//快捷支付
                break;
            default:
                return '6';//支付宝扫码
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
        $data = json_encode($pay_data,JSON_UNESCAPED_SLASHES);
        $data = post_pay_data($this->url,$data);
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['order']['pay_link'])|| '200' <> $data['resultCode'])
        {
            $msg = "返回参数错误";
            if (isset($data['resultCode'])) $msg = $data['resultCode'];
            $this->retMsg("下单失败：{$msg}");
        }
        $codeurl = $data['order']['pay_link'];
        return $codeurl;
    }
}