<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/27
 * Time: 22:23
 */
include_once __DIR__.'/Publicpay_model.php';
class Renrenqianbao_model extends Publicpay_model
{
    protected $c_name = 'renrenqianbao';
    private $p_name = 'RENRENQIANBAO';//商品名称

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
                return '1';//微信
                break;
            case 2:
                return '4';//微信app
                break;
            case 4:
                return '5';//支付宝
                break;
            case 5:
                return '5';//支付宝app
                break;
            default :
                return '5';
                break;
        }
    }
}