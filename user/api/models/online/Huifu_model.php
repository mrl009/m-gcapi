<?php
/**
 * 汇付支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Huifu_model extends Publicpay_model
{
    protected $c_name = 'huifu';
    protected $p_name = 'HUIFU';

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data,'','') . $this->key;
        $data['sign'] = strtoupper(sha1($string));
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['mchid'] = $this->merId;
        $data['order_id'] = $this->orderNum;
        $data['channel_id'] = $this->getPayType();
        $data['total_amount'] = $this->money;
        $data['return_url'] = $this->callback;
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
                return '3';//微信扫码
                break;
            case 2:
                return '2';//微信H5
                break;
            case 4:
            case 5:
                return '1';//支付宝
                break;
            default:
                return '1';
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
        if (empty($data['url']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付连接或二维码地址
        return $data['url'];
    }
}