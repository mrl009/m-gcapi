<?php
/**
 * 乐联盟支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/9
 * Time: 14:29
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Lelianmeng_model extends Publicpay_model
{
    protected $c_name = 'lelianmeng';
    private $p_name = 'LELIANMENG';//商品名称
    //支付接口签名参数
    private $ks = '&'; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }

    protected function returnApiData($data){
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
        $k = $this->ks . $this->key;
        ksort($data);
        $string = data_to_string($data) . $k;
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        //网银参数
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
        $data['paytype'] = $this->getPayType();
        $data['status'] = '';
        $data['sdpayno'] = '';
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
            case 2:
                return 'weixin';
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银直连
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}