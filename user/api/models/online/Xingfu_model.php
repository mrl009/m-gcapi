<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 星付支付模块
 *
 * @author      ssm
 * @package     model/online/Xingfu
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Xingfu_model extends Basepay_model
{

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
     *
     * @access protected
     * @param Integer $code 支付编号
     * @param String $bank 银行编号
     * @return String
     */
    protected function _get_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return '2';//微信生成二维码
            case 2:
                return '2';//微信跳转网页
            case 4:
                return '1';//支付宝生成二维码
            case 5:
                return '1';//支付宝跳转网页
            case 7:
                return $bank;//網銀
            case 8:
                return '3';//qq钱包生成二维码
            case 9:
                return '5';//京东生成二维码
            case 12:
                return '3';//qq钱包跳转网页
            case 19:
                return '4';//银联钱包扫码
            case 25:
                return $bank;//快捷支付
            case 26:
                return $bank;//收银台
            default:
                return '2';
        }
    }

    /**
     * 获取支付参数
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Float $money 金额
     * @param String $pay_data 第三方支付信息
     * @return Array
     */
    protected function _get_data($order_num, $money, $pay_data)
    {
        $data['merId'] = trim($pay_data['pay_id']);
        $data['tradeNo'] = trim($order_num);
        $data['amount'] = $money;
        $data['version'] = '1.0.0.0';
        $data['tradeDate'] = date('Ymd');
        $data['summary'] = 'xingfu';
        $data['clientIp'] = get_ip();
        $data['notifyUrl'] = $pay_data['pay_domain'].'/index.php/callback/Xingfu/hrefbackurl';
        $payType = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        if ($pay_data['code'] == 7) {//网银
            $data['bankId'] = $payType;
            $data['service'] = 'TRADE.B2C';
        }elseif ($pay_data['code'] == 26) {//收银台
            $data['bankId'] = '';
            $data['service'] = 'TRADE.B2C';
        }  elseif(in_array($pay_data['code'],$this->pay_model_1)) {
            $data['typeId'] = $payType;
            $data['service'] = 'TRADE.SCANPAY';
        } else{
            $data['typeId'] = $payType;
            $data['service'] = 'TRADE.H5PAY';
        }

        return $data;
    }

    /**
     * 获取支付签名
     *
     * @access protected
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return String $sign签名值
     */
    protected function _get_sign($data, $pay_data)
    {
        $tokenKey = $pay_data['pay_key'];
        //1网银支付
        if($data['service'] == 'TRADE.B2C') {
            $result = sprintf(
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
        }
        //2扫码支付 //3.跳轉wap
        else if($data['service'] == 'TRADE.SCANPAY' || $data['service'] == 'TRADE.H5PAY'){
            $result = sprintf(
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
        else {
            $this->_error_echo('无法识别的支付类型，请联系客服');
        }
        return md5($result.$tokenKey);
    }

    /**
     * 获取支付结果
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return Array
     */
    protected function _get_result($order_num, $ZFdata, $pay_data)
    {   

        if ($ZFdata['service'] == 'TRADE.B2C') {
            // 网银和收银台
            $temp['url']    = $pay_data['pay_url'];
            $temp['method'] = 'post';
            $temp['data']   = $ZFdata;;
            $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";
            $data['jump']   = 5;
            $data['url']    = $url.'/'.$order_num;
            $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
        }elseif($ZFdata['service'] == 'TRADE.SCANPAY'){ // 扫码
            $res = $this->pay_curl($pay_data['pay_url'], $pay_data['pay_domain'], $ZFdata);
            $res = FromXml($res);
            if ($res['detail']['code'] != '00') {
                $str = "错误信息: {$res['detail']['desc']}";
                $this->_error_echo($str);
            }
            $data['jump']   = 3; //设置支付方式的返回格式
            $data['img']    = base64_decode($res['detail']['qrCode']);//二维码的
            $data['money']  = $ZFdata['amount']; //支付的钱
            $data['order_num']  = $order_num;//订单号
            return $data;
        }elseif($ZFdata['service'] == 'TRADE.H5PAY'){//wap，h5
            $temp['url']    = $pay_data['pay_url'];
            $temp['method'] = 'post';
            $temp['data']   = $ZFdata;
            $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";
            $data['jump']   = 5;
            $data['url']    = $url.'/'.$order_num;
            $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
        }


    }
}
