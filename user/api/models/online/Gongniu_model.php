<?php
/**
 * 公牛支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Gongniu_model extends Publicpay_model
{
    protected $c_name = 'gongniu';
    private $p_name = 'GONGNIU';//商品名称

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
        $string = ToUrlParams($data);
        unset($data['merchant_md5']);
        $data['gateway'] = $this->getPayType();
        $data['urlcall'] = $this->callback;
        $data['urlback'] = $this->returnUrl;
        $data['merchant_sign'] = base64_encode(md5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant_code'] = $this->merId;
        $data['merchant_order_no'] = $this->orderNum;
        $data['merchant_goods'] = $this->p_name;
        $data['merchant_amount'] = $this->money;
        $data['merchant_md5'] = $this->key;
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
            case 4:
                return 'alipay';//支付宝
                break;
            case 5:
                return 'alipay_wap';//支付宝
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}
