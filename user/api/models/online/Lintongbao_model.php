<?php
/**
 * 灵通宝支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/23
 * Time: 11:07
 */
defined('BASEPATH')or exit('No direct script access allowed');
include_once  __DIR__.'/Publicpay_model.php';
class Lintongbao_model extends Publicpay_model
{
    protected $c_name = 'lintongbao';
    private $p_name = 'LINTONGBAO';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
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
     * 获取前端返回的数据
     */
    public function returnApiData($data){
        return $this->buildForm($data);
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['user_account'] = $this->merId;//商户号
        $data['out_trade_no'] = $this->orderNum;
        $data['payment_type'] = $this->getPayType();//获取不同的支付方式
        $data['body'] = $this->p_name;
        $data['total_fee'] = $this->money;
        $data['trade_time'] = date('Y-m-d H:i:s');//订单生成时间
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
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
                return 'wxpay';//微信扫码
                break;
            case 2:
                return 'wxwap';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'aliwap';//支付宝WAP
                break;
            case 8:
                return 'qqpay';//QQ钱包扫码
                break;
            case 12:
                return 'qqwap';//QQWAP
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}