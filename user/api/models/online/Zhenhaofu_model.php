<?php
/**
 * 真好付支付接口调用
 * User: lqh
 * Date: 2018/08/07
 * Time: 09:03
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhenhaofu_model extends Publicpay_model
{
    protected $c_name = 'zhenhaofu';
    private $p_name = 'ZHENHAOFU';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
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
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['nonceStr'] = create_guid();
        $data['startTime'] = date("YmdHis");
        $data['merchantNo'] = $this->merId;//商户号
        $data['outOrderNo'] = $this->orderNum;
        $data['amount'] = yuan_to_fen($this->money);
        $data['client_ip'] = get_ip();
        $data['timestamp'] = time();
        $data['productCode'] = $this->getPayType();
        $data['description'] = $this->p_name;
        $data['extra'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        if (7 == $this->code) $data['bankCode'] = $this->bank_type;
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
                return '1101';//微信扫码
                break;   
            case 2:
                return '1102';//微信WAP
                break;
            case 4:
                return '1201';//支付宝扫码
                break;
            case 5:
                return '1202';//支付宝WAP
                break; 
            case 7:
                return '1601';//网银支付
                break;
            case 8:
                return '1302';//QQ扫码
                break;
            case 9:
                return '1402';//京东钱包
                break;
            case 10:
                return '1901';//百度钱包
                break;
            case 12:
                return '1301';//QQwap
                break; 
            case 13:
                return '1401';//京东wap
                break; 
            case 17:
                return '1701';//银联钱包
                break;
            case 20:
                return '1902';//百度钱包wap
                break;
            case 25:
                return '1501';//快捷
                break;
            default:
                return '1201';//微信扫码
                break;
        }
    }
}
