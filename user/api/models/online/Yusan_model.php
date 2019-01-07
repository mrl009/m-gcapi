<?php

/**
 * 雨伞支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/27
 * Time: 18:38
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Yusan_model extends Publicpay_model
{
    protected $c_name = 'yusan';
    private $p_name = 'YUSAN';//商品名称A
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成
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
        $data['sign'] = $this->getSign($data);
        $data['remark'] = $this->c_name;
        //构造签名参数
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';
        $data['customerid'] = $this->merId;
        $data['sdorderno'] = $this->orderNum;
        $data['total_fee'] = $this->money;
        $data['paytype'] = $this->getPayType();
        if ($this->code == 7){
            $data['bankcode'] = $this->bank_type;
        }
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        return $data;
    }

    protected function getSign($data){
        $signData = [
            'version' => $data['version'],
            'customerid' => $data['customerid'],
            'total_fee' => $data['total_fee'],
            'sdorderno' => $data['sdorderno'],
            'notifyurl' => $data['notifyurl'],
            'returnurl' => $data['returnurl']
        ];
        $signStr = data_to_string($signData).$this->key_string.$this->key;
        $sign = md5($signStr);
        return $sign;
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
                return 'wx_qrcode';//微信扫码
                break;
            case 2:
                return 'wxh5';//微信Wap/h5
                break;
            case 4:
                return 'zfb_qrcode';//支付宝扫码
                break;
            case 5:
                return 'zfb_wap';//支付宝WAP
                break;
            case 7:
                return 'bank';//网银支付
                break;
            case 17:
                return 'bank_qrcode';//银联扫码
                break;
            case 25:
                return 'bank_quick';//财付通
                break;
            default:
                return 'zfb_qrcode';//支付宝扫码
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
        $data = json_decode($data,true);
        //接收参数为JSON格式 转化为数组
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payurl'])|| $data['status']  <> "1")
        {
            $msg = isset($data['string ']) ? $data['string '] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['payurl'];
    }
}