<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/29
 * Time: 14:37
 */
include_once __DIR__.'/Publicpay_model.php';

class Katong_model extends Publicpay_model
{
    protected $c_name = 'Katong';
    protected $p_name = 'KATONG';//商品名称
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
        return $this->buildWap($data);
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
        $money = yuan_to_fen($this->money);
        $data['service'] = 'create';//接口类型
        $data['mch_id'] = $this->merId;//商户号
        $data['nonce_str'] = time();//商户号
        $data['out_trade_no'] = $this->orderNum;
        $data['total_fee'] = (string)$money;
        $data['trade_type'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
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
            case 5:
                return 'pay.alipay.h5';//支付宝WAP
                break;
            default:
                return 'pay.alipay.h5';//微信扫码
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
        //传递参数为josn格式数据
        $pay_data = json_encode($pay_data,true);
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data =json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        if (isset($data['pay_info']))
        {
            $data = json_decode($data['pay_info'],true);
            if (empty($data)) $this->retMsg('返回支付信息格式错误！');
        }
        //判断是否下单成功
        if (empty($data['mweb_url']))
        {
            $msg = '返回参数错误';
            if (isset($data['message'])) $msg = $data['message'];
            if (isset($data['err_msg'])) $msg = $data['err_msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['mweb_url'];
    }
}