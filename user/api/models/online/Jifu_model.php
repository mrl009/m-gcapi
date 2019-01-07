<?php
/**
 * 极付支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jifu_model extends Publicpay_model
{
    protected $c_name = 'jifu';
    private $p_name = 'JIFU';//商品名称

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
        $data['sign_type'] = 'MD5';
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pid'] = $this->merId;
        $data['type'] = $this->getPayType();
        $data['out_trade_no'] = $this->orderNum;
        $data['money'] = $this->money;
        $data['name'] = $this->p_name;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
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
                return 'wechat2';//微信
                break;
            case 4: 
            case 5:
                return 'alipay2';//支付宝
                break;
            case 8: 
            case 12:
                return 'qqpay2';//QQ钱包
                break;   
            default:
                return 'alipay2';//支付宝
                break;
        }
    }
}
