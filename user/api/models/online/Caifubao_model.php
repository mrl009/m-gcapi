<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 財富寶支付接口调用
 * User: lqh
 * Date: 2018/07/22
 * Time: 14:02
 */
include_once __DIR__.'/Publicpay_model.php';

class Caifubao_model extends Publicpay_model
{
    protected $c_name = 'caifubao';
    private $p_name = 'CAIFUBAO';//商品名称
    //支付接口签名参数 
    private $key_string = '&'; //参与签名组成

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
        $string = implode('',array_values($data));
        $string .= $this->key_string . $this->key;
        $data['version'] = '1.0';
        $data['uid'] = $this->user['id'];
        $data['ptype'] = $this->getPayType();
        //网银参数
        if (7 == $this->code) $data['bankcode'] = $this->bank_type;
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pid'] = $this->merId;//商户号
        $data['oid'] = $this->orderNum;//订单号 唯一
        $data['notify'] = $this->callback;
        $data['callBack'] = $this->returnUrl;
        $data['money'] = $this->money;//金额
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
                return 'SP0021';//微信扫码
                break;
            case 2:
                return 'SP0020';//微信WAP
                break;
            case 4:
                return 'SP0008';//支付宝扫码
                break;
            case 5:
                return 'SP0009';//支付宝WAP
                break;
            default:
                return 'SP0021';//微信扫码
                break;
        }
    }
}
