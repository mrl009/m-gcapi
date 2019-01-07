<?php
/**
 * 全时支付接口调用 修改版
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Quanshi_model extends Publicpay_model
{
    protected $c_name = 'quanshi';
    private $p_name = 'QUANSHI';//商品名称
    //支付接口签名参数 
    private $ks = '|'; //参与签名组成

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
        $k = $this->ks . $this->key;
        $string = implode('|',array_values($data)) . $k;
        $data['sha1key'] = sha1($string);
        $data['ccid'] = 100;
        $data['spare1'] = $this->bank_type;
        $data['spare2'] = $this->cardType;
        $data['spare3'] = $this->p_name;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['siteid'] = $this->merId;//商户号
        $data['paytypeid'] = $this->getPayType();
        $data['site_orderid'] = $this->orderNum;
        $data['paymoney'] = yuan_to_fen($this->money);
        $data['goodsname'] = $this->p_name;
        $data['client_ip'] = get_ip();
        $data['thereport_url'] = $this->callback;
        $data['thejump_url'] = $this->returnUrl;
        $data['nowinttime'] = time();
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
                return '11';//微信
                break;
            case 2:
                return '21';//微信app
                break;
            case 4:
                return '12';//支付宝
                break;
            case 5:
                return '22';//支付宝app
                break;
            case 7:
                return '46';//网银
                break;
            case 8:
                return '13';//qq钱包
                break;
            case 9:
                return '14'; //京东钱包
                break;
            case 12 :
                return '23';
                break;
            case 17 :
                return '15';//财付通
                break;
            case 18 :
                return '25';
                break;
            case 25 :
                return '35';
                break;
            default :
                return '12';
                break;
        }
    }

    /**
     * @param $data 支付参数
     * @return return  二维码内容
     */
    protected function getPayResult($pay_data)
    {
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['payinfo']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['data']['payinfo'];
    }
}