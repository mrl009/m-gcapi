<?php
/**
 * 速通支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Sutong_model extends Publicpay_model
{
    protected $c_name = 'sutong';
    private $p_name = 'SUTONG';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $field = 'sign'; //签名参数名

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = 'V1.0';
        $data['mch_id'] = $this->merId;
        $data['trade_type'] = $this->getPayType();
        $data['out_trade_no'] = $this->orderNum;
        $data['amount'] = $this->money;
        $data['attach'] = $this->p_name;
        $data['body'] = $this->p_name;
        $data['mch_create_ip'] = get_ip();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
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
                return 'wxscan';//微信扫码
                break;
            case 2:
                return 'wxh5';//微信Wap/h5
                break;
            case 4:
                return 'zfbscan';//支付宝扫码
                break; 
            case 5:
                return 'zfbh5';//支付宝WAP
                break;
            case 7:
                return 'wg';//网银支付
                break;
            case 8:
                return 'qqscan';//QQ扫码
                break;
            case 9:
                return 'jd';//京东扫码
                break;
            case 12:
                return 'qqh5';//QQwap
                break;
            case 17:
                return 'unionqr';//银联钱包
                break;
            case 25:
                return 'quick';//快捷
                break;
            default:
                return 'zfbscan';//支付宝扫码
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
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['payUrl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['data']['payUrl'];
    }
}
