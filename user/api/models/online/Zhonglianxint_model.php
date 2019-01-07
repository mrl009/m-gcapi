<?php

/**
 * 众联信托支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/25
 * Time: 19:25
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay_model.php';
class Zhonglianxint_model extends Publicpay_model
{
    //redis 错误记录
    protected $c_name = 'zhonglianxint';
    private $p_name = 'ZLXT';//商品名称
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
                return '991';//微信扫码
                break;
            case 2:
                return '991';//微信WAP
                break;
            case 4:
                return '992';//支付宝扫码
                break;
            case 5:
                return '992';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网关支付
                break;
            default:
                return '992';
                break;
        }
    }
}