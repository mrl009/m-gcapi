<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Chuangyi_model extends Publicpay_model
{
    protected $c_name = 'chuangyi';
    private $p_name = 'CHUANGYI';//商品名称A

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
        $key = $this->key;
        //构造签名参数
        $data['sign'] = $this->getSign($data,$key);
        $data['type'] = $this->getPayType();
        $data['webname'] = $this->p_name;
        $data['subject'] = $this->p_name;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['appid'] = $this->merId;
        $data['out_trade_no'] = $this->orderNum;
        $data['total_fee'] = $this->money;
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
                return 3;//微信Wap/h5
                break;
            case 4:
            case 5:
                return 1;//支付宝WAP
                break;
            case 8:
            case 12:
                return 2;//QQ扫码
                break;
            default:
                return 1;//支付宝扫码
                break;
        }
    }

    protected function getSign($data,$key){
        $signStr = $data['appid'].$key.$data['out_trade_no'].$data['total_fee'];
        return $sign = md5($signStr);
    }
}
