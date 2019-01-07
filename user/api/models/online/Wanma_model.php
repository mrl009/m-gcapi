<?php
/**
 * 万码支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Wanma_model extends Publicpay_model
{
    protected $c_name = 'wanma';
    private $p_name = 'WANMA';//商品名称
    private $ks = '&key='; //加密字符连接符

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
        $string = data_to_string($data) . $k;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $money = yuan_to_fen($this->money);
        $data['uid'] = $this->merId;
        $data['outTradeNo'] = $this->orderNum;
        $data['price'] = (string)$money;
        $data['type'] = $this->getPayType();
        $data['notifyUrl'] = $this->callback;
        $data['commdityName'] = $this->p_name;
        $data['nonceStr'] = md5(rand(0001,9999));
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
            case 2:
                return 'wechat';//微信
                break;
            case 4: 
            case 5:
                return 'alipay';//支付宝
                break;
            default:
                return 'alipay';//支付宝
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
        //传递参数为json类型数据
        $pay_data = json_encode($pay_data,320);
        $length = strlen($pay_data);
        //设置传递头部信息
        $header = array(
            "Content-Type: application/json; charset=utf-8",
            "Content-Length: {$length}"
        );
        $data = post_pay_data($this->url,$pay_data,$header);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //返回的data数据可能是一个"null"字符串 需要过滤
        if (!empty($data['data']) && ('null' <> $data['data'])) 
        {
            $data = json_decode($data['data'],true);
        }
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrcodeUrl']))
        {
            $msg = '返回信息错误';
            if (isset($data['errCodeDes'])) $msg = $data['errCodeDes'];
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['qrcodeUrl'];
    }
}
