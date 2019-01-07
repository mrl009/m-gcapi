<?php
/**
 * 信誉支付接口调用
 * User: lqh
 * Date: 2018/06/13
 * Time: 10:22
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinyu_model extends Publicpay_model
{
    protected $c_name = 'xinyu';
    private $p_name = 'XINYU';
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名
    
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
        if (in_array($this->code,[2,5,7,12,25]))
        {
            return $this->buildWap($data);
        } else {
            return $this->buildScan($data);
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['mch_id'] = $this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;// 订单号
        //网关支付参数
        if (7 == $this->code) $data['bank_id'] = $this->bank_type;
        $data['body'] = $this->p_name;//商品描述
        $data['total_fee'] = yuan_to_fen($this->money);//金额 单位分
        $data['mch_create_ip'] = get_ip();//终端IP
        $data['notify_url'] = $this->callback;//异步返回地址
        $data['nonce_str'] = create_guid();//随机字符串
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //获取用户登录的token值
        $token = $this->getToken();
        //构造支付网关地址，每一种支付方式都不一样 
        $base_url = 'http://api.xinyuzhifu.com/sig/v1/';
        switch ($this->code)
        {
            case 1:
                $url = 'wx/native';//微信扫码
                break;
            case 2:
                $url = 'wx/wappay';//微信wap
                break;
            case 4:
                $url = 'alipay/native';//支付宝扫码
                break;
            case 5:
                $url = 'alipay/wap';//支付宝wap
                break;
            case 7:
                $url = 'union/net';//网关
                break;
            case 8:
                $url = 'qq/native';//QQ钱包扫码
                break;
            case 9:
                $url = 'jd/native';//京东扫码
                break;
            case 12:
                $url = 'qq/wap';//QQ钱包wap
                break;
            case 17:
                $url = 'union/native';//银联扫码
                break;
            case 25:
                $url = 'union/quick';//快捷
                break;    
            default:
                $url = 'wx/native';
                break;
        }
        //构造实际支付网关地址(包含token信息)
        $pay_url = $base_url . "{$url}?token={$token}";
        return $pay_url;
    }

    /**
     * 获取支付网关地址 用户登录token
     * @param array 支付参数
     * @return string token
     */
    private function getToken()
    {
        $data['appid'] = $this->merId;
        $data['secretid'] = $this->key;
        //用户登录获取token的登录地址
        $url = "http://api.xinyuzhifu.com/auth/access-token";
        $return = post_pay_data($url,$data);
        if (empty($return)) $this->retMsg('商户登录接口获取信息失败！');
        //接收参数为XML格式 转化为数组
        $return = FromXml($return);
        if (empty($return['token'])) 
        {
            $this->retMsg('商户登录接口获取token失败！');
        }
        return $return['token'];
    }
    
    
    /**
     * @param $data 支付参数
     * @return return  二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为XML格式 将数组转化成XML格式
        $xml = ToXml($pay_data);
        $data = post_pay_data($this->url,$xml,'xml');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为XML格式 转化为数组
        $data = FromXml($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['result_code']) || ('0' <> $data['result_code']) 
           || (empty($data['pay_info']) && empty($data['code_url'])))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败: {$msg}");
        }
        //扫码支付返回 二维码地址 wap支付返回支付地址
        if (!empty($data['code_url']))
        {
            $pay_url = $data['code_url']; //二维码地址
        } else { 
            $pay_url = $data['pay_info']; //wap支付地址
        }
        return $pay_url;
    }
}
