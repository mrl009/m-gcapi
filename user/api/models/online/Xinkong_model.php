<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/30
 * Time: 10:04
 */
include_once  __DIR__.'/Publicpay_model.php';

class Xinkong_model extends Publicpay_model
{
    protected $p_name = 'XINKONG';
    protected $c_name = 'xinkong';
    protected $mt = [100,200,500,1000,1500,2000,2500,3000,3500,4000,4500,5000
];

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
     * 构造基本参数
     */
    protected function getPayData(){
        $data = $this->getDataBase();
        //构造签名参数
        $string = implode('', array_values($data));
        $string .= $this->key;
        $data['sign'] = strtoupper(md5($string));
        $data['remark'] = $this->p_name;
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase(){
        //判断金额是否在允许的范围内
        $m = intval($this->money);
        if (!in_array($m,$this->mt))
        {
            $msg = '只允许充值金额100,200,500,1000,1500,2000,';
            $msg .= '2500,3000,3500,4000,4500,5000';
            $this->retMsg($msg);
        }
        $data['appid']=$this->merId;
        $data['orderno']=$this->orderNum;
        $data['amount']=$this->money;
        $data['paytype']=$this->getPayType();//充值方式
        $data['returnurl'] = $this->callback;
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
                return '1';//微信
                break;
            case 4:
            case 5:
                return '2';//支付宝
                break;
            case 25:
                return '5';//网银
                break;
            default:
                return '2';
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数 为json格式数据
        $pay_data = json_encode($pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $pay_data = str_replace('\/\/','//',$pay_data);
        $post_data['data'] = $pay_data;
        $post_data = http_build_query($post_data);
        $data = post_pay_data($this->url,$post_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrcode']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['qrcode'];
    }
}