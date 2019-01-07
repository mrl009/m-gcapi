<?php
/**
 * E宝支付接口调用
 * User: lqh
 * Date: 2018/06/11
 * Time: 09:35
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Ebao_model extends Publicpay_model
{
    protected $c_name = 'ebao';
    private $p_name = 'EBAO';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

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
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['pay_memberid'] = $this->merId;//商户号
        $data['pay_orderid'] = $this->orderNum . $this->merId;
        $data['pay_amount'] = $this->money;
        $data['pay_applydate'] = date('Y-m-d H:i:s');
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_notifyurl'] = $this->callback;
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
                return 'WXZF';//微信扫码
                break;   
            case 2:
                return 'WXZF_H5';//微信WAP
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY_H5';//支付宝WAP
                break;  
            case 7:
                return 'GATEWAY';//网关支付
                break;
            case 8:
                return 'TENPAY';//QQ钱包扫码
                break;
            case 9:
                return 'JDPAY';//京东扫码
                break;
            case 10:
                return 'BAIDUPAY';//百度扫码
                break;
            case 12:
                return 'TENPAY_H5';//QQWAP
                break; 
            case 17:
                return 'UNIONQRPAY';//银联钱包
                break;
            case 25:
                return 'QUICKPAY';//银联快捷
                break;
            default:
                return 'WXZF';//微信扫码
                break;
        }
    }
}
