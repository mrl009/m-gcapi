<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/10
 * Time: 上午10:37
 */

class Youmifu_model extends MY_Model
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
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/youmifu/callbackurl';//回调地址
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
        $data['apiName'] = $fromWay == 4 ? 'WAP_PAY_B2C' : 'WEB_PAY_B2C';// 接口名字
        $data['apiVersion'] = '1.0.0.1';// 接口版本
        $data['platformID'] = $this->merId;// 商户
        $data['merchNo'] = $this->merId;// 商户
        $data['orderNo'] = $this->orderNum;//商户订单号
        $data['tradeDate'] = date('Ymd', time());//商户订单号
        $data['amt'] = $this->money;//订单金额
        $data['merchUrl'] = $this->callback;// 商户通知地址
        $data['merchParam'] = '';
        $data['tradeSummary'] = 'YMF';
        $data['customerIP'] = get_ip();
        $data['signMsg'] = $this->sign($data);// 签名
        if (7 == $code) $data['bankCode'] = $bankType;
        $data['choosePayType'] = $this->getCode($code);//交易类型
        return $data;
    }

    /**
     * @param $code
     * @param $bank
     * @return string
     */
    private function getCode($code, $bank = '')
    {
        switch ($code) {
            case 1:
                return '5';//微信扫码
                break;
            case 2:
                return '11';//微信WAP
                break;
            case 4:
                return '4';//支付宝扫码
                break;
            case 5:
                return '10';//支付宝WAP
                break;
            case 7:
                return '1';//网银
                break;
            case 8:
                return '6';//QQ钱包
                break;
            case 9:
                return '8';//京东钱包
                break;
            case 12:
                return '14';//QQ钱包APP
                break;
            case 15:
                return '21';//京东钱包H5
                break;
            case 16:
                return '15';//QQ钱包H5
                break;
            case 17:
                return '17';//银联
                break;
            case 25:
                return '12';//快捷
                break;
            case 33:
                return '13';//微信H5
                break;
            case 36:
                return '9';//支付宝H5
                break;
            default:
                return '5';
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        $str = sprintf(
            "apiName=%s&apiVersion=%s&platformID=%s&merchNo=%s&orderNo=%s&tradeDate=%s&amt=%s&merchUrl=%s&merchParam=%s&tradeSummary=%s&customerIP=%s",
            $data['apiName'], $data['apiVersion'], $data['platformID'], $data['merchNo'], $data['orderNo'], $data['tradeDate'], $data['amt'], $data['merchUrl'], $data['merchParam'], $data['tradeSummary'], $data['customerIP']
        );
        return md5($str . $this->key);
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