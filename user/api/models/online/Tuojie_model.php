<?php
/**
 * 拓界支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tuojie_model extends Publicpay_model
{
    protected $c_name = 'tuojie';
    private $p_name = 'TUOJIE';//商品名称
    //支付接口签名参数 
    private $key_string = '&key='; //参与签名组成

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
        ksort($data);
        $k = $this->key_string . $this->key;
        $string = data_to_string($data,'=>') . $k;
        $data['pay_md5sign'] = strtoupper(md5($string));
        $data['pay_productname'] = $this->p_name;
        $data['tongdao'] = $this->getPayType();
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;
        $data['pay_orderid'] = $this->orderNum;
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
        $data['pay_amount'] = $this->money;
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
                return '901';//支付宝扫码
                break; 
            case 5:
                return '902';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            default:
                return '901';//支付宝扫码
                break;
        }
    }
}
