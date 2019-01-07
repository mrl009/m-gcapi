<?php
/**
 * 信付支付接口调用
 * User: lqh
 * Date: 2018/07/17
 * Time: 10:51
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinfu_model extends Publicpay_model
{
    protected $c_name = 'xinfu';
    private $p_name = 'XINFU';//商品名称

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
        return $this->buildForm($data,'get');
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
        $string = data_to_string($data) . $this->key;
        $data['hrefbackurl'] = $this->returnUrl;
        $data['payerIp'] = get_ip();
        $data['attach'] = $this->p_name;
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
                return '1004';//微信扫码
                break;   
            case 2:
                return '1007';//微信WAP
                break;
            case 4:
                return '1003';//支付宝扫码
                break;
            case 5:
                return '1006';//支付宝WAP
                break; 
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '1009';//QQ扫码
                break;
            case 12:
                return '1008';//QQwap
                break; 
            case 13:
                return '1010';//京东钱包
                break; 
            case 17:
                return '2000';//银联钱包
                break;
            case 25:
                return '1005';//快捷
                break;
            default:
                return '1004';//微信扫码
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
        if (empty($data['codeUrl']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['codeUrl'];
    }
}
