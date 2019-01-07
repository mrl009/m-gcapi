<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 轻易付支付模块
 * @version     v1.0 2017/1/11
 */
class Qinyifu2_model extends MY_Model
{
    private $key;
    private $privateKey;
    private $publicKey;
    private $serviceKey;
    private $merId;
    private $orderNum;
    private $money;
    private $url;
    private $callback;
    private $domain;

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $order_num
     * @param $money
     * @param $pay_data
     * @return array
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money * 100;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//商户秘钥
        $this->privateKey = isset($pay_data['pay_private_key']) ? $pay_data['pay_private_key'] : '';//商户私钥
        $this->publicKey = isset($pay_data['pay_public_key']) ? $pay_data['pay_public_key'] : '';//商户公钥
        $this->serviceKey = isset($pay_data['pay_server_key']) ? $pay_data['pay_server_key'] : '';//服务公钥
        $this->url = $this->getUrl($pay_data['code']);//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Qinyifu/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        $str = $this->encodePay(json_encode($data, 320));
        $param = 'data=' . urlencode($str) . '&merchNo=' . $this->merId . '&version=V3.1.0.0';
        $url = $this->getUrl($pay_data['code']);
        $res = json_decode($this->request($url, $param), true);
        if ($res['stateCode'] != '00') {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误码 : {$res['stateCode']} 错误信息: 通道正在维护！"));
            exit;
        }
        $rs = $this->getRs($pay_data['code'], $res);
        return $rs;
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $bank = isset($pay_data['bankType']) ? $pay_data['bankType'] : '';
        // 请求数据赋值
        $data['orderNum'] = $this->orderNum;// 订单号
        $data['version'] = 'V3.1.0.0';
        $data['charset'] = 'UTF-8';
        $data['random'] = (string)rand(1000, 9999);
        $data['merNo'] = $this->merId;// 商户在支付平台的的平台号
        $data['netway'] = $this->getService($code, $bank);    //WX:微信支付,ZFB:支付宝支付
        $data['amount'] = (string)$this->money;// 金额
        $data['goodsName'] = 'QYF';
        $data['callBackUrl'] = $this->callback;// 商户通知地址
        $data['callBackViewUrl'] = get_auth_headers('Origin');;
        $data['sign'] = $this->sign($data, $this->key);
        return $data;
    }

    /**
     * @param $code
     * @param $bank
     * @return int
     */
    private function getService($code, $bank)
    {
        switch ($code) {
            case 1:
                return 'WX';//微信
            case 2:
                return 'WX_WAP';//微信app
            case 33:
                return 'WX_H5';//微信扫码
            case 4:
                return 'ZFB';//支付宝
            case 5:
                return 'ZFB_WAP';//支付宝app
            case 7:
                return $bank;//网银
            case 8:
                return 'QQ';//qq钱包
            case 12:
                return 'QQ_WAP';//qqwap
            case 9:
                return 'JD'; //京东钱包
            case 13:
                return 'JD_WAP'; //京东钱包
            case 10:
                return 'BAIDU'; //百度钱包
            case 17:
                return 'UNION_WALLET';//银联钱包
            default:
                return 'WX';
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @param string $key 商户秘钥
     * @return string $sign签名值
     */
    private function sign($data, $key)
    {
        ksort($data);
        $sign = strtoupper(md5(json_encode($data, 320) . $key));
        return $sign;
    }

    private function encodePay($data)
    {
        $publicKey = openssl_pkey_get_public($this->publicKey);
        $encryptData = '';
        $str = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $publicKey);
            $str = $str . $encryptData;
        }
        return base64_encode($str);
    }

    /**
     * 获取请求地址
     * @param $code
     * @return string
     */
    private function getUrl($code)
    {
        switch ($code) {
            case 1:
                return 'http://wx.qyfpay.com:90/api/pay.action';//微信
                break;
            case 2:
                return 'http://wxwap.qyfpay.com:90/api/pay.action';//微信wap
                break;
            case 33:
                return 'http://wx.qyfpay.com:90/api/pay.action';//微信h5
                break;
            case 4:
                return 'http://zfb.qyfpay.com:90/api/pay.action';//支付宝
                break;
            case 5:
                return 'http://zfbwap.qyfpay.com:90/api/pay.action';//支付宝app
                break;
            case 7:
                return 'http://remit.qyfpay.com:90/api/remit.action';//网银
                break;
            case 8:
                return 'http://qq.qyfpay.com:90/api/pay.action';//qq钱包
                break;
            case 12:
                return 'http://qqwap.qyfpay.com:90/api/pay.action';//qqwap
                break;
            case 9:
                return 'http://jd.qyfpay.com:90/api/pay.action'; //京东钱包
                break;
            case 13:
                return 'http://jd.qyfpay.com:90/api/pay.action'; //京东wap
                break;
            case 10:
                return 'http://baidu.qyfpay.com:90/api/pay.action'; //百度钱包
                break;
            case 17:
                return 'http://unionpay.qyfpay.com:90/api/pay.action';//银联钱包
                break;
            default:
                return 'http://wx.qyfpay.com:90/api/pay.action';//微信
        }
    }

    /**
     * 请求接口
     * @param $url
     * @param $data
     * @return mixed
     */
    private function request($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        return $tmpInfo;
    }

    /**
     * 获取返回
     * @param $code
     * @param $res
     * @return array
     */
    private function getRs($code, $res)
    {
        if (in_array($code, $this->pay_model_1)) {
            $rs = [
                'img' => $res['qrcodeUrl'],
                'money' => $this->money,
                'jump' => 3,
                'order_num' => $this->orderNum
            ];
        } elseif (in_array($code, [2, 5, 12, 13, 33])) {
            $rs = [
                'url' => $res['qrcodeUrl'],
                'jump' => 5
            ];
        } else {
            $temp = [
                'url' => $res['qrcodeUrl'],
                'method' => 'get',
                'data' => []
            ];
            $rs = [
                'url' => $this->domain . "/index.php/pay/pay_test/pay_sest/" . $this->orderNum,
                'json' => json_encode($temp, JSON_UNESCAPED_UNICODE),
                'jump' => 5,
            ];
        }
        return $rs;
    }
}