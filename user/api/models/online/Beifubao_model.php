<?php
/**
 * 北付宝支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Beifubao_model extends Publicpay_model
{
    protected $c_name = 'beifubao';
    private $p_name = 'BEIFUBAO';//商品名称

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
        //获取头部基本参数
        $head_data = $this->getHead();
        $context_data = $this->getContext();
        //生成用户私钥签名数据
        $head_data['sign'] = $this->getPriveKey($context_data);
        //转换成json数据
        $data['businessHead'] = $head_data;
        $data['businessContext'] = $context_data;
        $data = json_encode($data,320);
        //获取最后传输的签名数据 
        $context = $this->encodePay($data);
        if (empty($context)) $this->retMsg('加密密文错误！');
        unset($data);
        $data['context'] = $context;
        unset($head_data,$context_data);
        return $data;
    }
    
    /**
     * 生成私钥key
     * @return array
     */
    private function getPriveKey($data)
    {
        ksort($data);
        $string = json_encode($data,320);
        $pk = openssl_get_privatekey($this->p_key);
        if (empty($pk)) $this->retMsg('解析商户私钥失败');
        openssl_sign($string, $sign_info, $pk, OPENSSL_ALGO_MD5);
        return base64_encode($sign_info);
    }

    /**
     * 生成传输密文
     * @return array
     */
    private function encodePay($data)
    {
        $str = '';
        $encryptData = '';
        $sk = openssl_pkey_get_public($this->s_key);
        if (empty($sk)) $this->retMsg('解析第三方服务端公钥失败');
        foreach (str_split($data, 117) as $chunk) 
        {
            openssl_public_encrypt($chunk, $encryptData, $sk);
            $str = $str . $encryptData;
        }
        return base64_encode($str);
    }

    /*
     * 秘钥解密方式
     */
    private function decodePay($data)
    {
        $crypto = '';
        $pk = openssl_pkey_get_private($this->p_key);
        if (empty($pk)) $this->retMsg('解析商户私钥失败');
        //分段解密   
        foreach (str_split($data, 128) as $chunk) 
        {
            openssl_private_decrypt($chunk, $decryptData, $pk);
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    /**
     * 构造支付基本参数(头部)
     * @return array
     */
    private function getHead()
    {
        $data['charset'] = '00';
        $data['version'] = 'V1.0.0';
        $data['memberNumber'] = $this->merId;
        $data['method'] = 'UNIFIED_PAYMENT';
        $data['requestTime'] = date('YmdHis');
        $data['signType'] = 'RSA';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getContext()
    {
        $data['defrayalType'] = $this->getPayType();
        $data['memberOrderNumber'] = $this->orderNum;
        $data['tradeCheckCycle'] = 'T1';
        $data['orderTime'] = date('YmdHis');
        $data['currenciesType'] = 'CNY';
        $data['tradeAmount'] = yuan_to_fen($this->money);
        $data['commodityBody'] = $this->p_name;
        $data['commodityDetail'] = $this->p_name;
        $data['commodityRemark'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['terminalId'] = $this->user['id'];
        $data['terminalIP'] = get_ip();
        $data['userId'] = $this->user['id'];
        $data['remark'] = $this->p_name;
        $data['attach'] = $this->p_name;
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
                return 'WECHAT_NATIVE';//微信扫码
                break;
            case 4: 
                return 'ALI_NATIVE';//支付宝扫码
                break;
            case 5:
                return 'ALI_H5';//支付宝WAP
                break;
            default:
                return 'ALI_NATIVE';//支付宝扫码
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
        $pay_data = json_encode($pay_data,320);
        $data = post_pay_data($this->url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //验证下单是否成功
        if (empty($data['context']))
        {
            $msg = '返回参数错误';
            if (!empty($data['message']['content']))
            {
                $msg = $data['message']['content'];
            }
            $this->retMsg("下单失败：{$msg}");
        }
        //解密密文
        $data = base64_decode($data['context']);
        if (empty($data)) $this->retMsg('密文格式解析错误！');
        $data = $this->decodePay($data);
        if (empty($data)) $this->retMsg('密文解密错误！');
        //解密的json数据转化成数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('密文转化格式错误！');
        //解析数据以后 获取支付连接
        if (empty($data['businessContext']['content']))
        {
            $this->retMsg('返回支付链接错误！');
        }
        return $data['businessContext']['content'];
    }
}
