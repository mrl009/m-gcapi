<?php
/**
 * 新媒体支付接口调用
 * User: lqh
 * Date: 2018/08/20
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinmeiti_model extends Publicpay_model
{
    protected $c_name = 'xinmeiti';
    private $p_name = 'XINMEITI';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
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
        $data['mert_no'] = $this->merId;
        $data['out_trade_no'] = $this->orderNum;
        $data['pay_type'] = $this->getPayType();
        $data['amount'] = yuan_to_fen($this->money);
        $data['order_ip'] = get_ip();
        $data['notify_url'] = $this->callback;
        $data['order_time'] = time();
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
                return 'WX_QR';//微信扫码
                break;
            case 4:
                return 'ALI_QR';//支付宝扫码
                break;
            case 5:
                return 'ALI_WAP';//支付宝WAP
                break;
            case 8: 
                return 'QQ_QR';//QQ扫码
                break;
            default:
                return 'ALI_QR';//支付宝扫码
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
        //传递参数为json格式数据
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['res_data']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['res_data'];
    }
}
