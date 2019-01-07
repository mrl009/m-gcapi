<?php
/**
 * 合付支付接口调用
 * User: lqh
 * Date: 2018/08/26
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Hefu_model extends Publicpay_model
{
    protected $c_name = 'hefu';
    private $p_name = 'HEFU';//商品名称
    //支付接口签名参数 
    private $key_string = '&sign='; //参与签名组成

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
        return $this->buildForm($data,'get');
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
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['merchant_id'] =$this->merId;//商户号
        $data['pay_method'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_id'] =$this->merId;//商户号
        $data['order_id'] = $this->orderNum;
        $data['amount'] = intval($this->money);
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
            case 2:
                return '4';//微信
                break;
            case 4:
            case 5:
                return '5';//支付宝
                break;
            default:
                return '5';//支付宝
                break;
        }
    }
}
