<?php
/**
 * 口碑支付接口调用
 * User: lqh
 * Date: 2018/08/12
 * Time: 16:50
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Koubei_model extends Publicpay_model
{
    protected $c_name = 'koubei';
    private $p_name = 'KOUBEI';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'signature'; //签名参数名

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
        $data['merchno'] = $this->merId; 
        $data['traceno'] = $this->orderNum;
        $data['amount'] = $this->money;
        $data['payType'] = $this->getPayType();
        $data['goodsName'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['settleType'] = 1; //结算方式 T+2
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
                return 2;//微信支付
                break; 
            case 4:
            case 5:
                return 1;//支付宝支付
                break;
            case 8:
            case 12:
                return 8;//QQ支付
                break; 
            case 9:
            case 13:
                return 16;//京东支付
                break;
            case 17:
            case 18:
                return 32;//银联支付
                break;
            default:
                return 1;//支付宝支付
                break;
        }
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = isset($pay['pay_url']) ? trim($pay['pay_url']) : '';
        if (in_array($this->code,$this->scan_code)) $pay_url .= 'passivePay';
        if (in_array($this->code,$this->wap_code)) $pay_url .= 'wapPay';
        return $pay_url;
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式 将数组转化成STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['barCode']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['barCode'];
    }
}
