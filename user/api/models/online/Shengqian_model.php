
<?php

/**
 * 省钱支付支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/10/19
 * Time: 16:27
 */
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';
class Shengqian_model extends Publicpay_model
{
    protected $c_name = 'shengqian';
    private $p_name = 'SHENGQIAN';//
    //支付接口签名参数
    private $field = 'sign'; //签名参数名
    private $method = 'D'; //小写
    private $sk='&appkey=';//签名方式参数名

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
        $k = $this->sk.$this->key;
        $f = $this->field;
        $s = $this->method;
        $data = get_pay_sign($data,$k,$f,$s);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchantNo']    = $this->merId;// 商户在支付平台的的平台号
        $data['tradeNo']       = $this->orderNum;// 订单号
        $data['operationCode'] = 'order.createOrder';//下单
        $data['version']       = '1.0';
        $data['date']          = time();
        $data['amount']        = intval($this->money);// 金额
        $data['subject']       = $this->p_name;//商品名称
        $data['body']          = $this->c_name;//描述
        $data['paymentType']   = $this->getPayType();// 商户在支付平台支付方式
        $data['notifyUrl']     = $this->callback;// 商户通知地址
        $data['frontUrl']      = $this->returnUrl;
        $data['spbillCreateIp']= get_ip();
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
                return 'ALIPAY_QRCODE';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY_QRCODE';//支付宝H5
                break;
            default:
                return 'ALIPAY_QRCODE';
        }
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //判断下单是否成功
        if (!isset($data['payCode']) || (100 <> $data['code'])
        )
        {
            if (!empty($data['code'])) $msg = $data['code'];
            if (!empty($data['msg'])) $msg = $data['msg'];
            $msg = isset($msg) ? $msg : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['payCode'];//wap支付地址或者二维码地址
        return $pay_url;
    }
}