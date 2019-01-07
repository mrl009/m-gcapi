<?php
/**
 * 摩拜支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Mobai_model extends Publicpay_model
{
    protected $c_name = 'mobai';
    private $p_name = 'MB';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $ks = '&key='; //参与签名组成
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
        $k = $this->ks . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['partnerid'] = $this->merId;
        $data['orderid'] = $this->orderNum;
        $data['payamount'] = yuan_to_fen($this->money);
        $data['payip'] = get_ip();
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        $data['paytype'] = $this->getPayType();
        $data['remark'] = $this->p_name;
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
                return 'wcScan';//微信扫码
                break;   
            case 2:
                return 'wcWap';//微信WAP
                break;
            case 4:
                return 'alScan';//支付宝扫码
                break;
            case 5:
                return 'alWap';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return 'wcWScan';//QQ扫码
                break;
            case 9:
                return 'jdWScan';//京东扫码
                break;
            case 12:
                return 'QQWap';//QQWAP
                break;
            case 13:
                return 'jdWap';//京东WAP
                break;    
            case 17:
                return 'unpScan';//银联扫码
                break;
            case 18:
                return 'unpWap';//银联WAP
                break;
            case 25:
                return 'unpQuick';//快捷
                break;
            case 40:
                return 'wcRScan';//微信反扫
                break; 
            case 41:
                return 'alRScan';//支付宝反扫
                break;
            default:
                return 'alScan';//支付宝扫码
                break;
        }
    }
}
