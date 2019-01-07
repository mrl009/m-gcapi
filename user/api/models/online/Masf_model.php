<?php

/**
 * 马上付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/6
 * Time: 18:59
 */
defined('BASEPATH') or exit('No direct script access allowed');
//公共文件调用
include_once __DIR__.'/Publicpay_model.php';
class Masf_model extends Publicpay_model
{
    protected $c_name = 'masf';
    private $p_name = 'MSF';//商品名称
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data).$k;
        $data[$this->field] = md5($string);
        $data['device_info'] = '0';//设备号
        $data['client_ip'] = get_ip();
        $data['body'] = $this->c_name;
        $data['fee_type'] = 'CNY';//人民币
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
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            $data['pay_type'] = $this->getPayType();//支付类型，固定值 106-企业 206-个人
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            $data['pay_type'] = '101';//支付类型，固定值 101-企业 201-个人
            //网银支付快捷支付和收银台 (部分接口不通用)
        }else{
            $data['pay_type'] = '108';//支付类型 ，固定值 108
        }

        $data['total_amount'] =yuan_to_fen($this->money);//单位分
        $data['out_trade_no'] = $this->orderNum;
        $data['notify_url'] = $this->callback;
        if(in_array($this->code,[7])){
        $data['bank_card_type']='00';//支付银行卡类型（00：B2C 借贷记综合01：B2C 纯借记03：B2B 企业网银）
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
        $payUrl = '';
        if (!empty($pay['pay_url']))
        {
            $payUrl = trim($pay['pay_url']);
        }

        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            $payUrl .= '/h5pay/v2';
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            $payUrl .= '/h5pay/v2';//扫码下单
            //网银支付快捷支付和收银台 (部分接口不通用)
        }else{
            $payUrl .= '/bankpay';
        }
        return $payUrl;
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
                return '101';//支付宝扫码
                break;
            case 5:
                return '106';//支付宝WAP
                break;
            case 28:
                return '206';//支付宝wap
                break;
            case 36:
                return '201';//支付宝扫码
                break;
            default:
                return '201';//支付宝扫码
                break;
        }
    }
}