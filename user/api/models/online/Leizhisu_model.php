<?php
/**
 * 雷之速支付接口调用
 * User: lqh
 * Date: 2018/05/27
 * Time: 09:12
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Leizhisu_model extends Publicpay_model
{
    protected $c_name = 'leizhisu';
    private $p_name = 'LZS';//商品名称

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
       return $this->buildForm($data);
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
        $sign_data = array_values($data);
        $sign_string = implode('',$sign_data);
        $data['format'] = 'web';
        $data['key'] = md5($sign_string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['uid'] = $this->merId;
        $data['price'] = $this->money;
        $data['istype'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['orderid'] = $this->orderNum;
        $data['token'] = $this->key;
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
                return '20001';//微信扫码WAP
                break;   
            case 4:
            case 5:
                return '10001';//支付宝扫码WAP
                break; 
            default:
                return '20001';//微信扫码
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true); 
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['code']) || (0 > $data['code'])
            || empty($data['data']) || empty($data['data']['qrcode']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        } 
        return $data['data']['qrcode'];
    }
}
