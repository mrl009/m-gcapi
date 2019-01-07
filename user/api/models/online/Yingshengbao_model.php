<?php
/**
 * 盈生宝支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yingshengbao_model extends Publicpay_model
{
    protected $c_name = 'yingshengbao';
    private $p_name = 'YSB';//商品名称

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
        $string = implode('',array_values($data));
        $string = md5(md5($string) . $this->key);
        $data['BG_URL'] = $this->callback;
        $data['PAGE_URL'] = $this->returnUrl; 
        $data['SIGN'] = substr($string,8,16);
        return $data;
    }
    
    /**
     * 构造支付基本参数(业务数据)
     * @return array
     */
    private function getBaseData()
    {
        $data['ORDER_ID'] = $this->orderNum;
        $data['ORDER_AMT'] = $this->money;
        $data['USER_ID'] = $this->merId;
        $data['BUS_CODE'] = $this->getPayType();
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
                return '3105';//微信扫码
                break;   
            case 4:
            case 5:
                return '3201';//支付宝扫码
                break;
            default:
                return '3201';//支付宝扫码
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
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //确认下单是否成功
        if (empty($data['result']['QRCODE']))
        {
            $msg = isset($data['desc']) ? $data['desc'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['result']['QRCODE'];
    }
}
