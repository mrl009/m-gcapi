<?php
/**
 * 千信支付接口调用
 * User: lqh
 * Date: 2018/05/29
 * Time: 16:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Qianxin_model extends Publicpay_model
{
    protected $c_name = 'qianxin';
    private $p_name = 'QIANXIN';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'sign'; //签名参数名

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
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data).$k;
        $data['paytype'] = $this->getPayType();
        if (7 == $this->code) 
        {
            $data['bankcode'] = $this->bank_type;
        }
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['customerid'] = $this->merId;//商户号
        $data['total_fee'] = $this->money;
        $data['sdorderno'] = $this->orderNum;
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
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
            case 1:
                return 'weixin';//微信扫码
                break;   
            case 2:
                return 'wxh5';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break; 
            case 5:
                return 'alipaywap';//支付宝WAP
                break;    
            case 7:
                return 'bank';//网关支付
                break;
            case 8:
                return 'qqrcode';//QQ钱包扫码
                break;
            case 9:
                return 'jingdong';//京东扫码
                break; 
            case 12:
                return 'qqwap';//QQ钱包WAP
                break;
            case 13:
                return 'jdwap';//京东钱包WAP
                break;   
            case 17:
                return 'unipaywall';//银联钱包
                break;
            case 18:
                return 'unipaywap';//银联WAP
                break;
            default:
                return '1000';//微信扫码
                break;
        }
    }
}