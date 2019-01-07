<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/31
 * Time: 15:51
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Zhong_model extends  Publicpay_model
{
    protected $c_name = 'zhong';
    private $p_name = 'ZF';//商品名称

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
        $string = data_to_string($data);
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
        $data['sign'] = $this->key;
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
                return '2';//微信
                break;
            case 2:
                return '2';//微信app
                break;
            case 4:
                return '1';//支付宝
                break;
            case 5:
                return '1';//支付宝app
                break;
            default :
                return '2';
                break;
        }
    }
}