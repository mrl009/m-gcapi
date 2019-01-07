<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhihuibao_model extends Publicpay_model
{
    protected $c_name = 'zhihuibao';
    private $p_name = 'ZHIHUIBAO';//商品名称A
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成

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
        $k = $this->key_string . $this->key;
        $signStr = data_to_string($data).$k;
        $data['sign'] = md5($signStr);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['shid'] = $this->merId;
        $data['bb'] = '1.0';
        $data['zftd'] = $this->getPayType();
        $data['ddh'] = $this->orderNum;
        $data['je'] = $this->money;
        $data['ddmc'] = $this->p_name;
        $data['ddbz'] = $this->p_name;
        $data['ybtz'] = $this->callback;
        $data['tbtz'] = $this->returnUrl;
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
                return 'alipay';//支付宝WAP
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}
