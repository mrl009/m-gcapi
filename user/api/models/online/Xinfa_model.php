<?php
/**
 * 鑫发支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinfa_model extends Publicpay_model
{
    protected $c_name = 'xinfa';
    private $p_name = 'XINFA';//商品名称

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
        $string = json_encode($data,320);
        $string = str_replace('\/','/',$string);
        $string = str_replace('\/\/','//',$string);
        $string .= $this->key;
        $data['sign'] = strtoupper(md5($string));
        //构造RSA签名参数
        $string = json_encode($data,320);
        $string = str_replace('\/','/',$string);
        $string = str_replace('\/\/','//',$string);
        $rsamsg = $this->encodePay($string);
        //返回传送的报文参数
        unset($data,$string);
        $data['data'] = $rsamsg;
        $data['merchNo'] = $this->merId;
        return $data;
    }

    /**
     * 生成传输密文
     * @return array
     */
    private function encodePay($data)
    {
        $str = '';
        $encryptData = '';
        $data = str_split($data, 117);
        $sk = openssl_pkey_get_public($this->s_key);
        if (empty($sk)) $this->retMsg('解析第三方服务端公钥失败');
        foreach ($data as $chunk)
        {
            openssl_public_encrypt($chunk, $encryptData, $sk);
            $str = $str . $encryptData;
        }
        return base64_encode($str);
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $rand = mt_rand(10000000,99999999);
        $money = yuan_to_fen($this->money);
        $data['version'] = 'V3.3.0.0';
        $data['charsetCode'] = 'UTF-8';
        $data['merchNo'] = $this->merId;//商户号
        $data['payType'] = $this->getPayType();
        $data['randomNum'] = sprintf("%08d",$rand);
        $data['orderNo'] = $this->orderNum;
        $data['amount'] = (string)$money;
        $data['goodsName'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['notifyViewUrl'] = $this->returnUrl;
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
                return 'WX';//微信扫码
                break;
            case 2:
                return 'WX_WAP';//微信WAP
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFB_WAP';//支付宝WAP
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JDQB';//京东扫码
                break;
            case 10:
                return 'BAIDU';//百度扫码
                break;
            case 12:
                return 'QQ_WAP';//QQwap
                break;
            case 13:
                return 'JDQB_WAP';//京东wap
                break;
            case 17:
                return 'UNION_WALLET';//银联扫码
                break;
            default:
                return 'ZFB';//支付宝
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
        if (empty($data['qrcodeUrl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付连接或二维码地址
        return $data['qrcodeUrl'];
    }
}
