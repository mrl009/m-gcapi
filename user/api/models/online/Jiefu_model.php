<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 捷付支付模块
 *
 * @author      ssm
 * @package     model/online/Jiefu
 * @version     v1.0 2017/09/26
 * @copyright
 * @link
 */
class Jiefu_model extends Basepay_model
{

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
     *
     * @access protected
     * @param Integer $code 支付编号【支付类型@1微信#2微信app#3微信扫码#4支付宝#5支付宝APP#6支付宝扫码#7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡,12qq钱包WAP,13京东钱包WAP 逗号分割】
     * @param String $bank 银行编号
     * @return String
     */
    protected function _get_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return 'WEIXIN';//微信
            case 2:
                return 'WEIXINWAP';//微信
            case 4:
                return 'ALIPAY';//支付宝
            case 5:
                return 'ALIPAYWAP';//微信
            case 7:
                return $bank;
            case 8:
                return 'QQ';//qq钱包
            case 12:
                return 'QQWAP';//qq钱包
            default:
                return 'DEFAULT';//百度钱包
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
        $data['partner'] = (string)trim($pay_data['pay_id']);
        $data['callbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Jiefu/callbackurl';
        $data['hrefbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Jiefu/hrefbackurl';
        $data['paymoney']  =  $money;
        $data['ordernumber'] = $order_num;
        $bank_code = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['banktype']  = $bank_code;

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
        $k = "partner={$data['partner']}&banktype={$data['banktype']}&paymoney={$data['paymoney']}&ordernumber={$data['ordernumber']}&callbackurl={$data['callbackurl']}{$pay_data['pay_key']}";
        return md5($k);
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
        $data = parent::_get_result2('get', $order_num, $ZFdata, $pay_data);
        return $data;
    }
}
