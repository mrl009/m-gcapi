<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/28
 * Time: 13:52
 */
include_once __DIR__.'/Publicpay_model.php';

class Bofubao_model extends Publicpay_model
{
    protected $c_name = 'bofubao';
    private $p_name = 'BOFUBAO';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

    protected function returnApiData($data)
    {
         if (in_array($this->code,[1,4,8,9,17])) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildForm($data);
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
        $data['service'] = 'pay';
        $data['version'] = '1.0';
        $data['mch_id'] = $this->merId;//商户号
        $data['out_order_no'] = $this->orderNum;
        $data['subject'] = $this->p_name;
        $data['total_fee'] = $this->money;
        $data['pay_type'] = $this->getPayType();
        if($this->code == 7) $data['open_bank_code'] = $this->bank_type;//网关支付
        $data['notify_url'] = $this->callback;//异步回调地址
        $data['clientip'] = get_ip();
        $data['return_url'] = $this->returnUrl;
        $data['nonce_str'] = substr(time(),2,8);
        return $data;
    }


    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //构造支付网关地址
        $base_url = $pay['pay_url'];
         if(in_array($this->code,[7]))
        {
             $url = 'formpay';//网银
        }else{
             $url = 'gateway';//扫码
        }
        return $base_url.$url ;
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
                return '1';//微信扫码1,2,3,6,11
                break;
            case 4:
                return '2';//支付宝扫码1,4,8,9,17
                break;
            case 7:
                return '4';//网银支付7,8,9,14,17
                break;
            case 8:
                return '3';//QQ扫码12,34,37
                break;
            case 12:
                return '9';//QQh5
                break;
            case 17:
                return '11';//银联钱包:跳出网页扫码支付
                break;
            case 34:
                return '7';//微信h5wap
                break;
            case 37:
                return '8';//支付宝h5wap
                break;
            case 9:
                return '6';//京东扫码
                break;
            default:
                return '1';//微信扫码
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['code_url'])|| 1<>$data['code_status'])
        {
            $msg = isset($data['code_status']) ? $data['code_status'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['code_url'];
    }
}