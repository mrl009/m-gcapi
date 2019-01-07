<?php
/**
 * 易充支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yichong_model extends Publicpay_model
{
    protected $c_name = 'yichong';
    private $p_name = 'YICHONG';//商品名称

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
        ksort($data);
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['id'] = $this->merId;
        $data['pay_id'] = $this->user['id'];
        $data['type'] = $this->getPayType();
        $data['price'] = $this->money;
        $data['order_no'] = $this->orderNum;
        $data['param'] = $this->p_name;
        $data['timestamp'] = time();
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
                return '2';//支付宝
                break; 
            case 5:
                return '4';//支付宝
                break; 
            default:
                return '2';//支付宝
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
        $pay_data = json_encode($pay_data,true);
        $data = post_pay_data($this->url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['url']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '接口返回参数错误';
            $this->retMsg($msg);
        }
        return $data['data']['url'];
    }
}
