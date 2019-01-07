<?php

/**
 * 易汇支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/18
 * Time: 16:47
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Yihui_model extends Publicpay_model
{
    protected $c_name = 'yihui';
    private $p_name = 'YIHUI';//商品名称
    private $key_string = '&key=';

    public function __construct(){
        parent::__construct();
    }

    protected function returnApiData($data){
        return $this->buildForm($data);
    }
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data).$this->key_string.$this->key;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['version'] = '1.8'; //接口版本号
        $data['merchantId'] = $this->merId;//商户号
        $data['orderId'] = $this->orderNum;//订单号
        $data['goodsName'] = base64_encode($this->p_name);//商品名称
        //$data['payType'] =$this->getPayType() ;//支付模式
        if(in_array($this->code,[1,2])){
            if(!in_array($this->money,[100,200,300,400,500])){
                return $this->retMsg('请支付金额100,200,300,400,500');
            }
        }
        $data['amount'] = $this->money;//订单金额
        $data['notifyUrl'] = $this->callback;//回调地址
        $data['returnUrl'] = $this->returnUrl;//可选
        /*if($this->code==7){
            $data['bankCode'] =$this->bank_type ;//银行编号
        }*/
        $data['signType'] = 'MD5';
        return $data;
    }

    private function getPayType(){
        switch ($this->code){
            case 1:
                return 'wxscan';//微信扫码
                break;
            case 2:
                return 'wxh5';//微信扫码
                break;
            case 4:
                return 'aliscan';//支付宝扫码
                break;
            case 5:
                return 'alih5';//支付宝扫码
                break;
            case 7:
                return 'b2c';//网银
                break;
            case 8:
                return 'qqscan';//QQ扫码
                break;
            case 9:
                return 'jdscan';//京东扫码
                break;
            case 10:
                return 'bdscan';//百度扫码
                break;
            case 17:
                return 'upscan';//银联扫码
                break;
            case 12:
                return 'qqh5';//QQ H5
                break;
            case 13:
                return 'jdh5';//京东H5
                break;
            case 25:
                return 'kj';//快捷支付
                break;
            default:
                return 'aliscan';
                break;
        }

    }
}