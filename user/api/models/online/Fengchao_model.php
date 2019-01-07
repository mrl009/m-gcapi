<?php

/**
 *蜂巢支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/19
 * Time: 12:27
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Fengchao_model extends Publicpay_model
{
    protected $c_name = 'fengchao';
    protected $p_name = 'FENGCHAO';
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = ''; //参与签名组成
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
        $k = $this->key;
        //把数组参数以key=value形式拼接最后加上$ks值
        $string = data_value($data) . $k;
        //拼接字符串进行MD5大写加密
        $data['sign'] = strtolower(md5($string));
        $data['payMethod'] = $this->getPayType();
        $data['ip'] = get_ip();
        $data['client'] = 'mobile';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['shop_id'] = $this->merId;//商户号
        $data['order_user'] = $this->orderNum;
        $data['money_order'] = $this->money;
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
            case 1:
                return 'wx';//微信扫码
                break;
            case 2:
                return 'wx';//微信WAP
                break;
            case 4:
                return 'zfb';//支付宝扫码
                break;
            case 5:
                return 'zfb';//支付宝h5
                break;
            default:
                return 'zfb';//支付宝扫码
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
        if ($data['status'] <> '1' && empty($data['payurl']))
        {
            $msg = isset($data['error']) ? $data['error'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['payurl'];
    }
}