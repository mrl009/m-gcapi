<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 芒果支付模块
 * @version     v1.0 2017/1/19
 */
class Mangguo_model extends MY_Model
{
    public $key;
    public $serviceKey;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->serviceKey = isset($pay_data['pay_server_key']) ? $pay_data['pay_server_key'] : '';//服务公钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->callback = $pay_data['pay_domain'] . '/index.php/callback/Mangguo/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        $res = json_decode($this->request($data), true);
        if ($res['code'] != '0000') {
            exit(json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: 通道正在维护！{$res['code']} {$res['msg']}")));
        }
        if (in_array($pay_data['code'], [2,5, 12, 16,18])) {
            $rs = [
                'url' => $res['code_url'],
                'jump' => 5
            ];
        } else {
            $rs = [
                'jump' => 3,
                'img' => $res['code_url'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        }
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
        $data['order_time'] = date('Y-m-d H:i:s', time());// 订单时间

        $data['type_code'] = $this->getService($code);// 支付类型
        $data['down_sn'] = $this->orderNum;// 订单号
        $data['subject'] = 'MG';// 商品名称
        $data['amount'] = $this->money;// 金额
        $data['notify_url'] = $this->callback;// 商户通知地址
        $data['return_url'] = get_auth_headers('Origin');// 返回地址
        if ($code == 7) {
            $data['card_type'] = 1;
            $data['bank_segment'] = $pay_data['bank_type'];
            $data['user_type'] = 1;
            $data['agent_type'] = $pay_data['from_way'] == 3 ? 1 : 2;
        }
        $data['sign'] = $this->sign($data);
        $postData = [
            'member_code' => $this->merId,
            'cipher_data' => $this->encrypt($data),
        ];
        return $postData;
    }

    /**
     * @param $code
     * @return int
     */
    private function getService($code)
    {
        switch ($code) {
            case 1:
                return 'wxbs';//微信扫码
            case 2:
                return 'wxh5';//微信H5
            case 4:
                return 'zfbbs';//支付宝扫码zfbh5
            case 5:
                return 'zfbh5';//支付宝h5
            case 7:
                return 'gateway';//网银
            case 8:
                return 'qqbs';//QQ钱包
            case 9:
                return 'jdbs';//京东钱包
            case 12:
                return 'qqwap';//QQ钱包WAP
            case 16:
                return 'qqh5';//QQ钱包H5
            case 18:
                return 'ylh5';//QQ钱包H5
            //case 25:
            //    return 'sms'; //暂时不用
            default:
                return 'wxbs';
        }
    }

    /**
     * 生成内部签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    private function sign($data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            if (!in_array($key, ['sign', 'code', 'msg']) && (!empty($val) || $val === 0 || $val === '0')) {
                $str .= $key . '=' . $val . '&';
            }
        }
        $str .= 'key=' . $this->key;
        return strtolower(md5($str));
    }

    /**
     * @param $params
     * @return string
     */
    public function encrypt($params)
    {
        $originalData = json_encode($params);
        $crypto = '';
        $encryptData = '';
        foreach (str_split($originalData, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $this->serviceKey);
            $crypto .= $encryptData;
        }
        return base64_encode($crypto);
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
}