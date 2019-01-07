<?php
/**
 * 优付支付接口调用
 * User: lqh
 * Date: 2018/08/14
 * Time: 17:00
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Youfu_model extends Publicpay_model
{
    protected $c_name = 'youfu';
    private $p_name = 'YOUFU';//商品名称

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
        $string = ToUrlParams($data) . $this->key;
        $data['mark'] = $this->p_name;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['customerid'] = $this->merId;
        $data['sdcustomno'] = $this->orderNum;
        $data['orderAmount'] = yuan_to_fen($this->money);
        $data['cardno'] = $this->getPayType();
        $data['noticeurl'] = $this->callback;
        $data['backurl'] = $this->returnUrl;
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
                return '32';//微信扫码
                break;
            case 2:
                return '41';//微信WAP
                break;
            case 4:
                return '42';//支付宝扫码
                break; 
            case 5:
                return '44';//支付宝WAP
                break;
            case 8:
                return '36';//QQ扫码
                break;
            case 12:
                return '45';//QQWAP
                break;
            default:
                return '42';//支付宝扫码
                break;
        }
    }
}
