<?php
/**
 * 聚合支付接口调用
 * User: lqh
 * Date: 2018/05/04
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Juhe_model extends Publicpay_model
{
    protected $c_name = 'juhe';
    private $settleType = 1;//结算方式
    private $p_name = 'JHF';//交易备注
    //扫码、wap、公众号支付(支付宝、微信、QQ、银联钱包)
    private $sp = [1,2,4,5,8,12,16,17,18,19,33,36];
    //网关支付(收银台、直联银行)
    private $wp = [7,26];
    //快捷支付
    private $kp = [25];
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'signature'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
       // 网银支付$wp
        if (in_array($this->code,$this->wp)) 
        {
            return $this->buildForm($data);
        } 
        //除网银支付外 其他支付都是返回二维码
        if(in_array($this->code,$this->sp))
        {
            return $this->buildScan($data);
        }
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
        $data['goodsName'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['settleType'] = $this->settleType;//结算方式 T+1
        //扫码、wap、公众号支付(支付宝、微信、QQ、银联钱包)
        if(in_array($this->code,$this->sp))
        {
            //支付方式参数
            $data['payType'] = $this->getPayType();
            //公众号支付支付 特殊参数
            if(in_array($this->code,[16,19,33,36]))
            {
                $data['returnUrl'] = $this->returnUrl;
            }
        //网关支付(收银台、直联银行)
        } elseif(in_array($this->code,$this->wp)) {
            //对接模式参数
            $data['channel'] = $this->getPayChannel();
            //网银支付 参数
            if(7 == $this->code)
            {
                $data['bankCode'] = $this->bank_type;
            }
        //快捷支付
        } elseif(in_array($this->code,$this->kp)) {
            $data['cardno'] = $this->cardNo;
            $data['cardType'] = $this->cardType;
            $data['settleType'] = 2; //结算方式 T+2
        }
        return $data;
    }

    /**
     * 获取支付网关地址 
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay_data=[])
    {
        switch ($this->code)
        {
            case 1:
            case 4:
            case 8:
            case 17:
                return 'http://115.159.50.242:6060/passivePay';//扫码支付
                break;
            case 2:
            case 5:
            case 12:
            case 18:
                return 'http://115.159.50.242:6060/wapPay';//wap支付
                break; 
            case 16:
            case 19:
            case 33:
            case 36:
                return 'http://115.159.50.242:6060/openPay';//公众号支付
                break; 
            case 7:
            case 26:
                return 'http://115.159.50.242:6060/gateway.do?m=order';//网关支付
                break;
            case 25:
                return 'http://115.159.50.242:6060/h5Quick.do';//快捷支付
                break;
            default:
                return 'http://115.159.50.242:6060/passivePay';//扫码支付
                break;
        }
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
            case 33:
                return 2;//微信支付
                break;
            case 4:
            case 5:
            case 36:
                return 1;//支付宝支付
                break;
            case 8:
            case 12:
            case 16:
                return 8;//QQ钱包支付
                break;
            case 17:
            case 18:
            case 19:
                return 32;//银联钱包支付
                break;
            default:
                return 1;
                break;
        }
    }

    /**
     * 根据code值获取对接模式
     * @param string code 
     * @return string 聚合付支付方式 参数
     */
    private function getPayChannel()
    {
        switch ($this->code)
        {
            case 7:
                return 2; //直联银行
                break;
            case 26:
                return 1; //收银台
                break;
            default:
                return 1;
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
        //传递参数为STRING格式 将数组转化成STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (!isset($data['respCode']) || ('00' <> $data['respCode'])
           || empty($data['barCode']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['barCode'];
    }
}
