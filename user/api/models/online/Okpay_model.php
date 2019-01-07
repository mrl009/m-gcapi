<?php

/**
 * Okpay支付接口
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/1
 * Time: 11:21
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay_model.php';
class Okpay_model extends Publicpay_model
{
//redis 错误记录
    protected $p_name = 'Okpay';
    protected $c_name = 'okpay';
    //签名参数
    protected $f_d  = 'sign';
    protected $m_d = 'X';
    protected $k_g = '';
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
    //构造支付参数
    protected function getPayData(){
        //构造签名参数
        $data = $this->getBaseData();
        //签名
        $string = $data['money'].$data['order_id'].$data['sdk'];
        $data['sign'] = md5(md5($string));
        return $data;
    }

    //签名参数 (修改)
    protected function getBaseData()
    {
        $data['id'] = $this->merId;  //商户id
        $data['sdk'] = $this->s_num; //第三方SDK(机构号)
        $data['order_id'] = $this->orderNum;
        $data['money']    = $this->money;
        $data['refer']    = $this->returnUrl;
        $data['notify_url'] = $this->callback;
        $data['Identification'] = '454a42d8dad186f50093270bd6bbccf1';//识别类型 固定值
        return $data;
    }

    /**
     * 根据编码获取支付通道
     */
    protected function getPayType(){

        switch ($this->code){
            case 1:
                return 'wechat';
                break;
            case 2:
                return 'wechat';
                break;
            case 4:
            case 5:
                return 'alipay';
                break;
            case 8:
            case 12:
                return 'qq';
                break;
            default:
                return 'alipay';
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
        //传递的参数数据
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['payurl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['payurl'];
    }

}