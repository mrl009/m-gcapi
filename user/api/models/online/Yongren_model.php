<?php
/**
 * 永仁支付接口调用
 * User: lqh
 * Date: 2018/07/01
 * Time: 17:21
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yongren_model extends Publicpay_model
{
    protected $c_name = 'yongren';
    private $p_name = 'YONGREN';//商品名称

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
        //扫码支付返回二维码
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildScan($data);
        } else {
            return $this->buildForm($data);
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
        $string = data_to_string($data) . $this->key;
        $data['signMsg'] = md5($string);
        //出扫码支付外特殊参数
        if (!in_array($this->code,$this->scan_code))
        {
            $data['bankCode'] = $this->getPayType();
        }
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        //网银支付方式
        if (7 == $this->code)
        {
            $data['apiName'] = 'WEB_PAY_B2C';
        //WAP支付
        } elseif (in_array($this->code,$this->wap_code)) {
            $data['apiName'] = 'WAP_PAY_B2C';
        } else {
            $data['apiName'] = $this->getPayType();
        }    
        $data['apiVersion'] = '1.0.0.0';
        $data['platformID'] = $this->merId;//商户号
        $data['merchNo'] = $this->merId;//商户号
        $data['orderNo'] = $this->orderNum;
        $data['tradeDate'] = date('Ymd');
        $data['amt'] = $this->money;
        $data['merchUrl'] = $this->callback;
        $data['merchParam'] = urlencode($this->p_name);
        $data['tradeSummary'] = $this->p_name;
        //扫码支付参数
        if (in_array($this->code,$this->scan_code)) 
        {
            $data['overTime'] = 300;
            $data['customerIP'] = get_ip();
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
                return 'WECHAT_PAY';//微信扫码
                break;   
            case 2:
                return 'WXWAP';//微信WAP
                break;
            case 4:
                return 'ZFB_PAY';//支付宝扫码
                break;
            case 5:
                return 'ZFBWAP';//支付宝WAP
                break; 
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 8:
                return 'QQ_PAY';//QQ扫码
                break;
            case 9:
                return 'JD_PAY';//京东钱包
                break; 
            case 12:
                return 'QQWAP';//QQwap
                break;  
            case 17:
                return 'UNION_PAY';//银联钱包
                break;
            default:
                return 'WECHAT_PAY';//微信扫码
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
        if (empty($data['code']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return base64_decode($data['code']);
    }
}
