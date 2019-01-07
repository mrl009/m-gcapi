<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Huifeng_model extends Publicpay_model
{
    protected $c_name = 'Huifeng';

    public function __construct()
    {
        parent::__construct();
    }

    protected function returnApiData($data)
    {
        //把参数按照地址的形式拼接出来
        $parameter  = http_build_query($data);
        $pay_url = $this->url . '?' . $parameter;
        $res = [
            'jump' => 5,
            'url' => $pay_url
        ];
        return $res;
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
        $data['sign'] = $this->getSign($data);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['uid'] = $this->merId;
        $data['price'] = $this->money;
        $data['order_id'] = $this->orderNum;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        return $data;
    }

    protected function getSign($data){
        $signStr = '';
        foreach ($data as $v){
            $signStr.=$v;
        }
        $signStr.=$this->key;
        return $sign = md5($signStr);
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
                return 'WX';//微信Wap/h5
                break;
            case 4:
            case 5:
                return 'ZFB';//支付宝WAP
                break;
            case 8:
            case 12:
                return 'QQ';//QQ扫码
                break;
            default:
                return 'ZFB';//支付宝扫码
                break;
        }
    }
}
