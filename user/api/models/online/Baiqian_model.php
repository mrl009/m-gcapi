<?php
/**
 * 百钱支付接口调用(更新)
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Baiqian_model extends Publicpay_model
{
    protected $c_name = 'baiqian';
    private $p_name = 'BAIQIAN'; //商品名称
    private $ks = '&'; //参与签名组成

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
        ksort($data);
        $k = $this->ks . strtoupper(md5($this->key));
        $string = ToUrlParams($data) . $k;
        $data['X5_NotifyURL'] = $this->returnUrl;
        $data['X6_MD5info'] = strtoupper(md5($string));
        $data['X7_PaymentType'] = $this->getPayType();
        $data['X8_MerRemark'] = $this->p_name;
        $data['X9_ClientIp'] = get_ip();
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['X1_Amount'] = $this->money;
        $data['X2_BillNo'] = $this->orderNum;
        $data['X3_MerNo'] = $this->merId;
        $data['X4_ReturnURL'] = $this->callback;
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
                return 'WXSM';//微信扫码
                break;
            case 2:
                return 'WXH5';//微信WAP
                break;
            case 4:
                return 'ALIPAYSM';//支付宝扫码
                break; 
            case 5:
                return 'ALIPAYH5';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'QQSM';//QQ扫码
                break;
            case 9:
                return 'JDSM';//京东扫码
                break;
            case 17:
                return 'BSM';//银联扫码
                break;
            case 18:
                return 'YLWAP';//银联WAP
                break;
            case 25:
            case 27:
                return 'KJZF';//网银快捷
                break;
            default:
                return 'ALIPAYSM';//支付宝扫码
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
        if (empty($data['imgUrl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['imgUrl'];
    }
}
