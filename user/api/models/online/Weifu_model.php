<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 微付支付模块
 * @version     v1.0 2017/12/28
 */
class Weifu_model extends MY_Model
{
    public $privateKey;
    public $serviceKey;
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
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->privateKey = isset($pay_data['pay_private_key']) ? $pay_data['pay_private_key'] : '';//商户私钥
        $this->serviceKey = isset($pay_data['pay_server_key']) ? $pay_data['pay_server_key'] : '';//服务公钥
        $this->url = $this->getUrl($pay_data['code']);//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Weifu/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        if ($pay_data['code'] == 7) {
            return $this->buildForm($data);
        }
        $rs = $this->request($data);
        $rs = new SimpleXMLElement($rs);
        $rs = $rs->response;
        if ($rs->resp_code != "SUCCESS" || $rs->result_code == 1) {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: 通道正在维护！"));
            exit;
        }
        if (in_array($pay_data['code'], [1, 4, 8, 9])) {
            $res = [
                'jump' => 3,
                'img' => $rs->qrcode,
                'money' => $money,
                'order_num' => $order_num,
            ];
        } elseif (in_array($pay_data['code'], [2, 5, 12])) {
            $res = [
                'url' => urldecode($rs->payURL),
                'jump' => 5
            ];
        }
        return $res;
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
        $data['merchant_code'] = $this->merId;// 商户在支付平台的的平台号
        $data['service_type'] = $this->getService($code);// 商户在支付平台的的平台号
        $data['notify_url'] = $this->callback;// 商户通知地址
        $data['interface_version'] = $this->getVersion($code);// 接口版本
        $data['order_no'] = $this->orderNum;// 订单号
        $data['order_amount'] = $this->money;// 金额
        $data['order_time'] = date('Y-m-d H:i:s', time());// 订单时间
        $data['product_name'] = 'WEIFU';// 商品名称
        $data['client_ip'] = get_ip();// IP
        if ($code == 7) {
            $data['input_charset'] = 'UTF-8';//编码字符集
            $data['bank_code'] = $pay_data['bank_type'];//银行代码
        }
        $data['sign'] = $this->sign($data, $this->privateKey);// 签名
        $data['sign_type'] = 'RSA-S';// 签名方式
        return $data;
    }

    /**
     * @param $code
     * @return int
     */
    private function getService($code)
    {
        switch ($code) {
            case 1:
                return 'weixin_scan';//微信扫码
                break;
            case 2:
                return 'weixin_h5api';//微信H5
                break;
            case 4:
                return 'alipay_scan';//支付宝扫码
                break;
            case 5:
                return 'aliapi_h5api';//支付宝H5
                break;
            case 7:
                return 'direct_pay';//网银
                break;
            case 8:
                return 'tenpay_scan';//QQ钱包
                break;
            case 9:
                return 'jdpay_scan';//京东钱包
                break;
            case 12:
                return 'qq_h5api';//QQ钱包H5
                break;
            case 25:
                return 'sign_query';
                break;
            default:
                return 'weixin_scan';
        }
    }

    /**
     * @param $code
     * @return string
     */
    private function getVersion($code)
    {
        if (in_array($code, [1, 2, 4, 5, 8, 9, 12])) {
            return 'V3.1';
        } else {
            return 'V3.0';
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @param string $privateKey 商户私钥
     * @return string $sign签名值
     */
    private function sign($data, $privateKey)
    {
        ksort($data);
        $str = ToUrlParams($data);
        $privateKey = openssl_get_privatekey($privateKey);
        openssl_sign($str, $sign_info, $privateKey, OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
        return $sign;
    }

    /**
     * 获取请求地址
     * @param $code
     * @return string
     */
    private function getUrl($code)
    {
        if (in_array($code, [1, 4, 8, 9])) {
            return 'https://api.wefupay.com/gateway/api/scanpay';
        } else if (in_array($code, [2, 5, 12])) {
            return 'https://api.wefupay.com/gateway/api/h5apipay';
        } else if ($code == 25) {
            return 'https://api.wefupay.com/gateway/api/express';
        } else if ($code == 7) {
            return 'https://pay.wefupay.com/gateway?input_charset=UTF-8';
        }
    }

    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }

    /**
     * 创建表单
     * @param array $data 表单内容
     * @return array
     */
    private function buildForm($data)
    {
        $temp = [
            'method' => 'post',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }
}