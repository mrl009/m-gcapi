<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/13
 * Time: 下午4:17
 */

class Tudou_model extends MY_Model
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
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = $this->getUrl($pay_data['code']);//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/tudou/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        $rs = json_decode($this->request($data), true);
        if ($pay_data['code'] == 7) {
            var_dump($rs);
            exit;
        }
        if ($rs['status'] != "SUCCESS") {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: {$rs['message']}"));
            exit;
        }
        if (in_array($pay_data['code'], [1, 4, 8, 9, 17])) {
            $res = [
                'jump' => 3,
                'img' => $rs['qrCode'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        } else {
            $res = [
                'url' => $rs['payUrl'],
                'jump' => 5
            ];
        }
        return $res;
    }

    /**
     * 获取请求地址
     * @param $code
     * @return string
     */
    private function getUrl($code)
    {
        if ($code == '7') {
            return 'https://www.aloopay.com/v1/api/ebank/pay';
        } elseif (in_array($code, [2, 5, 12])) {
            return 'https://www.aloopay.com/v1/api/h5/pay';
        } else {
            return 'https://www.aloopay.com/v1/api/scancode/pay';
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
        $data['amount'] = $this->money;//订单金额
        $data['clientIp'] = get_ip();//IP
        $data['interfaceVersion'] = '1.0';
        $data['merchantCode'] = $this->merId;// 商户在支付平台的的平台号
        $data['notifyUrl'] = $this->callback;// 商户通知地址
        $data['orderId'] = $this->orderNum;//商户订单号
        $data['productName'] = 'TD';//商品名称
        $data['productDesc'] = 'TD';
        if ($code != 7) {
            $data['serviceType'] = $this->getType($code);
        } else {
            $data['bankCode'] = $pay_data['bank_type'];
            $data['userType'] = 1;
            $data['cardType'] = 1;
            $data['productExt'] = 'TD';
            $data['returnUrl'] = get_auth_headers('Origin');
            $data['openType'] = $pay_data['from_way'] == 3 ? 1 : 2;
        }
        $data['sign'] = $this->sign($data);
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
                return 'weixin_pay';//微信扫码
                break;
            case 2:
                return 'wx_h5';//微信H5
                break;
            case 4:
                return 'alipay_pay';//支付宝扫码
                break;
            case 5:
                return 'ali_h5';//支付宝H5
                break;
            case 8:
                return 'qqmobile_pay';//QQ钱包
                break;
            case 9:
                return 'jdpay_pay';//京东钱包
                break;
            case 12:
                return 'qq_h5';//QQ钱包APP
                break;
            case 17:
                return 'union_pay';//银联
                break;
            default:
                return 'weixin_pay';
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        $str = '';
        ksort($data);
        foreach ($data as $x => $x_value) {
            if ($x_value == '')
                continue;
            $str = $str . $x . '=' . $x_value . '&';
        }
        $str = $str . 'key=' . $this->key;
        return strtoupper(md5($str));
    }

    /**
     * 请求
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $accept = empty($data['serviceType']) ? 'text/html' : 'application/json';
        $headers = array("Content-type: application/json;charset='utf-8'",
            "Accept: " . $accept,
            "Cache-Control: no-cache",
            "Pragma: no-cache"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, (json_encode($data)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }
}