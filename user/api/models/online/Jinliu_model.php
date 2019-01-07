<?php
/**
 * 金流支付接口调用
 * User: lqh
 * Date: 2018/08/12
 * Time: 15:50
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jinliu_model extends Publicpay_model
{
    protected $c_name = 'jinliu';
    private $p_name = 'JINLIU';//商品名称
    //支付接口签名参数 
    private $key_string = '&key='; //参与签名组成

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
        $string = implode("",array_values($data)) . $this->key;
        $data['fxpay'] = $this->getPayType();
        $data['fxdesc'] = $this->p_name;
        $data['fxattch'] = $this->p_name;
        $data['fxbackurl'] = $this->returnUrl;
        $data['fxip'] = get_ip();
        $data['fxbankcode'] = $this->bank_type;
        $data['fxsign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['fxid'] = $this->merId;
        $data['fxddh'] = $this->orderNum;
        $data['fxfee'] = $this->money;
        $data['fxnotifyurl'] = $this->callback;
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
                return 'alipay';//支付宝扫码、WAP
                break; 
            default:
                return 'alipay';//支付宝扫码
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
        if (empty($data['payurl']))
        {
            $msg = isset($data['error']) ? $data['error'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['payurl'];
    }
}
