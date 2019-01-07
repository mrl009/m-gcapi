<?php
/**
 * 众付支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhongpay_model extends Publicpay_model
{
    protected $c_name = 'zhongpay';
    protected $p_name = 'ZHONGPAY';

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
        //wap支付
        if (in_array($this->code,[2,5]))
        {
            return $this->buildWap($data);
            //扫码支付
        } elseif (in_array($this->code,[1,4])) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildForm($data);
        }
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
                return '2';//微信扫码
                break;
            case 4:
                return '6';//支付宝
                break;
            case 5:
                return '6';//支付宝
                break;
            case 36:
            case 37:
                return '9';//支付宝
                break;
            default:
                return '6';
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