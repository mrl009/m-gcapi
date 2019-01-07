<?php
/**
 * 天龙支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tianlong_model extends Publicpay_model
{
    protected $c_name = 'tianlong';
    private $p_name = 'TIANLONG';//商品名称

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
        $data['attach'] = $this->p_name;
        $data['hrefbackurl'] = $this->returnUrl;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '3.0';
        $data['method'] = 'Gt.online.interface';
        $data['partner'] = $this->merId;
        $data['banktype'] = $this->getPayType();
        $data['paymoney'] = $this->money;
        $data['ordernumber'] = $this->orderNum;
        $data['callbackurl'] = $this->callback;
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
                return 'WEIXIN';//微信扫码
                break;
            case 2:
                return 'WEIXINWAP';//微信WAP
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break; 
            case 5:
                return 'ALIPAYWAP';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JD';//京东扫码
                break;
            case 10:
                return 'BAIDU';//百度扫码
                break;
            case 12:
                return 'QQWAP';//QQwap
                break;
            case 13:
                return 'JDWAP';//京东WAP
                break;
            case 17:
                return 'UNIONPAY';//银联扫码
                break;
            case 18:
                return 'UNIONPAYWAP';//银联wap
                break;
            case 20:
                return 'BAIDUWAP';//百度扫码
                break;
            case 25:
                return 'BANK';//网银快捷PC
                break;
            case 27:
                return 'BANKWAP';//网银快捷WAP
                break;
            default:
                return 'ALIPAY';//支付宝扫码
                break;
        }
    }
}
