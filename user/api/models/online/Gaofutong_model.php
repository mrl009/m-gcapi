<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/24
 * Time: 9:53
 */
include_once __DIR__.'/Publicpay_model.php';

class Gaofutong_model extends Publicpay_model
{
     protected $c_name = 'gaofutong';
     protected $p_name = 'GAOFUTONG';
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
      if (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildWap($data);
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
        $data['merchantNo'] = $this->merId;//商户号
        $data['orderAmount'] = yuan_to_fen($this->money);
        //微信通道只支持 为100元的整数金额
        if(in_array($this->code,[1])&& intval($this->money) !=100){
            $this->retMsg('微信通道请支付100元');
        }
        $data['orderNo'] = $this->orderNum;
        $data['payType'] = $this->getPayType();
        $data['notifyUrl'] = $this->callback;
        $data['callbackUrl'] = $this->returnUrl;
        $data['ip'] = get_ip();//非必填 风控
        if(in_array($this->code,[5,18,34])) {
            if($this->code != 18) $data['deviceType'] = $this->from_way3 ? '01' : '02';
            $data['mchAppId'] = '.qq';
            $data['mchAppName'] = 'Ap';
        }elseif($this->code==7) {
            $data['bankName'] = $this->bank_type;
            $data['currencyType'] = 'CNY';
            $data['productName'] = 'XiaoMoney';
            $data['productDesc'] = $this->c_name;
            $data['cardType'] = '1';
            $data['businessType'] = '01';
            $data['remark'] = $this->p_name;
        }elseif($this->code==33) {
            $data['openid'] = '';//公众号支付传openid
        }
        return $data;
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
                return '2';//微信扫码
                break;
            case 2:
                return '12';//微信WAP
                break;
            case 4:
                return '4';//支付宝扫码 1,4,5,7,17,25
                break;
            case 5:
                return '13';//支付宝WAP
                break;
            case 7:
                return '11';//网关支付
                break;
            case 8:
                return '8';//QQ钱包扫码
                break;
            case 9:
                return '14';//京东扫码
                break;
            case 38:
                return '15';//苏宁扫码
                break;
            case 12:
                return '7';//QQWAP
                break;
            case 17:
                return '9';//银联钱包扫码
                break;
            case 18:
                return '5';//银联钱包wap
                break;
            case 25:
                return '6';//银联快捷
                break;
            case 33:
                return '1';//微信公众号
                break;
            case 34:
                return '3';//微信app
                break;
            default:
                return '2';//微信扫码
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

        $payUrl = '';
        if (!empty($pay['pay_url']))
        {
            $payUrl = trim($pay['pay_url']);
        }

        if (7 == $this->code) {
            $payUrl .= '/payapi/netpay';
        }else
        {
            $payUrl .= '/payapi/order';

        }
        return $payUrl;
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        //$pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']) && $data['status']<>'T')
        {
            $msg = isset($data['errMsg']) ? $data['errCode'].$data['errMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        if($data['qrcode_build']=='on'&& in_array($this->code,$this->scan_code)){
            //扫码支付返回支付二维码,无需再生成
            return $data;
        }else{
            //扫码支付返回支付二维码连接地址
            return $data['payUrl'];
        }

    }
    /**
     * 创建扫码支付 返回二维码无需自己生成
     * @param array $data 支付参数
     * @return array
     */
    protected function buildScan($data)
    {
        //第三方支付返回 二维码地址
        $qrcode_url = $this->getPayResult($data);
        if($data['qrcode_build']=='on'){
            $res = [
                'jump'      => 2,
                'img'       => $qrcode_url['payUrl'],
                'money'     => $this->money,
                'order_num' => $this->orderNum,
            ];
        }else{
            $res = [
                'jump'      => 3,
                'img'       => $qrcode_url,
                'money'     => $this->money,
                'order_num' => $this->orderNum,
            ];
        }
        return $res;
    }
}