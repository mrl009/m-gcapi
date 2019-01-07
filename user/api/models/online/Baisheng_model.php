<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/21
 * Time: 下午6:36
 */

class Baisheng_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money * 100;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/baisheng/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        if (in_array($pay_data['code'], [2,5,7,12,13,18,20,25,39])) {
            return $this->buildForm($data);
        }
        $rs = json_decode($this->request($data), true);
        if ($rs['Code'] != 200) {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: 通道正在维护！"));
            exit;
        }
        return [
            'jump' => 3,
            'img' => $rs['QrCodeUrl'],
            'money' => $money,
            'order_num' => $order_num,
        ];
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $bankType = isset($pay_data['bank_type']) ? $pay_data['bank_type'] : 0;
        // 请求数据赋值
        $data['MerchantId'] = $this->merId;// 商户在支付平台的的平台号
        $data['Timestamp'] = date("Y-m-d H:i:s");//发送请求的时间
        $data['PaymentTypeCode'] = $this->getType($code);//发送请求的时间
        $data['OutPaymentNo'] = $this->orderNum;//商户订单号
        $data['PaymentAmount'] = (string)$this->money;//订单金额
        $data['PassbackParams'] = get_auth_headers('Origin');// 页面返回地址
        $data['NotifyUrl'] = $this->callback;// 商户通知地址
        $data['Sign'] = $this->sign($data);
        return $data;
    }

    private function getType($code)
    {
        switch ($code) {
            case 1:
                return 'WECHAT_QRCODE_PAY';//微信扫码
                break;
            case 2:
                return 'WECHAT_WAP_PAY';//微信H5
                break;
            case 4:
                return 'ALIPAY_QRCODE_PAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY_WAP_PAY';//支付宝H5
                break;
            case 7:
                return 'ONLINE_BANK_PAY';//网银
                break;
            case 8:
                return 'QQ_QRCODE_PAY';//QQ钱包
                break;
            case 9:
                return 'JD_QRCODE_PAY';//京东钱包
                break;
            case 10:
                return 'BD_QRCODE_PAY';//百度钱包
                break;
            case 12:
                return 'QQ_WAP_PAY';//QQ钱包APP
                break;
            case 13:
                return 'JD_WAP_PAY';//京东钱包APP
                break;
            case 17:
                return 'UNIONPAY_QRCODE_PAY';//银联
                break;
            case 18:
                return 'UNIONPAY_WAP_PAY';//银联WAP
                break;
            case 20:
                return 'BD_WAP_PAY';//百度钱包WAP
                break;
            case 25:
                return 'ONLINE_BANK_QUICK_PAY';//快捷
                break;
            case 38:
                return 'SN_QRCODE_PAY';//苏宁钱包
                break;
            case 39:
                return 'SN_WAP_PAY';//苏宁钱包WAP
                break;
            default:
                return 'WECHAT_QRCODE_PAY';
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        ksort($data);
        $arg = "";
        foreach ($data as $k => $v) {
            if ($k == 'Sign' || $k == 'SignType' || $v == '') {
                continue;
            }
            $arg .= $k . "=" . $v . "&";
        }
        $arg = substr($arg, 0, count($arg) - 2);
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return md5($arg . $this->key);
    }

    /**
     * 发送请求
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