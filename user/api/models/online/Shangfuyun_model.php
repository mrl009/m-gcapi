<?php

/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/12
 * Time: 上午11:38
 */
class Shangfuyun_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;
    public $signStr;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/shangfuyun/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        if (in_array($pay_data['code'], [2, 5, 7, 12])) {
            return $this->buildForm($data);
        }
        $rs = $this->request($this->signStr . '&sign=' . $data['sign']);
        $rs = new SimpleXMLElement($rs);
        if ($rs->detail->code != '00') {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误信息: {$rs->detail->desc} 通道正在维护！"));
            exit;
        }
        return [
            'jump' => 3,
            'img' => base64_decode($rs->detail->qrCode),
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
        // 请求数据赋值
        $data['service'] = $this->getService($code);// 接口名字
        $data['version'] = '1.0.0.0';// 接口版本
        $data['merId'] = $this->merId;// 商户在支付平台的的平台号
        $code != 7 && $data['typeId'] = $this->getTypeId($code);//交易类型
        $code == 7 && $data['bankId'] = $pay_data['bank_type'];//交易类型
        $data['tradeNo'] = $this->orderNum;//商户订单号
        $data['tradeDate'] = date('Ymd');//交易日期
        $data['amount'] = $this->money;//订单金额
        $data['notifyUrl'] = $this->callback;// 商户通知地址
        $data['summary'] = 'SYF';//商品名称
        $data['clientIp'] = get_ip();//IP
        $data['sign'] = $this->sign($data);
        return $data;
    }

    /**
     * 獲取service
     * @param int $code
     * @return string
     */
    private function getService($code)
    {
        if ($code == 7) {
            $service = 'TRADE.B2C';
        } elseif ($code == 25) {
            $service = 'TRADE.QUICKPAY.APPLY';
        } elseif (in_array($code, [2, 5, 12])) {
            $service = 'TRADE.H5PAY';
        } else {
            $service = 'TRADE.SCANPAY';
        }
        return $service;
    }

    /**
     * @param $code
     * @return int
     */
    private function getTypeId($code)
    {
        switch ($code) {
            case 1:
                return 2;//微信扫码
                break;
            case 2:
                return 2;//微信H5
                break;
            case 4:
                return 1;//支付宝扫码
                break;
            case 5:
                return 1;//支付宝H5
                break;
            case 8:
                return 3;//QQ钱包
                break;
            case 9:
                return 5;//京东钱包
                break;
            case 12:
                return 3;//QQ钱包APP
                break;
            case 17:
                return 4;//银联
                break;
            default:
                return 2;
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        $rs = '';
        if ($data['service'] == 'TRADE.B2C') {
            //1网银支付
            $rs = sprintf(
                "service=%s&version=%s&merId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&summary=%s&clientIp=%s&bankId=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['amount'],
                $data['notifyUrl'],
                $data['summary'],
                $data['clientIp'],
                $data['bankId']
            );
        } else if ($data['service'] == 'TRADE.SCANPAY' || $data['service'] == 'TRADE.H5PAY') {
            $rs = sprintf(
                "service=%s&version=%s&merId=%s&typeId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&summary=%s&clientIp=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['typeId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['amount'],
                $data['notifyUrl'],
                $data['summary'],
                $data['clientIp']
            );
        }
        $this->signStr = $rs;
        return md5($rs . $this->key);
    }

    /**
     * 请求
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $curl = curl_init();
        $curlData = array();
        $curlData[CURLOPT_POST] = true;
        $curlData[CURLOPT_URL] = $this->url;
        $curlData[CURLOPT_RETURNTRANSFER] = true;
        $curlData[CURLOPT_TIMEOUT] = 120;
        $curlData[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($curl, $curlData);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $rs = curl_exec($curl);
        curl_close($curl);
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