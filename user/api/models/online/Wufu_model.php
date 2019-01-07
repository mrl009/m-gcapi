<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 五福支付模块
 * @version     v1.0 2017/12/27
 */
class Wufu_model extends MY_Model
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
        $this->callback = $this->domain . '/index.php/callback/Wufu/callbackurl';//回调地址
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
        $data['svcName'] = $this->getService($code, $fromWay);// 接口名字
        $data['merId'] = $this->merId;// 商户在支付平台的的平台号
        $data['merchOrderId'] = $this->orderNum;//商户订单号
        $data['tranType'] = $this->getTypeId($code, $fromWay, $bankType);//交易类型
        $data['pName'] = 'Wufu';//商品名称
        $data['amt'] = $this->money;//订单金额
        $data['notifyUrl'] = $this->callback;// 商户通知地址
        $data['retUrl'] = get_auth_headers('Origin');// 页面返回地址
        $data['showCashier'] = $this->getCashier($data['tranType']);// 是否显示收银台
        $data['merData'] = $this->key;// 商户自定义返回数据
        $data['md5value'] = $this->sign($data, $this->key);// 商户自定义返回数据
        return $data;
    }

    /**
     * 獲取service
     * @param int $code
     * @param int $fromWay
     * @return string
     */
    private function getService($code, $fromWay)
    {
        $service = '';
        if ($code == 7) {
            $service = 'gatewayPay';
        } elseif ($code == 25 && $fromWay == 3) {
            $service = 'pcQuickPay';
        } elseif ($code == 25 && $fromWay == 4) {
            $service = 'wapQuickPay';
        } else {
            $service = 'UniThirdPay';
        }
        return $service;
    }

    /**
     * @param $code
     * @param $fromWay
     * @param $bankType
     * @return int
     */
    private function getTypeId($code, $fromWay, $bankType)
    {
        switch ($code) {
            case 1:
                return 'WEIXIN_NATIVE';//微信扫码
                break;
            case 2:
                return 'WEIXIN_H5';//微信H5
                break;
            case 4:
                return 'ALIPAY_NATIVE';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY_H5';//支付宝H5
                break;
            case 7:
                return $bankType;//网银
                break;
            case 8:
                return 'QQ_NATIVE';//QQ钱包
                break;
            case 9:
                return 'JD_NATIVE';//京东钱包
                break;
            case 12:
                return 'QQ_H5';//QQ钱包APP
                break;
            case 13:
                return 'JD_H5';//QQ钱包APP
                break;
            case 17:
                return 'UNIONPAY_NATIVE';//银联
                break;
            case 25:
                if ($fromWay == 3) {
                    return 2000047;
                } else {
                    return 2000048;
                }
                break;
            default:
                return 2;
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @param string $k 支付密钥
     * @return string $sign签名值
     */
    public function sign($data, $k)
    {
        ksort($data);
        $strRet = "";
        foreach ($data as $key => $value) {
            if ($value === "") {
                continue;
            }
            if ($key == "md5value") {
                continue;
            }
            $strRet = $strRet . $value;
        }
        $strRet = $strRet . $k;
        return strtoupper(md5($strRet));
    }

    /**
     * 是否显示收银台
     * @param $type
     * @return int
     */
    private function getCashier($type) {
        return in_array($type, ['ALIPAY_NATIVE', 'WEIXIN_NATIVE', 'QQ_NATIVE', 'UNIONPAY_NATIVE', 'JD_NATIVE']) ? 1 : 0;
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