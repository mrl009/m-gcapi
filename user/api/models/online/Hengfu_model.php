<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Hengfu_model extends Publicpay_model
{
    protected $c_name = 'Hengfu';
    private $p_name = 'HENGFUTONG';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = ''; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

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
        $signStr = $data['merchantId'].'pay'.$data['totalAmount'].$data['corp_flow_no'].$this->key;
        $data['sign'] = md5($signStr);
        //构造签名参数
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchantId'] = $this->merId;
        $data['type'] = $this->getPayType();
        $data['totalAmount'] = $this->money;
        $data['corp_flow_no'] = $this->orderNum;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['desc'] = $this->p_name;
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
                return 2;//支付宝
                break;
            default:
                return 2;//支付宝
                break;
        }
    }
}
