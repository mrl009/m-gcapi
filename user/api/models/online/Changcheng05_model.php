<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 长城05支付接口调用模型
 * User: 57207
 * Date: 2018/6/27
 * Time: 11:03
 */
include_once __DIR__.'/Publicpay_model.php';

class Changcheng05_model extends Publicpay_model
{
    protected $c_name = 'changcheng05';
    private $p_name = 'CHANGCHENG05';//商品名称
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
        $data['order_id']     = $this->orderNum;
        $data['price']        = $this->money;
        $data['is_type']      = $this->getPayType();
        $data['time']         = time();//支付时间服务器当前时间
        $data['mark']         = 'CCpay';//购买的描述
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
            case 2:
                return 'wechat13';//微信扫码
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            case 36:
                return 'talking';//支付宝
                break;
            case 7:
                return 'bankpay3';//网关支付
                break;
            default:
                return 'wechat13';//微信扫码
                break;
        }
    }

}