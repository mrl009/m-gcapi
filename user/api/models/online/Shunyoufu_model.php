<?php
/**
 * 顺优付支付接口调用
 * User: lqh
 * Date: 2018/06/20
 * Time: 13:40
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Shunyoufu_model extends Publicpay_model
{
    protected $c_name = 'shunyoufu';
    private $p_name = 'SYF';//商品名称
    private $ks = '&key=';

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
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k; 
        $data['sign_type'] = 'MD5';
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['version'] = '1.0';
        $data['mer_no'] = $this->merId;//商户号
        $data['back_url'] = $this->callback;
        $data['mer_return_msg'] = $this->orderNum;
        $data['mer_order_no'] = $this->orderNum;
        $data['gateway_type'] = $this->getPayType();
        $data['currency'] = 156;
        $data['trade_amount'] = $this->money;
        $data['order_date'] = date('Y-m-d H:i:s');
        $data['client_ip'] = get_ip();
        $data['goods_name'] = $this->p_name;
         //网关参数
        if(7 == $this->code) 
        {
            $data['version'] = '2.0';
            $data['bank_code'] = $this->bank_type;
        }
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = isset($pay['pay_url']) ? trim($pay['pay_url']) : '';
        if (7 == $this->code) {
            $pay_url .= '/web/receive';
        } else {
            $pay_url .= '/api/paymentAPI';
        }
        return $pay_url;
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
                return '002';//微信扫码
                break; 
            case 2:
                return '001';//微信WAP
                break;   
            case 4:
                return '003';//支付宝扫码
                break;
            case 5:
                return '006';//支付宝WAP
                break; 
            case 7:
                return '000';//网关支付
                break;
            case 8:
                return '004';//QQ钱包扫码
                break;
            case 9:
                return '013';//京东扫码
                break;
            case 17:
                return '012';//银联扫码
                break;
            case 18:
                return '008';//银联WAP
                break;
            default:
                return '003';//支付宝
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
        if (empty($data)) $this->retMsg('接口无信息返回');
        //接收参数为JSON格式的对象 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误');
        //判断是否下单成功
        if (empty($data['payInfo']))
        {
            $msg = isset($data['errorMsg']) ? $data['errorMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payInfo'];
    }
}
