<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Fenghuang_model extends Publicpay_model
{
    protected $c_name = 'Fenghuang';
    private $p_name = 'FENGHUANG';//商品名称A
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = ''; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

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
        $data['extend'] = $this->p_name;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['app_id'] = $this->merId;
        $data['pay_type'] = $this->getPayType();
        if ($this->code == 7){
            $data['pay_type'] = $this->bank_type;
        }
        $data['amount'] = yuan_to_fen($this->money);
        $data['order_id'] = $this->orderNum;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
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
                return 'wechat';//微信扫码
                break;
            case 2:
                return 'wechatwap';//微信Wap/h5
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return '907';//网银支付
                break;
            case 8:
                return 'qqpay';//QQ扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqpaywap';//QQ
                break;
            case 13:
                return 'jdpaywap';//京东钱包wap
                break;
            case 22:
                return 'tenpay';//财付通
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}
