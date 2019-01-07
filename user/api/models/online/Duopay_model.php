<?php

/**
 * 多付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/13
 * Time: 10:32
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Duopay_model extends Publicpay_model
{
    protected $c_name = 'duopay';
    private $p_name = 'DUOPAY';//商品名称
    private $field = 'sign';
    private $method = 'D';
    private $ks = '&key=';

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
        //构造MD5签名参数
        ksort($data);
        $k = $this->ks.$this->key;
        $fd = $this->field;
        $md = $this->method;
        $data = get_pay_sign($data,$k,$fd,$md);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = 'V1.0.5';
        $data['serviceName'] = 'openOrderPay';
        $data['reqTime'] = date('Y-m-d H:i:s',time());
        $data['merchantId'] = $this->merId;//商户号
        $data['payType'] = $this->getPayType();
        if($this->code== 7){
          $data['bankCode'] = $this->bank_type;
        }
        $data['merOrderNo'] = $this->orderNum;
        $data['orderAmount'] = $this->money;
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['signType'] = 'MD5';
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
                return 'WXPAY';//微信扫码
                break;
            case 2:
                return 'WXPAY';//微信WAP
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY';//支付宝WAP

                break;
            case 7:
                return 'BANKPAY';//网银
                break;
            case 8:
                return 'QQPAY';//QQ扫码
                break;
            case 9:
                return 'JDPAY';//京东扫码
                break;
            case 12:
                return 'QQPAY';//QQwap
                break;
            case 13:
                return 'JDPAY';//京东wap
                break;
            case 17:
                return 'UNIONPAY';//银联扫码
                break;
            case 25:
                return 'QUICKPAY';//快捷支付
                break;
            default:
                return 'ALIPAY';//支付宝
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
        if (empty($data['respBody']) || $data['respCode'] <> 'SUCCESS')
        {
            $msg = isset($data['respDesc']) ? $data['respDesc'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付连接或二维码地址
        return $data['respBody'];
    }
    /*银行：
        'Duopay'   => [
        'BANK_CCB'  => '中国建设银行',
        'BANK_ICBC' => '中国工商银行',
        'BANK_ABC'  => '中国农业银行',
        'BANK_BOC'  => '中国银行',
        'BANK_BOCOM'=> '中国交通银行',
        'BANK_CMBC' => '中国民生银行',
        'BANK_HXBC' => '华夏银行',
        'BANK_CIB'  => '兴业银行',
        'BANK_GDB'  => '广发银行',
        'BANK_CEB'  => '中国光大银行',
        'BANK_PSBC' => '中国邮政银行',
        'BANK_NBCB' => '宁波银行',
        'BANK_HZCB' => '杭州银行',
        'BANK_TCCB' => '天津银行',
        'BANK_CZB'  => '浙商银行',
        'BANK_CMB'  => '中国招商银行',
        'BANK_SPDB' => '浦发银行',
        'BANK_CITIC'=> '中信银行',
        'BANK_PAB'  => '平安银行',
        'BANK_BEA'  => '东亚银行',
        'BANK_BOBJ' => '北京银行',
        'BANK_BJRCB'=> '北京农商银行',
        'BANK_ZJTLCB'=> '浙江泰隆商业银行',
        'BANK_BON'  => '南京银行',
        'BANK_CBHB' => '渤海银行',
        'BANK_SRCB' => '上海农商银行',
        'BANK_BOS'  => '上海银行'
    ],*/
}