<?php
/**
 * 立刻付支付接口调用 (更新)
 * User: lqh
 * Date: 2018/07/31
 * Time: 16:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Likefu_model extends Publicpay_model
{
    protected $c_name = 'likefu';
    private $p_name = 'LIKEFU';//商品名称

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
        return $this->buildForm($data,'GET');
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
        $data['sign'] = md5($string);
        $data['hrefbackurl'] = $this->returnUrl;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
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
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return 'MSWEIXIN';//微信扫码
                break;
            case 2:
                return 'MSWEIXINWAP';//微信wap
                break;
            case 4:
                return 'MSAli';//支付宝扫码
                break;
            case 5:
                return 'ALIWAPPAY';//支付宝wap
                break;    
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return 'MSTENPAY';//qq钱包
                break;
            case 9:
                return 'MSJD'; //京东钱包
                break;    
            case 12:
                return 'MSTENWAP';//QQwap
                break;    
            case 13:
                return 'MSJDWAP';//京东wap
                break;
            case 17:
                return 'MSUNIONPAY';//银联钱包
                break;    
            case 18:
                return 'MSUNIONPAYWAP';//银联WAP
                break;    
            case 25:
                return 'BANKH5';//快捷WAP
                break;    
            case 40:
                return 'MSWXREVERSE';//微信条码反扫
                break;    
            case 41:
                return 'MSAlIREVERSE';//支付宝条码反扫
                break;
            default:
                return 'MSAli';//支付宝扫码
                break;
        }
    }
}
