<?php

/**
 * 人人支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/17
 * Time: 13:39
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Renren_model extends Publicpay_model
{
    protected $c_name = 'renren';
    private $p_name   = 'RENREN';//商品名称
    //支付接口签名参数
    private $field = 'sign'; //签名参数名*/

    public function __construct()
    {
        parent::__construct();
    }

    protected function  returnApiData($data)
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $string = data_value($data);
        $data['sign'] = md5($string.$this->key);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['mid'] = $this->merId;
        $data['oid'] = $this->orderNum;
        $data['amt'] = $this->money;
        $data['way'] = $this->getPayType();
        $data['back'] = $this->returnUrl;
        $data['notify'] = $this->callback;
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
                return '1';//微信扫码
                break;
            case 2:
                return '3';//微信wap
                break;
            case 4:
                return '2';//支付宝扫码
                break;
            case 5:
                return '4';//支付宝wap
                break;
            default:
                return '2';//支付宝扫码
                break;
        }
    }
}