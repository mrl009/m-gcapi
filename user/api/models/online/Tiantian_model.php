<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 天天惠模块
 * @version     v1.0 2017/1/17
 */
class Tiantian_model extends MY_Model
{
    public $privateKey;
    public $publicKey;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('common_helper');
    }

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money * 100;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->privateKey = isset($pay_data['pay_private_key']) ? $pay_data['pay_private_key'] : '';//商户私钥
        $this->publicKey = isset($pay_data['pay_public_key']) ? $pay_data['pay_public_key'] : '';//商户公钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Tiantian/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        $aesKey = $this->createNonceStr(16);
        $postData = [
            'Data' => $this->encrypt(json_encode($data), $aesKey),
            'Key' => $this->publicEncrypt($aesKey),
            'partner' => 'HEGS_HBYC',
            'signData' => $this->sign(json_encode($data)),
            'TypeCode' => 'LH_PAY',
            'reqMsgId' => $data['ordMsgId'],
        ];
        $res = json_decode($this->request($postData), true);
        $aes = $this->privateDecrypt($res['respEncrtptKey']);
        $msg = json_decode($this->decrypt($res['respEncrtptDate'], $aes), true);
        if (isset($msg['code']) && $msg['code'] == '000000') {
            if (in_array($pay_data['code'], [2, 5, 33])) {
                return [
                    'url' => $msg['respMsg'],
                    'jump' => 5
                ];
            } else {
                return [
                    'jump' => 3,
                    'img' => $msg['respMsg'],
                    'money' => $money,
                    'order_num' => $order_num,
                ];
            }
        } else {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: 通道正在维护！{$msg['code']} {$msg['message']}"));
            exit;
        }
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        // 请求数据赋值
        $data['saru'] = $this->merId;// 商户在支付平台的的平台号
        $data['transAmt'] = $this->money;// 金额
        $data['timestamp'] = time();// 金额
        $data['ordMsgId'] = $this->orderNum;// 订单号
        $data['type'] = $this->getType($code);// 交易类型
        $data['mark'] = $this->getMark($code);// 支付方式
        $data['requestIp'] = get_ip();// IP地址
        $data['notifyUrl'] = $this->callback;// 商户通知地址
        return $data;
    }

    /**
     * @param $code
     * @return int
     */
    private function getType($code)
    {
        switch ($code) {
            case 1:
                return 'WXZF';//微信扫码
            case 2:
                return 'WXZF';//微信WAP
            case 4:
                return 'ALIPAY';//支付宝扫码
            case 5:
                return 'ALIPAY';//支付宝WAP
            case 8:
                return 'QQPAY';//QQ钱包
            case 17:
                return 'UNIONPAY';
            case 33:
                return 'WXZF';//微信H5
            default:
                return 'WXZF';
        }
    }

    /**
     * @param $code
     * @return int
     */
    private function getMark($code)
    {
        switch ($code) {
            case 1:
                return 'D3';//微信扫码
            case 2:
                return 'D4';//微信WAP
            case 4:
                return 'D3';//支付宝扫码
            case 5:
                return 'D4';//支付宝WAP
            case 8:
                return 'D3';//QQ钱包
            case 17:
                return 'D3';
            case 33:
                return 'D1';//微信H5
            default:
                return 'D3';
        }
    }

    /**
     * 产生随机字符串
     * @param int $length 指定字符长度
     * @param string $str 字符串前缀
     * @return string
     */
    private function createNonceStr($length = 32, $str = "")
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 公钥加密
     * @param $data
     * @return mixed
     */
    private function publicEncrypt($data)
    {
        $crypto = '';
        foreach (str_split($data, 245) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, openssl_pkey_get_public($this->publicKey));
            $crypto .= $encryptData;
        }
        $encrypted = $this->urlSafeB64Encode($crypto);
        return $encrypted;
    }

    /**
     * 私钥解密
     * @param $encrypted
     * @return string
     */
    private function privateDecrypt($encrypted)
    {
        $crypto = '';
        foreach (str_split($this->urlSafeB64Decode($encrypted), 256) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, openssl_pkey_get_private($this->privateKey));
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    /**
     * 加密码时把特殊符号替换成URL可以带的内容
     * @param $string
     * @return mixed|string
     */
    private function urlSafeB64Encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    /**
     * 解密码时把转换后的符号替换特殊符号
     * @param $string
     * @return bool|string
     */
    private function urlSafeB64Decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    /**
     * encrypt aes加密
     * @param $data
     * @param $key
     * @return string
     */
    private function encrypt($data, $key)
    {
        $data = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * decrypt aes解密
     * @param $sStr
     * @param $sKey
     * @return string
     */
    private function decrypt($sStr, $sKey)
    {
        $decrypted = openssl_decrypt(base64_decode($sStr), 'AES-128-ECB', $sKey, OPENSSL_RAW_DATA);
        return $decrypted;
    }

    /**
     * 签名 SHA1_WITH_RSA
     * @param $data
     * @return string
     */
    public function sign($data)
    {
        $key = openssl_pkey_get_private($this->privateKey);
        openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA1);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Mobile Safari/537.36';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->buildPost($data));
        list($content, $status) = array(curl_exec($curl), curl_getinfo($curl), curl_close($curl));
        return (intval($status["http_code"]) === 200) ? $content : false;
    }

    /**
     * POST数据过滤处理
     * @param $data
     * @return mixed
     */
    private function buildPost(&$data)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_string($value) && !empty($value) && $value[0] === '@' && class_exists('CURLFile', false)) {
                    $filename = realpath(trim($value, '@'));
                    file_exists($filename) && $value = new CURLFile($filename);
                }
            }
        }
        return $data;
    }
}
