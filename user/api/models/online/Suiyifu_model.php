<?php
/**
 * 随意付支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/24
 * Time: 11:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';
class Suiyifu_model extends Publicpay_model
{
    protected $c_name = 'suiyifu';
    private $p_name = 'SUIYIFU';//商品名称

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
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();
        $data['value'] = $this->money;
        $data['orderid'] = $this->orderNum;
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
                return '1004';//微信扫码
                break;
            case 2:
                return '1100';//微信WAP
                break;
            case 4:
                return '992';//支付宝扫码
                break;
            case 5:
                return '1101';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '993';//QQ扫码
                break;
            case 9:
                return '1002';//京东扫码
                break;
            case 10:
                return '1003';//百度钱包
                break;
            case 12:
                return '1102';//QQwap
                break;
            case 13:
                return '1012';//京东wap
                break;
            case 17:
                return '1001';//银联扫码
                break;
            case 25:
                return '1008';//网银快捷
                break;
            case 27:
                return '1005';//网银WAP
                break;
            case 33:
                return '1007';//微信H5掃碼
                break;
            case 36:
                return '1006';//支付寶H5掃碼
                break;
            case 40:
                return '1010 ';//微信条码
                break;
            default:
                return '992';//支付宝pc扫码
                break;
        }
    }
}
