<?php

/**easypay支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/6
 * Time: 11:15
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Easypay_model extends Publicpay_model
{
    protected $c_name = 'easypay';
    private $p_name = 'EASYPAY';//商品名称

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
       if (in_array($this->code,$this->scan_code)) {
            return $this->buidImage($data);
        } else {
            return $this->buildWap($data);
        }
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
        ksort($data);
        $data['uid'] = $this->merId;
        $sign_data = array_values($data);
        $sign_string = implode('',$sign_data);
        $data['key'] = md5($sign_string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['istype'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        $data['orderuid'] = $this->user['username'];
        $data['orderid'] = $this->orderNum;
        $data['token'] = $this->key;
        $data['goodsname'] = $this->money;
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
            case 1:
            case 2:
                return '1';//微信扫码WAP
                break;
            case 4:
            case 5:
                return '2';//支付宝扫码WAP
                break;
            default:
                return '2';//微信扫码
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
        $this->url = $this->url.'/pay/index';
        $data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['code']) || ('1' > $data['code'])
            || empty($data['data']) )
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        if(in_array($this->code,$this->scan_code)){
            $payurl = $data['data']['qrcode'];
        }else{
            $payurl = $data['data']['pay_url'];
        }
        return $payurl;
    }
}