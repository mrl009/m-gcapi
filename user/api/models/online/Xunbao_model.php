<?php
/**
 * 讯宝商务支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xunbao_model extends Publicpay_model
{
    protected $c_name = 'xunbao';
    private $p_name = 'XUNBAO';//商品名称

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
        return $this->buildForm($data);
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
                return '8011';//微信扫码
                break;   
            case 2:
                return '933';//微信WAP
                break;
            case 4:
                return '8012';//支付宝扫码
                break;
            case 5:
                return '931';//支付宝WAP
                break; 
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '993';//QQ扫码
                break;
            case 9:
                return '911';//京东扫码
                break;
            case 12:
                return '935';//QQwap
                break; 
            case 17:
                return '7011';//银联扫码
                break;
            case 25:
                return '2004';//快捷
                break;
            default:
                return '8011';//微信扫码
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
        //判断返回的是否是支付地址
        if ((false !== strpos($data,'http')) || 
           (false !== strpos($data,'HTTP')) ||
           (false !== strpos($data,'WWW')) || 
           (false !== strpos($data,'www')))
        {
            return $data;
        } else {
            $cart = array('Unicode','ASCII','GB2312','GBK','UTF-8');
            $data = mb_convert_encoding($data,"UTF-8",$cart);
            $this->retMsg("下单失败：{$data}");
        }
    }
}
