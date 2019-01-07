<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 安全付支付模块
 * @version     v1.0 2017/1/13
 */
class Anquan_model extends MY_Model
{
    public $key;
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
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->privateKey = isset($pay_data['pay_private_key']) ? $pay_data['pay_private_key'] : '';//商户私钥
        $this->serviceKey = isset($pay_data['pay_server_key']) ? $pay_data['pay_server_key'] : '';//服务公钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Anquan/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        if (in_array($pay_data['code'], [2, 5, 7, 25])) {
            return $this->buildForm($data);
        }
        $res = json_decode($this->request($data), true);
        if ($res['ret_code'] != "SUCCESS") {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => '错误信息: 通道正在维护！' . $res['ret_msg']));
            exit;
        }
        $rs = [
            'jump' => 3,
            'is_img' => 1,
            'img' => 'data:image/png;base64,' . $res['code_url'],
            'money' => $money,
            'order_num' => $order_num,
        ];
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
        // 请求数据赋值
        $data['app_id'] = $this->merId;// 商户在支付平台的的平台号
        $data['out_trade_no'] = $this->orderNum;// 订单号
        $data['create_time'] = date('YmdHis', time());// 发起请求时间
        $data['subject'] = 'AQ';// 订单标题
        $data['total_amount'] = $this->money;// 总金额
        $data['pay_type'] = $this->getPayType($code);// 支付方式
        $data['body'] = 'AQ';// 订单详情内容
        $data['return_url'] = get_auth_headers('Origin');// 返回地址
        $data['notify_url'] = $this->callback;// 通知地址
        $data['sign'] = $this->sign($data, $this->privateKey);// 签名
        $code == 7 && $data['bank_code'] = $pay_data['bank_type'];//银行代码
        if ($code != 25 && $code != 7) {
            $data['type'] = in_array($code, [2, 5]) ? 3 : 2;// 返回类型
        }
        return $data;
    }

    /**
     * @param $code
     * @return int
     */
    private function getPayType($code)
    {
        switch ($code) {
            case 1:
                return 80002;//微信扫码
            case 4:
                return 80001;//支付宝扫码
            case 7:
                return 80003;//网银
            case 8:
                return 80004;//QQ钱包
            case 9:
                return 80005;//京东钱包
            case 10:
                return 80006;//百度钱包
            case 25:
                return 80007;//银联
            default:
                return 80002;
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
        openssl_sign($str, $sign_info, $privateKey, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign_info);
        return $sign;
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