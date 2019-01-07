<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 弘宝支付模块
 * @version     v1.0 2017/1/22
 */
class Hongbao_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->callback = $pay_data['pay_domain'] . '/index.php/callback/Hongbao/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        $res = $this->request($data, $pay_data['code']);
        if (empty($res)) {
            exit(json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: 通道正在维护！")));
        }
        if (in_array($pay_data['code'], [2, 5, 7, 25, 33])) {
            $rs = [
                'url' => $res,
                'jump' => 5
            ];
        } else {
            $rs = [
                'jump' => 3,
                'img' => $res,
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
        $bankType = isset($pay_data['bank_type']) ? $pay_data['bank_type'] : 0;
        // 请求数据赋值
        $data['expireTime'] = '';// 过期时间
        $data['summary'] = 'HB';// 交易摘要
        $data['amount'] = $this->money;//订单金额
        $data['tradeDate'] = date('Y-m-d', time());//交易日期
        $data['tradeNo'] = $this->orderNum;//商户订单号
        $data['extra'] = '';//商户参数
        $data['service'] = $code == 7 ? 'B2C' : 'SCANPAY';//接口名字
        $data['merId'] = $this->merId;// 商户在支付平台的的平台号
        if ($code == 7) {
            $data['bankName'] = $bankType;
        } elseif ($code == 25) {
            $data['bankName'] = '银联WAP';
        }
        $data['clientIp'] = get_ip();// IP
        $data['notifyUrl'] = $this->callback;// 商户通知地址
        if ($code != 7 && $code != 25) {
            $data['typeId'] = $this->getTypeId($code);//交易类型
        }
        $data['version'] = '1.0.0.0';//接口版本
        $data['sign'] = $this->sign($data, $this->key);// 商户自定义返回数据
        $code == 25 && $data['bankCardType'] = 'SAVING';
        return $data;
    }

    /**
     * @param $code
     * @return int
     */
    private function getTypeId($code)
    {
        switch ($code) {
            case 1:
                return 1;//微信扫码
            case 2:
                return 11;//微信H5
            case 4:
                return 2;//支付宝扫码
            case 5:
                return 12;//支付宝H5
            case 8:
                return 3;//QQ钱包
            case 33:
                return 10;
            default:
                return 1;
        }
    }

    /**
     * 获取支付签名
     * @param array $data 支付参数
     * @param string $k 支付密钥
     * @return string $sign签名值
     */
    function sign($data, $k)
    {
        $md5str = '{';
        foreach ($data as $key => $val) {
            $md5str .= $key . '=' . $val . ', ';
        }
        $signStr = trim($md5str, ', ') . '}' . $k;
        return md5($signStr);
    }

    /**
     * 请求数据
     * @param $data
     * @param $code
     * @return mixed
     */
    function request($data, $code)
    {
        $client = new SoapClient($this->url);
        $client->soap_defencoding = 'utf-8';
        $client->xml_encoding = 'utf-8';
        $client->decode_utf8 = false;
        if ($code == 7 || $code == 25) {
            $rs = $client->b2c(['merchantId' => $this->merId, 'paramsMaps' => json_encode($data)]);
            preg_match('{<script language="javascript" type="text/javascript">window.location.href=(.*?)</script>}', $rs->result, $match);
            $rs = isset($match[1]) ? trim($match[1], '"') : '';
        } else {
            $rs = $client->scan(['merchantId' => $this->merId, 'paramsMaps' => json_encode($data)]);
            $rs = $rs->result ? $rs->result : '';
        }
        return $rs;
    }
}