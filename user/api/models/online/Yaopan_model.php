<?php
/**
 * 瑶槃支付接口调用 (以curl方式提交的参数 des加密)
 * User: lqh
 * Date: 2018/07/29
 * Time: 14:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yaopan_model extends Publicpay_model
{
    protected $c_name = 'yaopan';
    private $p_name = 'YAOPAN';//商品名称
    //支付接口签名参数 
    private $km = 'DES-CBC'; //签名模式

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
        $string = json_encode($data);
        $data = $this->stringEncrypt($string);
        return strtoupper($data);
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['amount'] = $this->money;//金额
        $data['goodsId'] = $this->getPayType();
        $data['ordercode'] = $this->orderNum;//订单号唯一
        $data['statedate'] = date('Ymd');
        if (7 == $this->code) $data['bankname'] = $this->bank_type;
        $data['merNo'] = $this->merId;//商户号
        $data['callbackurl'] = $this->callback;
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
                return '142';//微信扫码
                break;
            case 4:
                return '171';//支付宝扫码
                break;
            case 5:
                return '172';//支付宝WAP
                break;
            case 7:
                return '192';//网关支付
                break;
            case 8:
                return '152';//QQ扫码
                break; 
            case 9:
                return '212';//京东扫码
                break;
            case 17:
                return '132';//银联扫码
                break;     
            case 25:
                return '232';//快捷支付
                break;
            case 38:
                return '252';//苏宁扫码
                break;
            default:
                return '172';//微信扫码
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
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $pay_url = trim($pay['pay_url']) . 'merNo=' . $this->merId;
        }
        return $pay_url;
    }

    /**
     * 获取加密数据结果 DES加密解密过程计算
     * @return return 二维码内容
     */
    //字符串加密过程
    private function stringEncrypt($string)
    {
        $km = $this->km;
        $key = $iv = $this->key;
        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $string = $this->stringPkcs5($string, $size);
        $string = openssl_encrypt($string, $km, $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
        $string = bin2hex($string);
        return $string;
    }

    //字符串解密过程
    private function stringDecrypt($string)
    {
        $km = $this->km;
        $key = $iv = $this->key;
        $string = $this->stringHex($string);
        $string = openssl_decrypt($string, $km, $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
        $string = $this->stringUnPkcs5($string);
        return $string;
    }

    //使用Pkcs5进行字符串填充补位
    private function stringPkcs5($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    //去除字符串中使用Pkcs5补位数据
    private function stringUnPkcs5($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, - 1 * $pad);
    }

    //字符串hex输出
    private function stringHex($hexData)
    {
        $binData = "";
        for ($i = 0; $i < strlen($hexData); $i += 2) {
            $binData .= chr(hexdec(substr($hexData, $i, 2)));
        }
        return $binData;
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接受参数需要解密
        $data = $this->stringDecrypt($data);
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['codeUrl']))
        {
            $msg = "返回参数错误";
            if (isset($data['resultmsg'])) $msg = $data['resultmsg'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付二维码连接地址或WAP支付地址
        return $data['codeUrl'];
    }
}
