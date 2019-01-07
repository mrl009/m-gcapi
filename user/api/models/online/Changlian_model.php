<?php

/**
 * 畅连云支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/9
 * Time: 17:41
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Changlian_model extends Publicpay_model
{
    protected $c_name = 'changlian';
    private $p_name = 'CHANGLIAN';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        if($this->code == 7){
            $data['bkcode']  = $this->bank_type;
        }
        $data['pay_productname'] = $this->p_name;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;
        $data['pay_orderid'] = $this->orderNum;
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
        $data['pay_amount'] = $this->money;
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

            case 4:
            case 5:
                return '914';//支付宝扫码和WAP
                break;
            case 7:
                return '913';//网银
            case 9:
                return '915';//京东支付
                break;
            default:
                return '914';//支付宝扫码
                break;
        }
    }
}