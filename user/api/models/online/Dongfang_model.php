<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';

class Dongfang_model extends Publicpay_model
{
    protected $c_name = 'dongfang';
    private $p_name = 'DONGFANG';//商品名称

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
        $k = $this->key;
        $signStr = '';
        foreach ($data as $v){
            $signStr .= $v;
        }
        $data['sign'] = md5($signStr.$k);
        $data['paytype'] = $this->getPayType();
        $data['refer'] = $this->returnUrl;
        $data['notify_url'] = $this->callback;
        $data['refer'] = $this->returnUrl;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['money']  = yuan_to_fen($this->money);
        $data['record'] = trim($this->orderNum);
        $data['appid']    = $this->merId;
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
            case 5:
                return '1';//支付宝
                break;
            default:
                return '1';//支付宝扫码
                break;
        }
    }

    /**
     * 获取支付提交返回结果
     * @param $data
     */
    protected function getPayResult($data)
    {
        //传递参数
        $pay_data = http_build_query($data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['image']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付连接或二维码地址
        return $data['data']['image'];
    }

}