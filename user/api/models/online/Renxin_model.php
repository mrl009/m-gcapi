<?php
/**
 * 仁信支付接口调用 修改版
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Renxin_model extends Publicpay_model
{
    protected $c_name = 'renxin';
    private $p_name = 'RENXIN';//商品名称

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
        return $this->buildForm($data,'get');
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
        $data['goodsname'] = $this->p_name;
        $data['isshow'] = 1;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['version'] = '3.0'; //接口版本号
        $data['method'] = 'Rx.online.pay';//接口名称
        $data['partner'] = $this->merId;//商户号
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
                return 'WEIXIN';//微信
                break;
            case 2:
                return 'WEIXINWAP';//微信app
                break;
            case 4:
                return 'ALIPAY';//支付宝
                break;
            case 5:
                return 'ALIPAYWAP';//支付宝app
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return 'QQ';//qq钱包
                break;
            case 9:
                return 'JD'; //京东钱包
                break;
            case 12 :
                return 'QQWAP';
                break;
            case 13 :
                return 'JDWAP';
                break;
            case 17 :
                return 'UNIONPAY';//财付通
                break;
            case 18 :
                return 'UNIONPAYWAP';
                break;
            case 22 :
                return 'TENPAY';//财付通
                break;
            case 23 :
                return 'TENPAYWAP';
                break;
            case 25 :
                return 'QUICK';
                break;
            case 40 :
                return 'WEIXINCODE';//微信条码
                break;
            case 41 :
                return 'ALICODE';//支付宝条码
                break;
            default :
                return 'WEIXIN';
                break;
        }
    }
}


