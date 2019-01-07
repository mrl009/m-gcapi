<?php
/**
 * M支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Mzhifu_model extends Publicpay_model
{
    protected $c_name = 'mzhifu';
    private $p_name = 'MZHIFU';//商品名称

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
        $string = data_to_string($data) . $this->key;
        $data['productName'] = $this->p_name;
        $data['productDesc'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['sign'] = strtoupper(MD5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['payType'] = 'syt';
        $data['partner'] = $this->merId;
        $data['orderId'] = $this->orderNum;
        $data['orderAmount'] = $this->money;
        $data['version'] = '1.0';
        $data['payMethod'] = $this->getPayType();
        $data['signType'] = 'MD5';
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
            case 5:
                return '22';//支付宝
                break;   
            default:
                return '22';//支付宝
                break;
        }
    }
}
