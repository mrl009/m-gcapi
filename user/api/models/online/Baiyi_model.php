<?php
/**
 * 佰亿支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Baiyi_model extends Publicpay_model
{
    protected $c_name = 'baiyi';
    protected $p_name = 'BAIYI';
    //支付接口签名参数
    private $ks = '&key='; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        ksort($data);
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['merId'] = $this->merId;//商户号
        $data['appId'] = $this->s_num;//应用ID
        $data['orderNo'] = $this->orderNum;
        $data['totalFee'] = $this->money;
        $data['channeltype'] = $this->getPayType();
        $data['returnUrl'] = $this->callback;
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
            case 4:
            case 5:
                return '1';//支付宝
                break;
            default:
                return '1';
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrcode']))
        {
            $msg = isset($data['Message']) ? $data['Message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['qrcode'];
    }
}