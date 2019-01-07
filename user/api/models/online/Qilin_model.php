<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 麒麟支付
 * 大犀牛
 * 2018.11.22
 */
include_once __DIR__.'/Publicpay_model.php';

class Qilin_model extends Publicpay_model
{
    protected $c_name = 'qilin';
    private $p_name = 'QILIN';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名
    //private $key   = '';//密钥

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
        $data['return_type']  = 'html';//格式json
        $data['api_code']     = $this->merId;//商户号
        $data['is_type']      = $this->getPayType();
        $data['order_id']     = $this->orderNum;
        $data['price']        = $this->money;
        $data['time']         = time();//支付时间服务器当前时间
        $data['mark']         = 'QLpay';//购买的描述
        $data['notify_url']   = $this->callback;//通知异步回调接收地址
        $data['return_url']   = $this->returnUrl;//成功后返回的跳转地址
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
                return 'wechat';//微信扫码
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay_wap';//支付宝WAP
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }

    protected function getPayUrl($pay)
    {
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $pay_url = trim($pay['pay_url']).'/channel/Common/mail_interface';
        }
        return $pay_url;
    }

}