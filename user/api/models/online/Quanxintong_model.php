<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Quanxintong_model extends Publicpay_model
{
    protected $c_name = 'Quanxintong';
    private $p_name = 'QUANXINTONG';//商品名称A

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
        //构造签名参数
        $key = $this->key;
        $data['SIGN'] = $this->getSign($data,$key);
        $data['BG_URL'] = $this->callback;
        $data['PAGE_URL'] = $this->returnUrl;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['ORDER_ID'] = 'ID'.$this->orderNum;
        $data['ORDER_AMT'] = $this->money;
        $data['USER_ID'] = $this->merId;
        $data['BUS_CODE'] = $this->getPayType();
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
                return '3105';//微信Wap/h5
                break;
            case 4:
            case 5:
                return '3201';//支付宝WAP
                break;
            default:
                return '3201';//支付宝扫码
                break;
        }
    }

    protected function getSign($data,$key){
        $signStr = '';
        foreach ($data as $v){
            $signStr .= $v;
        }
        $signStrMd5 = md5($signStr);
        $sign = md5($signStrMd5.$key);
        $sign = substr($sign,8,16);
        return $sign;
    }
}
