<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 悠悠支付模块
 * @version     v1.0 2017/12/28
 */
class Uu_model extends MY_Model
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
        $this->money = $money * 100;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->privateKey = isset($pay_data['pay_private_key']) ? $pay_data['pay_private_key'] : '';//商户私钥
        $this->serviceKey = isset($pay_data['pay_server_key']) ? $pay_data['pay_server_key'] : '';//服务公钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Uu/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        return $this->buildForm($data);
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $fromWay = isset($pay_data['from_way']) ? $pay_data['from_way'] : 0;
        $bankType = isset($pay_data['bank_type']) ? $pay_data['bank_type'] : 0;
        // 请求数据赋值
        $data['merchantNo'] = $this->merId;// 商户在支付平台的的平台号
        $data['outTradeNo'] = $this->orderNum;//商户订单号
        $data['amount'] = $this->money;//订单金额
        $data['content'] = 'UU';//交易主题
        $data['payType'] = $this->getPayType($code, $fromWay);//交易类型
        $data['outContext'] = '';//商户自定义返回数据
        $data['returnURL'] = get_auth_headers('Origin');// 页面返回地址
        $data['callbackURL'] = $this->callback;// 商户通知地址
        $data['defaultBank'] = $data['payType'] == 'DEBIT_BANK_CARD_PAY' ? $bankType : '';// 选择银行
        $data['sign'] = $this->sign($data, $this->privateKey);// 签名
        return $data;
    }

    /**
     * @param $code
     * @param $fromWay
     * @return int
     */
    private function getPayType($code, $fromWay)
    {
        switch ($code) {
            case 1:
                return 'WECHAT_QRCODE_PAY';//微信扫码
                break;
            case 2:
                return 'H5_WECHAT_PAY';//微信H5
                break;
            case 4:
                return 'ALIPAY_QRCODE_PAY';//支付宝扫码
                break;
            case 5:
                return 'H5_ALI_PAY';//支付宝H5
                break;
            case 7:
                return 'DEBIT_BANK_CARD_PAY';//网银
                break;
            case 8:
                return 'QQ_QRCODE_PAY';//QQ钱包
                break;
            case 17:
                return 'UNION_QRCODE_PAY';//银联
                break;
            case 25:
                if ($fromWay == 3) {
                    return 'DEBIT_BANK_QUICK_PAY';
                } else {
                    return 'BANK_QUICK_QRCODE_PAY';
                }
                break;
            default:
                return 'WECHAT_QRCODE_PAY';
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
        openssl_sign($str, $sign_info, $privateKey, OPENSSL_ALGO_SHA1);
        $sign = base64_encode($sign_info);
        return $sign;
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