<?php

/**
 * 宜兴支付接口
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/24
 * Time: 11:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Yixing_model extends Publicpay_model
{
    protected $c_name = 'yixing';
    private $p_name = 'YIXING';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
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
        $k = $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        $data['notify_url'] = $this->callback;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['appid'] = $this->merId;
        $data['out_trade_no'] = $this->orderNum;
        $data['order_type'] = $this->webType();
        $data['goods_name'] = base64_encode($this->c_name);
        $data['pay_id'] = $this->getPayType();
        $data['total_fee'] = $this->money;
        $data['currency_type'] = 'CNY';
        $data['return_url'] = $this->returnUrl;
        $data['sign_type'] = 'MD5';
        return $data;
    }

    protected function webType(){
        switch ($this->from_way)
        {
            case 1:
            case 2:
                return '2';//1为网页支付，2为app支付
                break;
            default:
                return '1';//1为网页支付，2为app支付
                break;
        }
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
                return 'WECHAT';//微信扫码
                break;
            case 2:
                return 'WECHAT';//微信Wap/h5
                break;
            case 4:
                return 'ALIPAY_RANDOM';//支付宝扫码//order_type=1
                break;
            case 5:
                return 'ALIPAY_RANDOM';//支付宝WAPorder_type=2返回json
                break;
            default:
                return 'ALIPAY_RANDOM';//支付宝扫码
                break;
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payment']) || $data['resp_code'] <> '0000')
        {
            $msg = isset($data['resp_desc']) ? $data['resp_desc'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payment'];
    }
}