<?php
/**
 * 秒付支付接口调用
 * User: lqh
 * Date: 2018/06/20
 * Time: 13:40
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Miaofu_model extends Publicpay_model
{
    protected $c_name = 'miaofu';
    private $p_name = 'MIAOFU';//商品名称

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
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = md5($string);
        $data['pay_type'] = $this->getPayType();
        //网关参数
        if(7 == $this->code) $data['bank_type'] = $this->bank_type;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['body'] = $this->p_name;
        $data['notify_url'] = $this->callback;
        $data['out_order_no'] = $this->orderNum;
        $data['partner'] = $this->s_num;//终端id
        $data['return_url'] = $this->returnUrl;
        $data['subject'] = $this->p_name;
        $data['total_fee'] = $this->money;
        $data['user_seller'] = $this->merId;//商户号
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
                return 'wx';//微信扫码
                break; 
            case 2:
                return 'wxh5';//微信WAP
                break;   
            case 4:
                return 'zfb';//支付宝扫码
                break;
            case 5:
                return 'zfbh5';//支付宝WAP
                break; 
            case 7:
                return 'wangyin';//网关支付
                break;
            case 8:
                return 'qq';//QQ钱包扫码
                break;
            case 12:
                return 'qqh5';//QQ钱包WAP
                break;
            case 25:
                return 'kuaijie';//银联快捷
                break;
            default:
                return 'zfb';//微信扫码
                break;
        }
    }
}
