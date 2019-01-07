<?php
/**
 * 安逸付支付接口调用
 * User: lqh
 * Date: 2018/07/29
 * Time: 14:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Anyifu_model extends Publicpay_model
{
    protected $c_name = 'anyifu';
    private $p_name = 'ANYIFU';//商品名称
    //支付接口签名参数 
    private $version = '3.0'; //接口版本
    private $service_name = 'fund_gpay_payment'; //接口名称


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
        return $this->buildForm($data);
    }
    
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造参与加密参数
        $data = $this->getBaseData();
        $head_data = $this->getHead();
        $body_data = $this->getBody();
        ksort($head_data);
        ksort($body_data);
        $params['head'] = $head_data;
        $params['body'] = $body_data;
        //参数转化成json数据
        $string = json_encode($params,320);
        $string = str_replace('\/\/','//',$string);
        $string = str_replace('\/','/',$string);
        $string = urlencode($string);
        //生成MD5秘钥和RSA秘钥
        $rsaMsg = $this->encodePay($string);
        $string .= $this->key;
        $data['rsamsg'] = $rsaMsg;
        $data['md5msg'] = md5($string);
        //删除中间变量
        unset($head_data,$body_data);
        unset($params,$string,$rsaMsg);
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
        $data['partner_id'] = $this->merId;
        $data['service_name'] = $this->service_name;
        $data['version'] = $this->version;
        return $data;
    }

    /**
     * 获取构造支付基本参数(头部)
     * @return array
     */
    private function getHead()
    {
        $data['serviceName'] = $this->service_name;
        $data['traceNo'] = $this->orderNum;
        $data['senderId'] = $this->merId;
        $data['sendTime'] = date('YmdHi');
        $data['charset'] = 'utf-8';
        $data['version'] = $this->version;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBody()
    {
        $money = yuan_to_fen($this->money);
        $data['orderNo'] = $this->orderNum;
        $data['orderDesc'] = $this->p_name;
        $data['productName'] = $this->p_name;
        $data['transAmount'] = (string)$money;
        $data['transTime'] = date('YmdHis');
        $data['transCurrency'] = 'CNY';
        $data['transType'] = '1';
        $data['payerAccType'] = 'DEBIT';
        $data['payMode'] = $this->getPayType();
        if (7 == $this->code)
        {
            $data['payerInstId'] = $this->bank_type;
        } else {
            $data['payerInstId'] = '';
        }
        $data['notifyUrl'] = $this->callback;
        $data['pageReturnUrl'] = $this->returnUrl;
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
                return 'Wechat';//微信扫码
                break;
            case 2:
                return 'WechatWap';//微信WAP
                break;
            case 4:
                return 'Alipay';//支付宝扫码
                break;
            case 5:
                return 'AlipayWap';//支付宝WAP
                break;
            case 7:
                return 'Bank';//网银
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JD';//京东扫码
                break;
            case 12:
                return 'QQWAP';//QQWAP
                break;
            case 17:
                return 'BankQRCode';//银联扫码
                break;
            case 25:
                return 'BankEX';//快捷支付
                break;
            default:
                return 'Alipay';//支付宝扫码
                break;
        }
    }
}
