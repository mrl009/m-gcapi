<?php
/**
 * UPAY支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Upay_model extends Publicpay_model
{
    protected $c_name = 'upay';
    protected $p_name = 'UPAY';
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
        //java时间戳格式为13位，php进行末尾补位
        $time = (string)(time() . '000');
        $money = (string)$this->money;
        $data['payType'] = $this->getPayType();
        $data['totalAmount'] = $money;
        $data['outTradeNo'] = $this->orderNum;
        $data['merchantNumber'] = $this->merId;
        $data['subject'] = $this->p_name;
        $data['body'] = $this->p_name;
        $data['timeStamp'] = $time;
        $data['notifyUrl'] = $this->callback;
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
            case 4:
            case 5:
                return 'AliPay';//支付宝
                break;
            default:
                return 'AliPay';
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为JSON格式数据
        $pay_data = json_encode($pay_data,320);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['qrCodeAddress']))
        {
            $msg = isset($data['info']) ? $data['info'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付连接或二维码地址
        return $data['data']['qrCodeAddress'];
    }
}