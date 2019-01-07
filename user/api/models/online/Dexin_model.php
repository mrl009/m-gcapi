<?php

/**
 * 德兴支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/28
 * Time: 11:40
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay_model.php';
class Dexin_model extends Publicpay_model
{
//redis 错误记录
    protected $c_name = 'dexin';
    private $p_name = 'DEXIN';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        $string = ToUrlParams($data).$k;
        $data['sign'] = md5($string);
        $data['hrefbackurl'] = $this->returnUrl;
        $data['payerIp'] = get_ip();//客户ip
        $data['attach'] = date('Y-m-d H:i:s',time());//自定发起时间
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();//银行的类型
        $data['value'] = $this->money;//金额
        $data['orderid'] = $this->orderNum;//订单号 唯一
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
                return '1007';//微信WAP
                break;
            case 4:
                return '992';//支付宝扫码
                break;
            case 5:
                return '1006';//支付宝WAP
                break;
            case 7:
                return '1015';//网关支付
                break;
            case 8:
                return '1013';//qq财富通
                break;
            case 9:
                return '1008';//京东钱包
                break;
            case 12:
                return '1014';//qqh5
                break;
            case 13:
                return '1010';//京东wap
                break;
            case 17:
                return '1000';//银联扫码
                break;
            case 25:
                return '1012';//快捷支付 1016人脸识别
                break;
            case 33:
                return '1009';//微信公众号
                break;
            default:
                return '992';
                break;
        }
    }
}