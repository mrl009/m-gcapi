<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/16
 * Time: 9:59
 */
include_once __DIR__.'/Publicpay_model.php';

class Ai_model extends Publicpay_model
{
    protected $c_name = 'ai';
    private $p_name = 'AI';//商品名称
    private $key_string = '&key=';

    public function __construct(){
        parent::__construct();
    }

    protected function returnApiData($data){
        if (in_array($this->code,$this->scan_code)){
            return $this->buildScan($data);
        }else{
            return $this->buildForm($data);
        }
    }
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $string = ToUrlParams($data).$this->key_string.$this->key;
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['version'] = 'v1'; //接口版本号
        $data['merchant_no'] = $this->merId;//商户号
        $data['order_no'] = $this->orderNum;//订单号
        $data['goods_name'] = base64_encode($this->p_name);//商品名称
        $data['order_amount'] = $this->money;//订单金额
        $data['backend_url'] = $this->callback;//回调地址
        $data['frontend_url'] = $this->returnUrl;//可选
        $data['reserve'] = $this->p_name;//可选
        $data['pay_mode'] =$this->getPayType() ;//支付模式
        $data['bank_code'] =$this->getBankCode() ;//银行编号
        $data['card_type'] =$this->getPayType() == '07' ? 0 : 2;//银行卡类型
        if ($data['bank_code'] == 'QUICKPAY') $data['merchant_user_id'] = $this->user['id'];
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType(){
        if (in_array($this->code,$this->short_code)){
            return '07';
        }elseif (in_array($this->code,$this->wap_code)){
            return '12';
        }else{
            return '09';
        }
    }

    private function getBankCode(){
        switch ($this->code){
            case 1:
                return 'WECHAT';//微信扫码
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 8:
                return 'QQSCAN';//QQ扫码
                break;
            case 9:
                return 'JDSCAN';//京东扫码
                break;
            case 17:
                return 'UNIONPAY';//银联扫码
                break;
            case 2:
                return 'WECHATWAP';//微信H5
                break;
            case 5:
                return 'ALIPAYWAP';//支付宝H5
                break;
            case 12:
                return 'QQWAP';//QQ H5
                break;
            case 36:
                return 'ALIPAYCODE';//支付宝付款码
                break;
            case 18:
                return 'UNIONPAYWAP';//银联H5
                break;
            case 25:
                return 'QUICKPAY';//快捷支付
                break;
            default:
                return 'WECHAT';
                break;
        }

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['code_url']) || $data['result_code'] <> '00'){
            $msg = isset($data['result_msg']) ? $data['result_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['code_url'];
        return $pay_url;
    }
}