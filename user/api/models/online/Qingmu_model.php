<?php
/**
 * 青木支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Qingmu_model extends Publicpay_model
{
    protected $c_name = 'qingmu';
    private $p_name = 'QINGMU';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名
    private $amount =[10,20,30,40,50,60,70,80,90,100,200,300,400,500,600,700,800,900,1000,2000,3000];

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
        $data['mchId'] = $this->merId;
        $data['mchOrderNo'] = $this->orderNum;
        $data['channelId'] = $this->getPayType();
        $data['amount'] = yuan_to_fen($this->money);
        if($this->code==2){
            if(in_array($this->money,$this->amount)){
                $data['amount'] = yuan_to_fen($this->money);
            }else{
                $this->retMsg('请支付10,20,30,40,50,60,70,80,90,100,200,300,400,500,600,700,800,900,1000,2000,3000');
            }}
        $data['notifyUrl'] = $this->callback;
        $data['subject'] = $this->p_name;
        $data['body'] = $this->p_name;
        $data['clientIp'] = get_ip();
        if (7 == $this->code) $data['bankCode'] = $this->bank_type;
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
                return '1204';//微信扫码
                break;
            case 2:
                return '1203';//微信WAP
                break; 
            case 4: 
            case 5:
                return '1201';//支付宝WAP
                break;
            case 7:
                return '1209';//网银支付
                break;
            case 9:
                return '1211';//京东钱包
                break;
            case 12:
                return '1206';//QQwap
                break;
            case 17:
                return '1210';//QQwap
                break;
            case 40:
                return '1207';//微信条码
                break;    
            default:
                return '1202';//支付宝扫码
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
        //傳遞參數為json數據
        $pay_data = json_encode($pay_data,true);
        $data = post_pay_data($this->url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        if (empty($data['payUrl']) && empty($data['qrUrl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $payUrl = !empty($data['qrUrl']) ? $data['qrUrl'] : $data['payUrl'];
        return $payUrl;
    }
}
