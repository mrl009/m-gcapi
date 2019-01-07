<?php

/**凯硕支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/11
 * Time: 18:52
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay_model.php';
class Kaishuo_model extends Publicpay_model
{
    protected $c_name = 'kaishuo';
    private $p_name = 'KAISHUO';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&paySecret='; //参与签名组成
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
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
       if (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else if(in_array($this->code,$this->wap_code)){
           return $this->buildWap($data);
       }else{
           return $this->useForm($data);
       }
    }
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['payKey'] =$this->merId;//支付key
        $data['orderPrice'] = $this->money;
        $data['outTradeNo'] = $this->orderNum;
        $data['productType']= $this->getPayType();
        $data['productType']= $this->getPayType();
        $data['orderTime']  = date('YmdHis',time());
        $data['productName'] = $this->p_name;
        $data['orderIp']     = get_ip();
        $data['returnUrl']   = $this->returnUrl;
        $data['notifyUrl']  = $this->callback;
        $data['remark']     = $this->p_name;
        if(in_array($this->code,[7]))
            $data['bankCode']= $this->bank_type;
        return $data;
    }
    protected function getPayUrl($pay)
    {
        $pay_url = $this->url;
        $pay_url = trim($pay['pay_url']);
        if (25 == $this->code)
        {
            $pay_url .= '/quickGateWayPay/initPay';
        } elseif (in_array($this->code,$this->wap_code)) {
            $pay_url .= '/cnpPay/initPay';
        } elseif (in_array($this->code,$this->scan_code)) {
            $pay_url .= '/cnpPay/initPay';
        }else{
            $pay_url .= '/cnpPay/initPay';
        }
        return $pay_url;
    }
    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return '10000103';//微信扫码https://gateway.lyzx8.cn/query/singleOrder
                break;
            case 2:
                return '10000203';//微信wap
                break;
            case 4:
                return '20000303';//支付宝
                break;
            case 5:
                return '20000203';//支付宝app
                break;
            case 7:
                return '50000103';//网关支付
                break;
            case 25:
                return '40000703';//快捷支付
                break;
            default :
                return '20000303';//支付宝扫码
                break;
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['payMessage']) || $data['resultCode'] <> '0000'){
            $msg = isset($data['errMsg']) ? $data['errMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['payMessage'];
        return $pay_url;
    }
}