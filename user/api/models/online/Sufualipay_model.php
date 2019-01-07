<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/13
 * Time: 16:58
 */
include_once __DIR__.'/Publicpay_model.php';

class Sufualipay_model extends Publicpay_model
{
    protected $c_name = 'sufualipay';
    private $p_name = 'SUFUALIPAY';//商品名称
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
        return $data;
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getBaseData()
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $bankType = isset($pay_data['bank_type']) ? $pay_data['bank_type'] : 0;
        // 请求数据赋值
        $data['notify_url'] =$this->callback;// 回调
        $data['return_url'] = $this->returnUrl;
        $data['pay_type'] = $this->getType();//支付方式
        $data['merchant_code'] = $this->merId;//商户订单号
        $data['order_no'] =  $this->orderNum;//订单号
        $data['order_amount'] = (string)$this->money;// 订单金额 元
        $data['order_time'] = date('Y-m-d H:i:s');//订单提交时间
        $data['req_referer'] = $this->domain;
        $data['customer_ip'] = get_ip();
        $data['sign'] = $this->sign($data);
        return $data;
    }

    private function getType()
    {
        switch ($this->code) {
             case 4:
                 return '3';//支付宝扫码
                 break;
             case 5:
                 return '3';//支付宝wap
                 break;
             case 36:
                 return '3';//支付宝h5
                 break;
            default:
                return '3';
            
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        ksort($data);
        $arg = "";
        foreach ($data as $k => $v) {
            if ($k == 'sign' || $k == 'signType' || $v == '') {
                continue;
            }
            $arg .= $k . "=" . $v . "&";
        }
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return md5($arg .'key='.$this->key);
    }


}